<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditAction;
use App\Exceptions\CardRegistrationException;
use App\Models\FincodeCard;
use App\Models\FincodeCustomer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Fincode\FincodeApiException;
use App\Services\Fincode\FincodeClient;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FincodeCustomerService
{
    private const CUSTOMER_CREATION_LOCK_SECONDS = 10;

    private const CUSTOMER_CREATION_LOCK_WAIT_SECONDS = 5;

    public function __construct(
        private FincodeClient $fincodeClient
    ) {}

    // fincode顧客の存在を保証する（冪等：既存があればそのまま返す）
    public function ensureCustomerExists(User $user, Tenant $tenant): FincodeCustomer
    {
        // 高速パス: 既に存在すればロック不要で即返却
        $fincodeCustomer = FincodeCustomer::findByUserAndTenant($user, $tenant);
        if ($fincodeCustomer !== null) {
            return $fincodeCustomer;
        }

        // 同一ユーザー+テナントの同時リクエストを直列化し、fincode API二重呼び出しを防ぐ
        $lock = Cache::lock("fincode_customer:{$user->id}:{$tenant->id}", self::CUSTOMER_CREATION_LOCK_SECONDS);

        try {
            $lock->block(self::CUSTOMER_CREATION_LOCK_WAIT_SECONDS);
        } catch (LockTimeoutException $e) {
            // ロック取得失敗 → 他プロセスが作成中のはず → 最終チェック
            $fincodeCustomer = FincodeCustomer::findByUserAndTenant($user, $tenant);
            if ($fincodeCustomer !== null) {
                return $fincodeCustomer;
            }
            Log::error('Failed to acquire lock for fincode customer creation', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
            ]);
            throw new FincodeApiException(null, [], '顧客登録が混み合っています。しばらくしてから再度お試しください。');
        }

        try {
            // ロック取得後に再チェック（先行プロセスが作成済みの場合）
            $fincodeCustomer = FincodeCustomer::findByUserAndTenant($user, $tenant);
            if ($fincodeCustomer !== null) {
                return $fincodeCustomer;
            }

            // fincode側にも顧客レコードが必要なため、外部APIで先に作成する（ロック保持中 → 1プロセスのみ実行）
            $customerResponse = $this->fincodeClient->createCustomer([
                'name' => $user->name,
                'email' => $user->email,
                'tenant_shop_id' => $tenant->fincode_shop_id,
            ]);

            if (! $customerResponse->isSuccess()) {
                Log::error('Failed to create fincode customer', [
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                    'error_code' => $customerResponse->errorCode,
                ]);
                throw new FincodeApiException(
                    $customerResponse->errorCode,
                    $customerResponse->rawResponse,
                    '顧客登録に失敗しました。'
                );
            }

            // fincode側のIDとローカルユーザーの紐付けを保持するため、ローカルDBにも記録する
            try {
                $fincodeCustomer = FincodeCustomer::create([
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                    'fincode_customer_id' => $customerResponse->id,
                ]);
            } catch (UniqueConstraintViolationException $e) {
                // ロックを貫通した同時リクエストによる重複 → 既存レコードを使用
                Log::warning('Fincode customer unique constraint violation despite lock', [
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                ]);
                $fincodeCustomer = FincodeCustomer::findByUserAndTenant($user, $tenant);
            }

            Log::info('Fincode customer created', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'fincode_customer_id' => $customerResponse->id,
            ]);

            return $fincodeCustomer;
        } finally {
            $lock->release();
        }
    }

    // 顧客登録とカード登録を同時に行う
    public function registerCustomerWithCard(
        User $user,
        Tenant $tenant,
        string $cardToken,
        bool $isDefault = true
    ): FincodeCard {
        // Step 1: 顧客確保（トランザクション外 — ensureCustomerExists 内部でロック管理済み）
        try {
            $fincodeCustomer = $this->ensureCustomerExists($user, $tenant);
        } catch (FincodeApiException $e) {
            throw new CardRegistrationException(
                $e->errorCode,
                '顧客登録に失敗しました。',
                tokenConsumed: false,
            );
        }

        // Step 2: カード登録API呼出（トランザクション外 — 外部APIレスポンス遅延時のDBロック保持を防ぐ）
        // registerCard() 呼び出し以降はトークンが消費されるため、全例外で tokenConsumed: true を設定する
        try {
            // トークンの有効期限が短いため、顧客登録直後にカード登録APIを呼び出す
            $cardResponse = $this->fincodeClient->registerCard(
                $fincodeCustomer->fincode_customer_id,
                [
                    'token' => $cardToken,
                    'default_flag' => $isDefault,
                    'tenant_shop_id' => $tenant->fincode_shop_id,
                ]
            );

            if (! $cardResponse->isSuccess()) {
                Log::error('Failed to register card', [
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                    'error_code' => $cardResponse->errorCode,
                ]);
                throw new CardRegistrationException(
                    $cardResponse->errorCode,
                    'カード登録に失敗しました。',
                    tokenConsumed: true,
                );
            }
        } catch (CardRegistrationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Card registration failed unexpectedly', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'message' => $e->getMessage(),
            ]);
            throw new CardRegistrationException(
                null,
                'カード登録に失敗しました。',
                tokenConsumed: true,
            );
        }

        // Step 3: DB書込みのみトランザクション内で実行
        return DB::transaction(function () use ($user, $tenant, $fincodeCustomer, $cardResponse, $isDefault) {
            // デフォルトカードは1枚のみ許容するため、新規がデフォルトなら既存を解除する
            if ($isDefault) {
                $fincodeCustomer->cards()->update(['is_default' => false]);
            }

            // 表示用情報（末尾4桁、ブランド、有効期限）をローカルに保持し、一覧表示時にfincodeへの問い合わせを不要にする
            $card = FincodeCard::create([
                'fincode_customer_id' => $fincodeCustomer->id,
                'fincode_card_id' => $cardResponse->id,
                'card_no_display' => $cardResponse->getCardNoDisplay(),
                'brand' => $cardResponse->brand,
                'expire' => $cardResponse->expire,
                'is_default' => $isDefault,
            ]);

            Log::info('Fincode card registered', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'card_id' => $card->id,
                'fincode_card_id' => $cardResponse->id,
            ]);

            return $card;
        });
    }

    public function getCards(User $user, Tenant $tenant): Collection
    {
        return FincodeCard::query()
            ->select('fincode_cards.*')
            ->join('fincode_customers', 'fincode_customers.id', '=', 'fincode_cards.fincode_customer_id')
            ->where('fincode_customers.user_id', $user->id)
            ->where('fincode_customers.tenant_id', $tenant->id)
            ->orderByDesc('fincode_cards.is_default')
            ->orderByDesc('fincode_cards.created_at')
            ->get();
    }

    // カードを削除する
    public function deleteCard(User $user, Tenant $tenant, int $cardId): bool
    {
        $fincodeCustomer = FincodeCustomer::findByUserAndTenant($user, $tenant);

        if ($fincodeCustomer === null) {
            return false;
        }

        $card = $fincodeCustomer->cards()->find($cardId);

        if ($card === null) {
            return false;
        }

        try {
            // fincode側のカード情報も確実に削除し、不要なカード情報が外部に残らないようにする
            $this->fincodeClient->deleteCard(
                $fincodeCustomer->fincode_customer_id,
                $card->fincode_card_id,
                $tenant->fincode_shop_id
            );
        } catch (FincodeApiException $e) {
            Log::error('Failed to delete card from fincode', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'card_id' => $card->id,
                'error_code' => $e->errorCode,
            ]);
            throw new CardRegistrationException(
                $e->errorCode,
                'カード削除に失敗しました。'
            );
        }

        Log::info('Fincode card deleted', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'card_id' => $card->id,
            'fincode_card_id' => $card->fincode_card_id,
        ]);

        // fincode側の削除が成功した後にローカルも削除する（順序を守り、ローカルだけ消えてfincode側に残る不整合を防ぐ）
        AuditLogger::log(
            action: AuditAction::CardDeleted,
            target: $card,
            changes: [
                'old' => [
                    'fincode_card_id' => $card->fincode_card_id,
                    'card_no_display' => $card->card_no_display,
                    'brand' => $card->brand,
                ],
                'metadata' => [
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                ],
            ],
        );

        $card->delete();

        return true;
    }

    public function getFincodeCustomer(User $user, Tenant $tenant): ?FincodeCustomer
    {
        return FincodeCustomer::findByUserAndTenant($user, $tenant);
    }
}
