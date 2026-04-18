<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentFailedException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Scopes\TenantScope;
use App\Services\Concerns\HandlesFincodePaymentErrors;
use App\Services\Fincode\FincodeApiException;
use App\Services\Fincode\FincodeClient;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    use HandlesFincodePaymentErrors;

    public function __construct(
        private FincodeClient $fincodeClient,
        private CardPaymentService $cardPaymentService,
        private PayPayPaymentService $payPayPaymentService
    ) {}

    // 決済を開始する
    public function initiate(Order $order, PaymentMethod $method, array $options = []): PaymentInitiationResult
    {
        // 外部API呼び出し前にローカル決済を確保し、order.payment_idを確定する
        $payment = DB::transaction(function () use ($order, $method) {
            // 同一注文の同時決済開始を防ぐため、注文行をロックして直列化する
            $lockedOrder = Order::withoutGlobalScope(TenantScope::class)
                ->lockForUpdate()
                ->findOrFail($order->id);

            // 同時リクエストで二重決済が作られることを防ぐため、排他ロックで既存決済を確認する
            $existingPayment = Payment::where('order_id', $lockedOrder->id)
                ->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Processing])
                ->lockForUpdate()
                ->first();

            if ($existingPayment) {
                throw new PaymentFailedException(
                    $existingPayment,
                    null,
                    'この注文には既に処理中の決済が存在します。'
                );
            }

            // 外部決済APIを呼ぶ前にローカルレコードを作成し、障害時にも状態を追跡可能にする
            // status, amount はMass Assignment攻撃を防止するため直接属性代入で設定する
            $payment = new Payment([
                'order_id' => $lockedOrder->id,
                'tenant_id' => $lockedOrder->tenant_id,
                'provider' => 'fincode',
                'method' => $method,
            ]);
            $payment->status = PaymentStatus::Pending;
            $payment->amount = $lockedOrder->total_amount;
            $payment->save();

            // 注文と決済を即時に紐付け、後続API失敗時でも追跡可能にする
            $lockedOrder->update(['payment_id' => $payment->id]);

            return $payment;
        });

        try {
            return match ($method) {
                PaymentMethod::Card => $this->cardPaymentService->initiateCardPayment($payment, $order, $options['card_id'] ?? null),
                PaymentMethod::PayPay => $this->payPayPaymentService->initiatePayPayPayment($payment, $order, $options),
            };
        } catch (FincodeApiException $e) {
            throw $this->handleFincodeException(
                $payment,
                $e,
                'Failed to initiate payment',
                '決済の開始に失敗しました。'
            );
        }
    }

    // 決済状態を確認する
    public function checkStatus(Payment $payment): PaymentStatus
    {
        if (! $payment->fincode_id) {
            return $payment->status;
        }

        try {
            $response = $this->fincodeClient->getPayment(
                $payment->fincode_id,
                $payment->tenant->fincode_shop_id,
                $payment->method->toFincodePayType()
            );

            return $response->toPaymentStatus();
        } catch (FincodeApiException $e) {
            throw $this->logAndConvertFincodeException(
                $payment,
                $e,
                'Failed to check payment status',
                '決済状態の確認に失敗しました。'
            );
        }
    }
}
