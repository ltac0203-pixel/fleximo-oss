<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\AnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

// 月次分析データを集計するジョブ
class AggregateMonthlyAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // 最大試行回数
    public int $tries = 3;

    // リトライ間隔（秒）: 1分 → 5分 → 15分
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly int $year,
        public readonly int $month,
        public readonly ?int $tenantId = null
    ) {}

    // ジョブを実行
    public function handle(AnalyticsService $analyticsService): void
    {
        Log::info('AggregateMonthlyAnalyticsJob started', [
            'year' => $this->year,
            'month' => $this->month,
            'tenant_id' => $this->tenantId,
            'attempt' => $this->attempts(),
        ]);

        try {
            if ($this->tenantId !== null) {
                // 手動再集計やデータ修正時に全テナント処理を避け、対象テナントだけ効率的に更新する
                $analyticsService->aggregateTenantMonthlyAnalytics($this->tenantId, $this->year, $this->month);
            } else {
                // 定期バッチ実行時はプラットフォーム全体の月次レポートを一括生成する
                $analyticsService->aggregateAllTenantsMonthlyAnalytics($this->year, $this->month);
            }

            Log::info('AggregateMonthlyAnalyticsJob completed successfully', [
                'year' => $this->year,
                'month' => $this->month,
                'tenant_id' => $this->tenantId,
            ]);
        } catch (Throwable $e) {
            Log::error('AggregateMonthlyAnalyticsJob failed', [
                'year' => $this->year,
                'month' => $this->month,
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
        Log::error('AggregateMonthlyAnalyticsJob failed after all retries', [
            'year' => $this->year,
            'month' => $this->month,
            'tenant_id' => $this->tenantId,
            'error' => $exception?->getMessage() ?? 'Unknown error',
            'attempts' => $this->attempts(),
        ]);
    }

    // 一意のジョブ ID
    public function uniqueId(): string
    {
        $tenantPart = $this->tenantId !== null ? "tenant_{$this->tenantId}" : 'all';
        $monthStr = sprintf('%04d-%02d', $this->year, $this->month);

        return "monthly_analytics_{$monthStr}_{$tenantPart}";
    }

    // ジョブのタグ
    public function tags(): array
    {
        $tags = [
            'analytics',
            'monthly',
            'year:'.$this->year,
            'month:'.$this->month,
        ];

        if ($this->tenantId !== null) {
            $tags[] = 'tenant:'.$this->tenantId;
        } else {
            $tags[] = 'platform';
        }

        return $tags;
    }
}
