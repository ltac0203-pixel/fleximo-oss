<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\SalesPeriod;
use App\Enums\TopItemsPeriod;
use Carbon\Carbon;

// テナントダッシュボード用キャッシュキーのビルダー集約。
//
// 各メソッドは TenantDashboardService で本来組み立てられていた文字列を 1:1 で再現する。
// デプロイ時にキーがずれると既存キャッシュが全空振りし、5 分〜1 時間は全テナントの
// ダッシュボードで DB 再集計が走るため、"1 文字違わず保つ" のが最重要不変条件。
class DashboardCacheKeys
{
    public const PREFIX = 'tenant_dashboard';

    public static function summary(int $tenantId, Carbon $date): string
    {
        return self::PREFIX.":{$tenantId}:summary:".$date->format('Y-m-d');
    }

    public static function recentWeek(int $tenantId, Carbon $today): string
    {
        return self::PREFIX.":{$tenantId}:recent_week:".$today->format('Y-m-d');
    }

    public static function sales(int $tenantId, SalesPeriod $period, Carbon $startDate, Carbon $endDate): string
    {
        return self::PREFIX.":{$tenantId}:sales:{$period->value}:"
            .$startDate->format('Y-m-d').':'.$endDate->format('Y-m-d');
    }

    public static function topItems(int $tenantId, TopItemsPeriod $period, int $limit): string
    {
        return self::PREFIX.":{$tenantId}:top_items:{$period->value}:{$limit}";
    }

    public static function hourly(int $tenantId, Carbon $date): string
    {
        return self::PREFIX.":{$tenantId}:hourly:".$date->format('Y-m-d');
    }

    public static function paymentMethods(int $tenantId, Carbon $startDate, Carbon $endDate): string
    {
        return self::PREFIX.":{$tenantId}:payment_methods:"
            .$startDate->format('Y-m-d').':'.$endDate->format('Y-m-d');
    }

    public static function customerInsights(int $tenantId, Carbon $startDate, Carbon $endDate): string
    {
        return self::PREFIX.":{$tenantId}:customer_insights:"
            .$startDate->format('Y-m-d').':'.$endDate->format('Y-m-d');
    }
}
