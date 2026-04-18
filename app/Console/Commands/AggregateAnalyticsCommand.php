<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AggregateDailyAnalyticsJob;
use App\Jobs\AggregateMonthlyAnalyticsJob;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AggregateAnalyticsCommand extends Command
{
    protected $signature = 'analytics:aggregate
                            {--date= : 集計対象日（YYYY-MM-DD形式、デフォルトは前日）}
                            {--tenant= : 特定テナントのみ集計}
                            {--monthly : 月次集計も実行}
                            {--sync : 同期実行（キュー使用しない）}';

    protected $description = '分析データを集計する';

    public function handle(AnalyticsService $analyticsService): int
    {
        $dateOption = $this->option('date');
        $tenantId = $this->option('tenant') ? (int) $this->option('tenant') : null;
        $includeMonthly = (bool) $this->option('monthly');
        $sync = (bool) $this->option('sync');

        // オプション未指定時は前日を対象にする（当日は営業中でデータが不完全なため）
        $date = $dateOption
            ? Carbon::parse($dateOption)
            : Carbon::yesterday();

        $this->info("集計開始: {$date->toDateString()}");

        if ($tenantId !== null) {
            $this->info("テナント ID: {$tenantId}");
        } else {
            $this->info('全テナント + プラットフォーム全体を集計');
        }

        // syncモードは手動実行やデバッグ用、通常運用ではキュー経由で負荷を分散する
        if ($sync) {
            $this->info('同期モードで日次集計を実行中...');

            if ($tenantId !== null) {
                $analyticsService->aggregateTenantDailyAnalytics($tenantId, $date);
            } else {
                $analyticsService->aggregateAllTenantsDailyAnalytics($date);
            }

            $this->info('日次集計が完了しました');
        } else {
            $this->info('日次集計ジョブをキューに追加...');
            AggregateDailyAnalyticsJob::dispatch($date, $tenantId);
            $this->info('日次集計ジョブがキューに追加されました');
        }

        // 月次集計は処理コストが高いため、明示的に--monthlyが指定された場合のみ実行する
        if ($includeMonthly) {
            $year = $date->year;
            $month = $date->month;

            if ($sync) {
                $this->info("同期モードで月次集計を実行中... ({$year}-{$month})");

                if ($tenantId !== null) {
                    $analyticsService->aggregateTenantMonthlyAnalytics($tenantId, $year, $month);
                } else {
                    $analyticsService->aggregateAllTenantsMonthlyAnalytics($year, $month);
                }

                $this->info('月次集計が完了しました');
            } else {
                $this->info("月次集計ジョブをキューに追加... ({$year}-{$month})");
                AggregateMonthlyAnalyticsJob::dispatch($year, $month, $tenantId);
                $this->info('月次集計ジョブがキューに追加されました');
            }
        }

        $this->info('完了');

        return Command::SUCCESS;
    }
}
