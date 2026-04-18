<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\PaymentFailedException;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Concerns\HandlesFincodePaymentErrors;
use App\Services\Fincode\FincodeApiException;
use App\Services\Fincode\FincodeClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class PayPayPaymentService
{
    use HandlesFincodePaymentErrors;

    private const CALLBACK_URL_EXPIRY_MINUTES = 30;

    private const ORDER_DESCRIPTION_MAX_LENGTH = 100;

    public function __construct(
        private FincodeClient $fincodeClient
    ) {}

    // PayPay決済を開始する
    public function initiatePayPayPayment(Payment $payment, Order $order, array $options): PaymentInitiationResult
    {
        $tenantShopId = $order->tenant->fincode_shop_id;

        // Step 1: fincodeにPayPay決済枠を作成する（この時点では金額とテナント情報のみ）
        $createResponse = $this->fincodeClient->createPayPayPayment([
            'amount' => $order->total_amount,
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'tenant_shop_id' => $tenantShopId,
            'order_description' => mb_substr($order->tenant->name.' でのご注文', 0, self::ORDER_DESCRIPTION_MAX_LENGTH),
        ]);

        // redirect_urlは常にサーバーサイドで生成し、クライアント入力を使用しない（VULN-PAY-007）
        // 署名付きURLでコールバックの正当性を検証する（VULN-PAY-002）
        $redirectUrl = URL::temporarySignedRoute(
            'order.checkout.callback.paypay',
            now()->addMinutes(self::CALLBACK_URL_EXPIRY_MINUTES),
            ['payment' => $payment->id]
        );

        $executeResponse = $this->fincodeClient->executePayPayPayment($createResponse->id, [
            'access_id' => $createResponse->accessId,
            'redirect_url' => $redirectUrl,
            'tenant_shop_id' => $tenantShopId,
        ]);

        // fincode_id の保存とステータス遷移を原子的に実行する
        DB::transaction(function () use ($payment, $createResponse) {
            $payment->fincode_id = $createResponse->id;
            $payment->save();
            $payment->markAsProcessing();
        });

        Log::info('PayPay payment initiated', [
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'fincode_id' => $createResponse->id,
        ]);

        return PaymentInitiationResult::forPayPay(
            $payment,
            $executeResponse->linkUrl,
            $createResponse->id,
            $createResponse->accessId
        );
    }

    // 決済を確定する（リダイレクト後のコールバック用）
    public function confirm(Payment $payment): bool
    {
        if ($payment->isCompleted() || $payment->isFailed()) {
            return $payment->isCompleted();
        }

        if (! $payment->fincode_id) {
            throw new PaymentFailedException(
                $payment,
                null,
                'fincode決済IDが設定されていません。'
            );
        }

        try {
            $response = $this->fincodeClient->getPayment(
                $payment->fincode_id,
                $payment->tenant->fincode_shop_id,
                $payment->method->toFincodePayType()
            );

            if ($response->isCaptured()) {
                DB::transaction(function () use ($payment) {
                    $payment->markAsCompleted();
                    $payment->order->markAsPaid();
                });

                Log::info('Payment confirmed', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'method' => $payment->method->value,
                ]);

                return true;
            }

            // ユーザーがPayPayアプリで承認操作中の場合、完了でも失敗でもないためfalseを返す
            if ($response->isPendingOrProcessing()) {
                return false;
            }

            // 上記以外のステータスは決済失敗として扱い、注文も失敗状態に遷移する
            DB::transaction(function () use ($payment) {
                $payment->markAsFailed();
                $payment->order->markAsPaymentFailed();
            });

            Log::warning('Payment confirmation failed', [
                'payment_id' => $payment->id,
                'fincode_status' => $response->status,
            ]);

            return false;
        } catch (FincodeApiException $e) {
            throw $this->handleFincodeException($payment, $e, 'Failed to confirm payment', '決済の確認に失敗しました。');
        }
    }
}
