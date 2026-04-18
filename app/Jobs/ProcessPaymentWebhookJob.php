<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\PaymentNotFoundException;
use App\Models\WebhookLog;
use App\Services\Webhook\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

// 決済 Webhook を処理するジョブ
class ProcessPaymentWebhookJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // 最大試行回数
    public int $tries = 3;

    // ユニークロック保持時間（秒）
    public int $uniqueFor = 300;

    // リトライ間隔（秒）: 1分 → 5分 → 15分
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly WebhookLog $webhookLog
    ) {}

    // ジョブを実行
    public function handle(WebhookService $webhookService): void
    {
        Log::info('ProcessPaymentWebhookJob started', [
            'webhook_log_id' => $this->webhookLog->id,
            'event_type' => $this->webhookLog->event_type,
            'attempt' => $this->attempts(),
        ]);

        try {
            $webhookService->processEvent($this->webhookLog);

            Log::info('ProcessPaymentWebhookJob completed successfully', [
                'webhook_log_id' => $this->webhookLog->id,
            ]);
        } catch (PaymentNotFoundException $e) {
            // 存在しない決済へのリトライは無意味なため、即座に失敗確定させてキューリソースを解放する
            Log::warning('Payment not found for webhook', [
                'webhook_log_id' => $this->webhookLog->id,
                'error' => $e->getMessage(),
            ]);

            $this->webhookLog->markAsFailed($e->getMessage());
            $this->fail($e);
        } catch (\InvalidArgumentException $e) {
            // 状態遷移の失敗は決定論的エラーのため、リトライしても解消しない
            Log::warning('Invalid state transition for webhook', [
                'webhook_log_id' => $this->webhookLog->id,
                'error' => $e->getMessage(),
            ]);

            $this->webhookLog->markAsFailed($e->getMessage());
            $this->fail($e);
        } catch (QueryException $e) {
            // DB エラーはリトライ対象（デッドロック、接続断など一時的障害の可能性）
            Log::warning('DB error in webhook processing', [
                'webhook_log_id' => $this->webhookLog->id,
                'event_type' => $this->webhookLog->event_type,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ジョブが失敗した場合
    public function failed(?Throwable $exception): void
    {
        $errorMessage = $exception?->getMessage() ?? 'Unknown error';

        Log::error('ProcessPaymentWebhookJob failed after all retries', [
            'webhook_log_id' => $this->webhookLog->id,
            'event_type' => $this->webhookLog->event_type,
            'fincode_id' => $this->webhookLog->fincode_id,
            'error' => $errorMessage,
            'attempts' => $this->attempts(),
        ]);

        // 障害調査時にWebhookLogから直接エラー原因を追跡できるようにする
        $this->webhookLog->markAsFailed($errorMessage);
    }

    // 一意のジョブ ID
    public function uniqueId(): string
    {
        return 'webhook_'.$this->webhookLog->id;
    }

    // ジョブのタグ
    public function tags(): array
    {
        return [
            'webhook',
            'payment',
            'webhook_log:'.$this->webhookLog->id,
            'tenant:'.$this->webhookLog->tenant_id,
        ];
    }
}
