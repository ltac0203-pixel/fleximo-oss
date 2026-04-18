<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPaymentWebhookJob;
use App\Services\Webhook\WebhookService;
use App\Services\Webhook\WebhookSignatureVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FincodeWebhookController extends Controller
{
    public function __construct(
        private readonly WebhookService $webhookService,
        private readonly WebhookSignatureVerifier $verifier,
    ) {}

    public function handle(Request $request, int $tenantId): JsonResponse
    {
        Log::info('Received fincode webhook', [
            'tenant_id' => $tenantId,
            'ip' => $request->ip(),
        ]);

        if (! $this->verifier->hasSecret()) {
            Log::error('Webhook secret is not configured');

            return $this->uniformAcknowledgedResponse();
        }

        if (! $this->verifier->verify($request)) {
            Log::warning('Webhook signature verification failed', [
                'tenant_id' => $tenantId,
            ]);

            return $this->uniformAcknowledgedResponse();
        }

        // タイムスタンプ検証（リプレイ攻撃防止）
        $tolerance = (int) config('fincode.webhook_timestamp_tolerance', 300);
        if (! $this->verifier->verifyTimestamp($request->getContent(), $tolerance)) {
            Log::warning('Webhook timestamp verification failed', [
                'tenant_id' => $tenantId,
            ]);

            return $this->uniformAcknowledgedResponse();
        }

        // テナント検証（存在しなくても応答差分を出さない）
        $tenant = $this->webhookService->findTenantById($tenantId);
        if (! $tenant) {
            Log::warning('Webhook received for unknown tenant', ['tenant_id' => $tenantId]);

            return $this->uniformAcknowledgedResponse();
        }

        $payload = (array) json_decode($request->getContent(), true);

        // テナントのfincode_shop_idとpayloadのshop_idの整合性を検証（VULN-PAY-008）
        // 片側が null でもスキップしない: 未連携テナントへの webhook は不正前提として無声にリジェクトする
        if ($tenant->fincode_shop_id === null) {
            Log::warning('Webhook received for tenant without fincode_shop_id', [
                'tenant_id' => $tenantId,
            ]);

            return $this->uniformAcknowledgedResponse();
        }

        $payloadShopId = $payload['shop_id'] ?? null;
        if ($payloadShopId !== $tenant->fincode_shop_id) {
            Log::warning('Webhook shop_id mismatch', [
                'tenant_id' => $tenantId,
                'payload_shop_id' => $payloadShopId,
            ]);

            return $this->uniformAcknowledgedResponse();
        }

        $eventType = $payload['event'] ?? 'unknown';
        $fincodeId = $this->resolveFincodeId($payload);

        Log::info('Processing webhook payload', [
            'tenant_id' => $tenantId,
            'event_type' => $eventType,
            'fincode_id' => $fincodeId,
        ]);

        // アトミックな冪等性チェック + ログ作成
        $result = $this->webhookService->findOrCreateLog(
            $tenantId,
            $eventType,
            $payload,
            $fincodeId
        );

        if ($result['is_duplicate']) {
            Log::info('Duplicate webhook ignored', ['fincode_id' => $fincodeId]);

            return $this->uniformAcknowledgedResponse();
        }

        $webhookLog = $result['log'];

        ProcessPaymentWebhookJob::dispatch($webhookLog);

        Log::info('Webhook queued for processing', [
            'webhook_log_id' => $webhookLog->id,
        ]);

        return response()->json(['status' => 'accepted']);
    }

    // 公開Webhookのため、失敗理由による応答差分を出さない。
    private function uniformAcknowledgedResponse(): JsonResponse
    {
        return response()->json(['status' => 'accepted']);
    }

    // Webhook payload から fincode_id を解決する。
    // id を最優先し、存在しない場合のみ order_id をフォールバックとして利用する。
    private function resolveFincodeId(array $payload): ?string
    {
        foreach ([$payload['id'] ?? null, $payload['order_id'] ?? null] as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }

            if (is_int($candidate)) {
                return (string) $candidate;
            }
        }

        return null;
    }
}
