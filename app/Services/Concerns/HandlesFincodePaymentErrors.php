<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use App\Enums\OrderStatus;
use App\Events\PaymentCompleted;
use App\Events\PaymentFailed;
use App\Exceptions\PaymentFailedException;
use App\Models\Payment;
use App\Models\Scopes\TenantScope;
use App\Services\Fincode\FincodeApiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

trait HandlesFincodePaymentErrors
{
    // FincodeApiExceptionをログ出力してPaymentFailedExceptionに変換する（決済状態は変更しない）
    private function logAndConvertFincodeException(
        Payment $payment,
        FincodeApiException $e,
        string $logMessage,
        string $userMessage
    ): PaymentFailedException {
        Log::error($logMessage, [
            'payment_id' => $payment->id,
            'fincode_error' => $e->errorCode,
        ]);

        return new PaymentFailedException(
            $payment,
            $e->errorCode,
            $userMessage
        );
    }

    // FincodeApiExceptionを処理し、決済を失敗にしてPaymentFailedExceptionを返す
    private function handleFincodeException(Payment $payment, FincodeApiException $e, string $logMessage, string $userMessage): PaymentFailedException
    {
        $this->markPaymentAndOrderAsFailed($payment, $logMessage);

        Log::error($logMessage, [
            'payment_id' => $payment->id,
            'fincode_error' => $e->errorCode,
        ]);

        return new PaymentFailedException(
            $payment,
            $e->errorCode,
            $userMessage
        );
    }

    // 決済失敗時に Payment/Order を確実に失敗へ寄せる（失敗時は可能な範囲でフォールバック）
    private function markPaymentAndOrderAsFailed(Payment $payment, string $reason): void
    {
        try {
            DB::transaction(function () use ($payment) {
                $payment->markAsFailed();
                $order = $payment->order()->withoutGlobalScope(TenantScope::class)->first();
                if ($order !== null && $order->status === OrderStatus::PendingPayment) {
                    $order->markAsPaymentFailed();
                }
            });

            return;
        } catch (\Throwable $e) {
            Log::error('Failed to mark payment and order as failed', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'reason' => $reason,
                'error_class' => $e::class,
                'error_message' => $e->getMessage(),
            ]);
        }

        $payment->refresh();
        if (! $payment->status->isTerminal()) {
            try {
                $payment->markAsFailed();
            } catch (\Throwable $e) {
                Log::error('Failed to mark payment as failed in fallback', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'reason' => $reason,
                    'error_class' => $e::class,
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        $order = $payment->order()->withoutGlobalScope(TenantScope::class)->first();
        if ($order !== null && $order->status === OrderStatus::PendingPayment) {
            try {
                $order->markAsPaymentFailed();
            } catch (\Throwable $e) {
                Log::error('Failed to mark order as payment failed in fallback', [
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'reason' => $reason,
                    'error_class' => $e::class,
                    'error_message' => $e->getMessage(),
                ]);
            }
        }
    }

    // 決済を完了し、注文を支払済みにする
    private function completePaymentWithOrder(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->markAsCompleted();
            $payment->order->markAsPaid();
        });

        event(new PaymentCompleted($payment, $payment->order));
    }

    // 決済を失敗にし、注文を決済失敗にする
    private function failPaymentWithOrder(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->markAsFailed();
            $payment->order->markAsPaymentFailed();
        });

        event(new PaymentFailed($payment, $payment->order));
    }

    // 3DSコールバックURLを一時署名付きで生成する
    private function buildThreeDsCallbackUrl(Payment $payment): string
    {
        $ttlMinutes = (int) config('fincode.three_ds_callback_ttl_minutes', 5);

        return URL::temporarySignedRoute(
            'order.checkout.callback.3ds',
            now()->addMinutes($ttlMinutes),
            ['payment' => $payment->id]
        );
    }
}
