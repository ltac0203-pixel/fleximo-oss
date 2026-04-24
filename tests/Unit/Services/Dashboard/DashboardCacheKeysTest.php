<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Dashboard;

use App\Enums\SalesPeriod;
use App\Services\Dashboard\DashboardCacheKeys;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

// Phase 2 PR2 の最重要 acceptance gate: 7 キー文字列をハードコードで比較する。
// 本番キャッシュキーは 1 文字ずれるとデプロイ直後に全テナントの cache miss が発生する。
class DashboardCacheKeysTest extends TestCase
{
    public function test_summary_key_matches_pre_refactor_format(): void
    {
        $this->assertSame(
            'tenant_dashboard:1:summary:2026-04-24',
            DashboardCacheKeys::summary(1, Carbon::parse('2026-04-24'))
        );
    }

    public function test_recent_week_key_matches_pre_refactor_format(): void
    {
        $this->assertSame(
            'tenant_dashboard:42:recent_week:2026-04-24',
            DashboardCacheKeys::recentWeek(42, Carbon::parse('2026-04-24'))
        );
    }

    public function test_sales_key_matches_pre_refactor_format(): void
    {
        $this->assertSame(
            'tenant_dashboard:7:sales:daily:2026-04-18:2026-04-24',
            DashboardCacheKeys::sales(7, SalesPeriod::Daily, Carbon::parse('2026-04-18'), Carbon::parse('2026-04-24'))
        );
        $this->assertSame(
            'tenant_dashboard:7:sales:weekly:2026-04-01:2026-04-30',
            DashboardCacheKeys::sales(7, SalesPeriod::Weekly, Carbon::parse('2026-04-01'), Carbon::parse('2026-04-30'))
        );
        $this->assertSame(
            'tenant_dashboard:7:sales:monthly:2026-01-01:2026-12-31',
            DashboardCacheKeys::sales(7, SalesPeriod::Monthly, Carbon::parse('2026-01-01'), Carbon::parse('2026-12-31'))
        );
    }

    public function test_top_items_key_matches_pre_refactor_format(): void
    {
        $this->assertSame(
            'tenant_dashboard:3:top_items:week:10',
            DashboardCacheKeys::topItems(3, 'week', 10)
        );
        $this->assertSame(
            'tenant_dashboard:3:top_items:month:5',
            DashboardCacheKeys::topItems(3, 'month', 5)
        );
    }

    public function test_hourly_key_matches_pre_refactor_format(): void
    {
        $this->assertSame(
            'tenant_dashboard:9:hourly:2026-04-24',
            DashboardCacheKeys::hourly(9, Carbon::parse('2026-04-24'))
        );
    }

    public function test_payment_methods_key_matches_pre_refactor_format(): void
    {
        $this->assertSame(
            'tenant_dashboard:4:payment_methods:2026-04-01:2026-04-24',
            DashboardCacheKeys::paymentMethods(4, Carbon::parse('2026-04-01'), Carbon::parse('2026-04-24'))
        );
    }

    public function test_customer_insights_key_matches_pre_refactor_format(): void
    {
        $this->assertSame(
            'tenant_dashboard:5:customer_insights:2026-04-01:2026-04-24',
            DashboardCacheKeys::customerInsights(5, Carbon::parse('2026-04-01'), Carbon::parse('2026-04-24'))
        );
    }

    public function test_prefix_constant_is_unchanged(): void
    {
        // PREFIX を定数として公開しているため、外部から想定外に書き換えられていないことを明示検証する
        $this->assertSame('tenant_dashboard', DashboardCacheKeys::PREFIX);
    }
}
