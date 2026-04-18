<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\CardRegistrationException;
use App\Exceptions\PaymentFailedException;
use App\Models\Payment;
use App\Services\Concerns\HandlesFincodePaymentErrors;
use App\Services\Fincode\FincodeApiException;
use App\Services\Fincode\FincodeClient;
use Illuminate\Support\Facades\Log;

class ThreeDsPaymentService
{
    use HandlesFincodePaymentErrors;

    public function __construct(
        private FincodeClient $fincodeClient,
        private FincodeCustomerService $fincodeCustomerService
    ) {}

    // 3DS用に決済を実行する（カード情報送信→acs_url取得）
    public function executePayment(
        Payment $payment,
        ?string $token = null,
        bool $saveCard = false,
        bool $saveAsDefault = false
    ): string {
        if (! $payment->fincode_id || ! $payment->fincode_access_id) {
            throw new PaymentFailedException(
                $payment,
                null,
                'fincode決済IDまたはaccess_idが設定されていません。'
            );
        }

        try {
            // カード保存が要求されている場合、決済前に同期的にカード登録を行う
            if ($this->shouldRegisterCardBeforePayment($payment, $token, $saveCard)) {
                $this->registerCardBeforePayment($payment, $token, $saveAsDefault);
            }

            $payment->markAsProcessing();

            $params = [
                'access_id' => $payment->getRawOriginal('fincode_access_id') ?? $payment->fincode_access_id,
                'tenant_shop_id' => $payment->tenant->fincode_shop_id,
                'tds2_ret_url' => $this->buildThreeDsCallbackUrl($payment),
            ];

            // 保存済みカードはcustomer_id+card_idで決済、新規カードはトークンで決済
            if ($payment->usesSavedCard()) {
                $params['customer_id'] = $payment->fincode_customer_id;
                $params['card_id'] = $payment->fincode_card_id;
            } elseif ($token !== null) {
                $params['token'] = $token;
                // 新規カードでもcustomer_idがあれば渡す（fincode側で決済と顧客を紐付け）
                if ($payment->fincode_customer_id !== null) {
                    $params['customer_id'] = $payment->fincode_customer_id;
                }
            }

            $response = $this->fincodeClient->executeCardPaymentFor3ds($payment->fincode_id, $params);

            Log::info('3DS payment executed, acs_url received', [
                'payment_id' => $payment->id,
                'has_acs_url' => $response->acsUrl !== null,
                'tds2_trans_result' => $response->tds2TransResult,
                'fincode_status' => $response->status,
            ]);

            if ($response->acsUrl === null) {
                Log::warning('3DS redirect URL missing from fincode response', [
                    'payment_id' => $payment->id,
                    'has_redirect_url' => isset($response->rawResponse['redirect_url']),
                    'has_acs_url' => isset($response->rawResponse['acs_url']),
                    'tds2_trans_result' => $response->tds2TransResult,
                    'fincode_status' => $response->status,
                ]);

                throw new PaymentFailedException(
                    $payment,
                    null,
                    '3DS認証用のリダイレクトURLが取得できませんでした。'
                );
            }

            return $response->acsUrl;
        } catch (FincodeApiException $e) {
            throw $this->handleFincodeException($payment, $e, '3DS payment execution failed', '決済実行に失敗しました。');
        }
    }

    // 3DS認証を実行する（3DS Method完了後、tds2_ret_urlから受け取ったparamで認証）
    public function executeAuthentication(Payment $payment, string $param): ThreeDsAuthResult
    {
        if (! $payment->fincode_access_id) {
            $this->markPaymentAndOrderAsFailed($payment, 'missing_access_id_on_3ds_authentication');

            throw new PaymentFailedException(
                $payment,
                null,
                'fincode access_idが設定されていません。'
            );
        }

        try {
            // 決済実行ステップで既にカード情報は送信済みのため、paramのみで認証を実行する
            $response = $this->fincodeClient->execute3dsAuthentication(
                $payment->fincode_access_id,
                $param,
                $payment->tenant->fincode_shop_id
            );

            // コールバック処理やデバッグ時に認証結果を参照できるよう永続化する
            $payment->tds_trans_result = $response->tds2TransResult;
            $payment->tds_challenge_url = $response->challengeUrl;
            $payment->save();

            Log::info('3DS authentication executed', [
                'payment_id' => $payment->id,
                'tds_trans_result' => $response->tds2TransResult,
            ]);

            if ($response->is3dsAuthenticated()) {
                return $this->executePaymentAfterAuthentication($payment);
            }

            if ($response->requires3dsChallenge()) {
                return ThreeDsAuthResult::requiresChallenge($payment, $response->challengeUrl);
            }

            $this->failPaymentWithOrder($payment);

            Log::warning('3DS authentication failed', [
                'payment_id' => $payment->id,
                'tds_trans_result' => $response->tds2TransResult,
            ]);

            return ThreeDsAuthResult::failed($payment);
        } catch (FincodeApiException $e) {
            throw $this->handleFincodeException($payment, $e, '3DS authentication request failed', '3DS認証に失敗しました。');
        }
    }

    // 3DSチャレンジ後の決済を確定する
    public function confirmAndExecute(Payment $payment, string $param): ThreeDsAuthResult
    {
        if (! $payment->fincode_access_id) {
            $this->markPaymentAndOrderAsFailed($payment, 'missing_access_id_on_3ds_confirmation');

            throw new PaymentFailedException(
                $payment,
                null,
                'fincode access_idが設定されていません。'
            );
        }

        try {
            // チャレンジ画面からのリダイレクト後、fincode側に認証結果を問い合わせる
            $response = $this->fincodeClient->get3dsAuthenticationResult(
                $payment->fincode_access_id,
                $payment->tenant->fincode_shop_id
            );

            // デバッグや決済調査のため、チャレンジ後の認証結果も永続化する
            $payment->tds_trans_result = $response->tds2TransResult;
            $payment->save();

            Log::info('3DS callback result received', [
                'payment_id' => $payment->id,
                'tds_trans_result' => $response->tds2TransResult,
            ]);

            if ($response->is3dsAuthenticated()) {
                return $this->executePaymentAfterAuthentication($payment);
            }

            $this->failPaymentWithOrder($payment);

            Log::warning('3DS challenge authentication failed', [
                'payment_id' => $payment->id,
                'tds_trans_result' => $response->tds2TransResult,
            ]);

            return ThreeDsAuthResult::failed($payment);
        } catch (FincodeApiException $e) {
            throw $this->handleFincodeException($payment, $e, '3DS callback processing failed', '3DS認証の確認に失敗しました。');
        }
    }

    // 3DS認証後に決済を実行する
    private function executePaymentAfterAuthentication(Payment $payment): ThreeDsAuthResult
    {
        if (! $payment->fincode_id) {
            $this->markPaymentAndOrderAsFailed($payment, 'missing_fincode_id_on_3ds_capture');

            throw new PaymentFailedException(
                $payment,
                null,
                'fincode決済IDが設定されていません。'
            );
        }

        if (! $payment->fincode_access_id) {
            $this->markPaymentAndOrderAsFailed($payment, 'missing_access_id_on_3ds_capture');

            throw new PaymentFailedException(
                $payment,
                null,
                'fincode access_idが設定されていません。'
            );
        }

        $response = $this->fincodeClient->executePaymentAfter3ds(
            $payment->fincode_id,
            $payment->fincode_access_id,
            $payment->tenant->fincode_shop_id
        );

        if ($response->isCaptured()) {
            $this->completePaymentWithOrder($payment);

            Log::info('3DS payment completed', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
            ]);

            return ThreeDsAuthResult::authenticated($payment);
        }

        $this->failPaymentWithOrder($payment);

        Log::warning('3DS payment capture failed', [
            'payment_id' => $payment->id,
            'fincode_status' => $response->status,
        ]);

        return ThreeDsAuthResult::failed($payment);
    }

    // 決済前にカード登録を同期的に実行する
    private function registerCardBeforePayment(Payment $payment, string $token, bool $saveAsDefault): void
    {
        try {
            $card = $this->fincodeCustomerService->registerCustomerWithCard(
                $payment->order->user,
                $payment->tenant,
                $token,
                $saveAsDefault
            );

            // 登録成功 → card_id で決済するため payment に紐付ける
            $payment->fincode_card_id = $card->fincode_card_id;
            $payment->save();

            Log::info('Card registered before payment', [
                'payment_id' => $payment->id,
                'fincode_card_id' => $card->fincode_card_id,
            ]);
        } catch (CardRegistrationException $e) {
            if ($e->tokenConsumed) {
                Log::error('Card registration failed with token consumed', [
                    'payment_id' => $payment->id,
                    'fincode_error' => $e->fincodeErrorCode,
                ]);

                throw new PaymentFailedException(
                    $payment,
                    $e->fincodeErrorCode,
                    'カード登録に失敗しました。もう一度お試しください。'
                );
            }

            Log::warning('Card registration failed, falling back to token payment', [
                'payment_id' => $payment->id,
                'fincode_error' => $e->fincodeErrorCode,
            ]);
        }
    }

    // 新規カード + save_card指定時のみ決済前にカード登録を実行する
    private function shouldRegisterCardBeforePayment(Payment $payment, ?string $token, bool $saveCard): bool
    {
        return $saveCard
            && $token !== null
            && $payment->fincode_customer_id !== null
            && ! $payment->usesSavedCard();
    }
}
