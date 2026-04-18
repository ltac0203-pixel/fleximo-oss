<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\PaymentFailedException;
use App\Models\FincodeCard;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Concerns\HandlesFincodePaymentErrors;
use App\Services\Fincode\FincodeApiException;
use App\Services\Fincode\FincodeClient;
use Illuminate\Support\Facades\Log;

class CardPaymentService
{
    use HandlesFincodePaymentErrors;

    public function __construct(
        private FincodeClient $fincodeClient,
        private FincodeCustomerService $fincodeCustomerService
    ) {}

    // クレジットカード決済を開始する（3DS認証付き）
    public function initiateCardPayment(Payment $payment, Order $order, ?int $cardId = null): PaymentInitiationResult
    {
        $fincodeCard = null;
        if ($cardId !== null) {
            // 入口のバリデーション有無に依存せず、決済実行時点でユーザー/テナント境界を強制する
            $fincodeCard = $this->resolveAccessibleSavedCard($payment, $order, $cardId);
        }

        $tds2RetUrl = $this->buildThreeDsCallbackUrl($payment);

        $response = $this->fincodeClient->createCardPaymentWith3ds([
            'amount' => $order->total_amount,
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'tenant_shop_id' => $order->tenant->fincode_shop_id,
            'tds2_ret_url' => $tds2RetUrl,
        ]);

        // 後続の3DS認証・決済実行でfincode側と紐付けるため、返却されたIDを永続化する
        $payment->fincode_id = $response->id;
        $payment->fincode_access_id = $response->accessId;

        // 保存済みカードはトークンではなくcustomer_id+card_idで決済するため、これらも記録する
        if ($cardId !== null) {
            /** @var FincodeCard $fincodeCard */
            $payment->fincode_customer_id = $fincodeCard->fincodeCustomer->fincode_customer_id;
            $payment->fincode_card_id = $fincodeCard->fincode_card_id;
        } else {
            // 新規カード → fincode顧客を確保して決済と顧客をテナント単位で紐付ける
            $fincodeCustomer = $this->fincodeCustomerService->ensureCustomerExists(
                $order->user,
                $order->tenant
            );
            $payment->fincode_customer_id = $fincodeCustomer->fincode_customer_id;
        }

        $payment->save();

        Log::info('Card payment initiated with 3DS', [
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'fincode_id' => $response->id,
            'uses_saved_card' => $cardId !== null,
        ]);

        if ($cardId !== null) {
            return PaymentInitiationResult::forSavedCard(
                $payment,
                $response->id,
                $response->accessId
            );
        }

        return PaymentInitiationResult::forCard(
            $payment,
            $response->id,
            $response->accessId
        );
    }

    // クレジットカード決済を実行する（トークン受信後）
    public function executeCardPayment(Payment $payment, string $token): Payment
    {
        if (! $payment->isPending()) {
            throw new PaymentFailedException(
                $payment,
                null,
                '決済は既に処理されています。'
            );
        }

        if (! $payment->fincode_id) {
            throw new PaymentFailedException(
                $payment,
                null,
                'fincode決済IDが設定されていません。'
            );
        }

        try {
            $accessId = $payment->getRawOriginal('fincode_access_id');
            if ($accessId === null || $accessId === '') {
                $accessId = $this->getAccessId($payment);
            }

            if ($accessId === null || $accessId === '') {
                throw new PaymentFailedException(
                    $payment,
                    null,
                    'fincode access_idが取得できませんでした。'
                );
            }

            $payment->markAsProcessing();

            // トークンの有効期限が短いため、processing状態にした直後にAPI呼び出しを行う
            $response = $this->fincodeClient->executeCardPayment($payment->fincode_id, [
                'access_id' => $accessId,
                'token' => $token,
                'tenant_shop_id' => $payment->tenant->fincode_shop_id,
            ]);

            if ($response->isCaptured()) {
                $this->completePaymentWithOrder($payment);

                Log::info('Card payment completed', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'fincode_id' => $payment->fincode_id,
                ]);
            } else {
                $this->failPaymentWithOrder($payment);

                throw new PaymentFailedException(
                    $payment,
                    $response->errorCode,
                    '決済が完了しませんでした。'
                );
            }

            return $payment;
        } catch (FincodeApiException $e) {
            throw $this->handleFincodeException($payment, $e, 'Card payment failed', '決済処理に失敗しました。');
        }
    }

    private function getAccessId(Payment $payment): ?string
    {
        try {
            $response = $this->fincodeClient->getPayment(
                $payment->fincode_id,
                $payment->tenant->fincode_shop_id,
                'Card'
            );

            $accessId = $response->accessId;
            if ($accessId === null || $accessId === '') {
                return null;
            }

            return $accessId;
        } catch (FincodeApiException $e) {
            Log::error('Failed to get access_id from fincode', [
                'payment_id' => $payment->id,
                'fincode_id' => $payment->fincode_id,
                'fincode_error' => $e->errorCode,
            ]);

            throw $e;
        }
    }

    private function resolveAccessibleSavedCard(Payment $payment, Order $order, int $cardId): FincodeCard
    {
        $fincodeCard = FincodeCard::query()
            ->with('fincodeCustomer')
            ->whereKey($cardId)
            ->whereHas('fincodeCustomer', function ($query) use ($order) {
                $query->where('user_id', $order->user_id)
                    ->where('tenant_id', $order->tenant_id);
            })
            ->first();

        if ($fincodeCard === null) {
            Log::warning('Saved card rejected due to user/tenant mismatch', [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'card_id' => $cardId,
                'order_user_id' => $order->user_id,
                'order_tenant_id' => $order->tenant_id,
            ]);

            $this->failPaymentWithOrder($payment);

            throw new PaymentFailedException(
                $payment,
                null,
                'このカードは当店舗で使用できません。'
            );
        }

        return $fincodeCard;
    }
}
