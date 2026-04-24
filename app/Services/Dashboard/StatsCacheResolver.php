<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Cache;

// テナントダッシュボード統計キャッシュの TTL 戦略を集約する。
//
// TenantDashboardService で従来インラインに散在していた Cache::remember 呼び出しと
// ttlForDate / ttlForDateRange 判定を 1 箇所に閉じ込める。呼び出し側は TTL の実値を意識せず、
// 「単日向け」「期間向け」「常時リアルタイム」の 3 戦略を選ぶだけで適切な TTL が適用される。
class StatsCacheResolver
{
    public const TTL_REALTIME = 300;    // 5 分 - 当日データ相当

    public const TTL_HISTORICAL = 3600; // 1 時間 - 過去データ相当

    // リクエスト単位で当日を凍結する。TenantStatsRepository と同方針で、
    // リクエスト処理中に日付が跨いでも TTL 判定がブレないことを担保する。
    private readonly Carbon $resolvedToday;

    public function __construct()
    {
        $this->resolvedToday = Carbon::today();
    }

    // 単日向け: 指定 date が今日なら REALTIME、それ以外は HISTORICAL。
    public function rememberForDate(string $key, Carbon $date, Closure $loader): mixed
    {
        return Cache::remember($key, $this->ttlForDate($date), $loader);
    }

    // 期間向け: 期間が今日を含むなら REALTIME、それ以外は HISTORICAL。
    public function rememberForDateRange(string $key, Carbon $startDate, Carbon $endDate, Closure $loader): mixed
    {
        return Cache::remember($key, $this->ttlForDateRange($startDate, $endDate), $loader);
    }

    // 常時 REALTIME: recent_week / top_items など、当日依存の指標向け。
    public function rememberRealtime(string $key, Closure $loader): mixed
    {
        return Cache::remember($key, self::TTL_REALTIME, $loader);
    }

    private function ttlForDate(Carbon $date): int
    {
        return $date->isSameDay($this->resolvedToday) ? self::TTL_REALTIME : self::TTL_HISTORICAL;
    }

    private function ttlForDateRange(Carbon $startDate, Carbon $endDate): int
    {
        return $this->resolvedToday->between($startDate, $endDate) ? self::TTL_REALTIME : self::TTL_HISTORICAL;
    }
}
