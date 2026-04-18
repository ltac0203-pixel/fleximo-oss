<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

// 日次分析データを集計するジョブ
class AggregateDailyAnalyticsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // 最大試行回数
    public int $tries = 3;

    // リトライ間隔（秒）: 1分 → 5分 → 15分
    public array $backoff = [60, 300, 900];

    // 子ジョブは単一テナント集計（85秒）、親ジョブは全テナント分のディスパッチに時間がかかる（300秒）
    public int $timeout;

    public function __construct(
        public readonly Carbon $date,
        public readonly ?int $tenantId = null
    ) {
        $this->timeout = $tenantId !== null ? 85 : 300;
    }

    // ジョブを実行
    public function handle(AnalyticsService $analyticsService): void
    {
        Log::info('AggregateDailyAnalyticsJob started', [
            'date' => $this->date->toDateString(),
            'tenant_id' => $this->tenantId,
            'attempt' => $this->attempts(),
        ]);

        try {
            $dispatchedTenantJobs = null;

            if ($this->tenantId !== null) {
                // 手動再集計やデータ修正時に全テナント処理を避け、対象テナントだけ効率的に更新する
                $analyticsService->aggregateTenantDailyAnalytics($this->tenantId, $this->date);
            } else {
                // 親ジョブではプラットフォーム集計のみ保存し、テナントごとの集計は子ジョブに分散する
                $analyticsService->savePlatformDailySales($this->date);
                $dispatchedTenantJobs = $analyticsService->dispatchAllTenantsDailyAnalytics($this->date);
            }

            Log::info('AggregateDailyAnalyticsJob completed successfully', [
                'date' => $this->date->toDateString(),
                'tenant_id' => $this->tenantId,
                'dispatched_tenant_jobs' => $dispatchedTenantJobs,
            ]);
        } catch (Throwable $e) {
            Log::error('AggregateDailyAnalyticsJob failed', [
                'date' => $this->date->toDateString(),
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    // ジョブが失敗した場合
    public function failed(?Throwable $exception): void
    {
        Log::error('AggregateDailyAnalyticsJob failed after all retries', [
            'date' => $this->date->toDateString(),
            'tenant_id' => $this->tenantId,
            'error' => $exception?->getMessage() ?? 'Unknown error',
            'attempts' => $this->attempts(),
        ]);
    }

    // 一意のジョブ ID
    public function uniqueId(): string
    {
        $tenantPart = $this->tenantId !== null ? "tenant_{$this->tenantId}" : 'all';

        return "daily_analytics_{$this->date->toDateString()}_{$tenantPart}";
    }

    // ジョブのタグ
    public function tags(): array
    {
        $tags = [
            'analytics',
            'daily',
            'date:'.$this->date->toDateString(),
        ];

        if ($this->tenantId !== null) {
            $tags[] = 'tenant:'.$this->tenantId;
        } else {
            $tags[] = 'platform';
        }

        return $tags;
    }
}
