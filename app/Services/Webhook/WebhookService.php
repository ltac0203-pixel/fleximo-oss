<?php

declare(strict_types=1);

namespace App\Services\Webhook;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\PaymentCompleted;
use App\Events\PaymentFailed;
use App\Exceptions\PaymentNotFoundException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\WebhookLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    private const WEBHOOK_PROVIDER = 'fincode';

    public const SUPPORTED_EVENTS = [
        'payment.completed',
        'payment.failed',
        'payment.refunded',
        'payments.paypay.regis',
        'payments.paypay.exec',
        'payments.paypay.capture',
        'payments.paypay.complete',
    ];

    public function findTenantById(int $tenantId): ?Tenant
    {
        return Tenant::find($tenantId);
    }

    public function findOrCreateLog(
        ?int $tenantId,
        string $eventType,
        array $payload,
        ?string $fincodeId = null
    ): array {
        $attributes = [
            'tenant_id' => $tenantId,
            'provider' => self::WEBHOOK_PROVIDER,
            'fincode_id' => $fincodeId,
            'event_type' => $eventType,
            'payload' => $payload,
        ];

        if ($fincodeId === null) {
            $log = WebhookLog::create($attributes);

            return ['log' => $log, 'is_duplicate' => false];
        }

        $uniqueAttributes = [
            'tenant_id' => $tenantId,
            'provider' => self::WEBHOOK_PROVIDER,
            'event_type' => $eventType,
            'fincode_id' => $fincodeId,
        ];

        try {
            $log = WebhookLog::firstOrCreate(
                $uniqueAttributes,
                $attributes
            );

            return ['log' => $log, 'is_duplicate' => ! $log->wasRecentlyCreated];
        } catch (QueryException $e) {
            $driverErrorCode = (int) ($e->errorInfo[1] ?? 0);
            $isDuplicate = $driverErrorCode === 1062 || str_contains(strtolower($e->getMessage()), 'unique');

            if ($isDuplicate) {
                $log = WebhookLog::where($uniqueAttributes)->first();

                return ['log' => $log, 'is_duplicate' => true];
            }

            throw $e;
        }
    }

    public function isEventSupported(string $eventType): bool
    {
        return in_array($eventType, self::SUPPORTED_EVENTS, true);
    }

    public function processEvent(WebhookLog $webhookLog): void
    {
        $eventType = $webhookLog->event_type;
        $payload = $webhookLog->payload;

        Log::info('Processing webhook event', [
            'webhook_log_id' => $webhookLog->id,
            'event_type' => $eventType,
            'fincode_id' => $webhookLog->fincode_id,
        ]);

        if (! $this->isEventSupported($eventType)) {
            Log::warning('Unsupported webhook event type', ['event_type' => $eventType]);
            $webhookLog->markAsProcessed();

            return;
        }

        match ($eventType) {
            'payment.completed' => $this->handlePaymentCompleted($webhookLog, $payload),
            'payment.failed' => $this->handlePaymentFailed($webhookLog, $payload),
            'payment.refunded' => $this->handlePaymentRefunded($webhookLog, $payload),
            'payments.paypay.regis',
            'payments.paypay.exec',
            'payments.paypay.capture',
            'payments.paypay.complete' => $this->handlePayPayEvent($webhookLog, $payload),
            default => $webhookLog->markAsProcessed(),
        };
    }

    // payment.completed 受信時は Payment と Order を状態遷移させ、注文確定フローを自動化する
    private function handlePaymentCompleted(WebhookLog $webhookLog, array $payload): void
    {
        $payment = $this->resolvePaymentFromPayload($webhookLog, $payload);

        $eventData = DB::transaction(function () use ($payment, $webhookLog) {
            $lockedPayment = Payment::lockForUpdate()->findOrFail($payment->id);
            $shouldFireEvent = false;

            if ($lockedPayment->status !== PaymentStatus::Completed) {
                $lockedPayment->markAsCompleted();
                $shouldFireEvent = true;

                Log::info('Payment marked as completed', [
                    'payment_id' => $lockedPayment->id,
                    'fincode_id' => $lockedPayment->fincode_id,
                ]);
            }

            $order = Order::lockForUpdate()->find($lockedPayment->order_id);
            if ($order) {
                if ($order->status === OrderStatus::PendingPayment) {
                    $order->markAsPaid();
                    Log::info('Order marked as paid', ['order_id' => $order->id]);
                }

                if ($order->status === OrderStatus::Paid) {
                    $order->markAsAccepted();
                    Log::info('Order marked as accepted', ['order_id' => $order->id]);
                }
            }

            $webhookLog->markAsProcessed();

            return $shouldFireEvent && $order ? ['payment' => $lockedPayment, 'order' => $order] : null;
        });

        if ($eventData !== null) {
            event(new PaymentCompleted($eventData['payment'], $eventData['order']));
        }
    }

    // 決済失敗を確定させ、注文ステータスを PAYMENT_FAILED に遷移して再試行・返金対応の出発点とする
    private function handlePaymentFailed(WebhookLog $webhookLog, array $payload): void
    {
        $payment = $this->resolvePaymentFromPayload($webhookLog, $payload);

        $eventData = DB::transaction(function () use ($payment, $webhookLog) {
            $lockedPayment = Payment::lockForUpdate()->findOrFail($payment->id);
            $shouldFireEvent = false;

            if (! $lockedPayment->status->isTerminal()) {
                $lockedPayment->markAsFailed();
                $shouldFireEvent = true;

                Log::info('Payment marked as failed', [
                    'payment_id' => $lockedPayment->id,
                    'fincode_id' => $lockedPayment->fincode_id,
                ]);
            }

            $order = Order::lockForUpdate()->find($lockedPayment->order_id);
            if ($order && $order->status === OrderStatus::PendingPayment) {
                $order->markAsPaymentFailed();
                Log::info('Order marked as payment failed', ['order_id' => $order->id]);
            }

            $webhookLog->markAsProcessed();

            return $shouldFireEvent && $order ? ['payment' => $lockedPayment, 'order' => $order] : null;
        });

        if ($eventData !== null) {
            event(new PaymentFailed($eventData['payment'], $eventData['order']));
        }
    }

    // 返金通知を受け取り、キャンセル済み注文のみを REFUNDED に遷移する（未キャンセルの場合は不正な状態遷移を防止）
    private function handlePaymentRefunded(WebhookLog $webhookLog, array $payload): void
    {
        $payment = $this->resolvePaymentFromPayload($webhookLog, $payload);

        DB::transaction(function () use ($payment, $webhookLog) {
            $order = Order::lockForUpdate()->find($payment->order_id);
            if ($order && $order->status === OrderStatus::Cancelled) {
                $order->markAsRefunded();
                Log::info('Order marked as refunded', ['order_id' => $order->id]);
            }

            $webhookLog->markAsProcessed();
        });
    }

    private function handlePayPayEvent(WebhookLog $webhookLog, array $payload): void
    {
        $status = $payload['status'] ?? null;

        match ($status) {
            'CAPTURED' => $this->handlePaymentCompleted($webhookLog, $payload),
            'CANCELED', 'EXPIRED', 'FAILED' => $this->handlePaymentFailed($webhookLog, $payload),
            default => $this->handlePayPayIntermediateStatus($webhookLog, $status),
        };
    }

    // PayPay の中間ステータスを処理（ログのみ）
    private function handlePayPayIntermediateStatus(WebhookLog $webhookLog, ?string $status): void
    {
        Log::info('PayPay intermediate status received', [
            'webhook_log_id' => $webhookLog->id,
            'status' => $status,
        ]);

        $webhookLog->markAsProcessed();
    }

    // payload.id（正規ID）を優先し、存在しない場合は order_id → webhook_log.fincode_id の順にフォールバックして決済を一意に特定する
    private function resolvePaymentFromPayload(WebhookLog $webhookLog, array $payload): Payment
    {
        $candidates = [
            'id' => $payload['id'] ?? null,
            'order_id' => $payload['order_id'] ?? null,
            'webhook_log_fincode_id' => $webhookLog->fincode_id,
        ];
        $firstNonEmptyCandidate = null;

        foreach ($candidates as $resolvedBy => $candidate) {
            $normalizedId = $this->normalizeFincodeId($candidate);
            if ($normalizedId === null) {
                continue;
            }

            $firstNonEmptyCandidate ??= $normalizedId;

            $payment = $this->paymentLookupQuery($webhookLog)
                ->where('fincode_id', $normalizedId)
                ->first();
            if ($payment === null) {
                continue;
            }

            Log::info('Resolved payment from webhook payload', [
                'webhook_log_id' => $webhookLog->id,
                'tenant_id' => $webhookLog->tenant_id,
                'event_type' => $webhookLog->event_type,
                'resolved_by' => $resolvedBy,
                'resolved_fincode_id' => $normalizedId,
                'payment_id' => $payment->id,
            ]);

            return $payment;
        }

        throw PaymentNotFoundException::forFincodeId($firstNonEmptyCandidate ?? 'unknown');
    }

    /**
     * @return Builder<Payment>
     */
    private function paymentLookupQuery(WebhookLog $webhookLog): Builder
    {
        if ($webhookLog->tenant_id === null) {
            throw new \RuntimeException(
                "Cannot resolve payment without tenant_id (webhook_log_id: {$webhookLog->id})"
            );
        }

        return Payment::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $webhookLog->tenant_id);
    }

    private function normalizeFincodeId(mixed $candidate): ?string
    {
        if (is_string($candidate)) {
            return $candidate !== '' ? $candidate : null;
        }

        if (is_int($candidate)) {
            return (string) $candidate;
        }

        return null;
    }
}
