<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Dashboard;

use App\Enums\SalesPeriod;
use App\Services\Dashboard\SalesDataFormatter;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class SalesDataFormatterTest extends TestCase
{
    private SalesDataFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new SalesDataFormatter;
    }

    public function test_format_daily_zero_fills_missing_days(): void
    {
        $startDate = Carbon::parse('2026-01-05');
        $endDate = Carbon::parse('2026-01-08');
        $dailyStats = [
            '2026-01-05' => ['total_sales' => 100, 'order_count' => 1],
            '2026-01-08' => ['total_sales' => 300, 'order_count' => 3],
        ];

        $result = $this->formatter->format(SalesPeriod::Daily, $startDate, $endDate, $dailyStats);

        $this->assertSame(
            [
                ['date' => '2026-01-05', 'total_sales' => 100, 'order_count' => 1],
                ['date' => '2026-01-06', 'total_sales' => 0, 'order_count' => 0],
                ['date' => '2026-01-07', 'total_sales' => 0, 'order_count' => 0],
                ['date' => '2026-01-08', 'total_sales' => 300, 'order_count' => 3],
            ],
            $result
        );
    }

    public function test_format_daily_maps_existing_stats(): void
    {
        $startDate = Carbon::parse('2026-02-01');
        $endDate = Carbon::parse('2026-02-03');
        $dailyStats = [
            '2026-02-01' => ['total_sales' => 500, 'order_count' => 5],
            '2026-02-02' => ['total_sales' => 600, 'order_count' => 6],
            '2026-02-03' => ['total_sales' => 700, 'order_count' => 7],
        ];

        $result = $this->formatter->format(SalesPeriod::Daily, $startDate, $endDate, $dailyStats);

        $this->assertSame(
            [
                ['date' => '2026-02-01', 'total_sales' => 500, 'order_count' => 5],
                ['date' => '2026-02-02', 'total_sales' => 600, 'order_count' => 6],
                ['date' => '2026-02-03', 'total_sales' => 700, 'order_count' => 7],
            ],
            $result
        );
    }

    public function test_format_weekly_aggregates_into_week_buckets(): void
    {
        // 2026-01-05 is Monday (start of week)
        $startDate = Carbon::parse('2026-01-05');
        $endDate = Carbon::parse('2026-01-18');
        $dailyStats = [
            '2026-01-05' => ['total_sales' => 100, 'order_count' => 1],
            '2026-01-11' => ['total_sales' => 200, 'order_count' => 2],
            '2026-01-12' => ['total_sales' => 300, 'order_count' => 3],
            '2026-01-18' => ['total_sales' => 400, 'order_count' => 4],
        ];

        $result = $this->formatter->format(SalesPeriod::Weekly, $startDate, $endDate, $dailyStats);

        $this->assertSame(
            [
                ['date' => '2026-01-05', 'total_sales' => 300, 'order_count' => 3],
                ['date' => '2026-01-12', 'total_sales' => 700, 'order_count' => 7],
            ],
            $result
        );
    }

    public function test_format_weekly_clamps_final_bucket_to_range_end(): void
    {
        // Start on Monday, end mid-week so final bucket is clamped
        $startDate = Carbon::parse('2026-01-05');
        $endDate = Carbon::parse('2026-01-14'); // Wednesday

        $dailyStats = [
            '2026-01-05' => ['total_sales' => 100, 'order_count' => 1],
            '2026-01-12' => ['total_sales' => 200, 'order_count' => 2],
            '2026-01-14' => ['total_sales' => 300, 'order_count' => 3],
            // 2026-01-15 (Thursday) should NOT be included due to clamping
            '2026-01-15' => ['total_sales' => 9999, 'order_count' => 99],
        ];

        $result = $this->formatter->format(SalesPeriod::Weekly, $startDate, $endDate, $dailyStats);

        $this->assertSame(
            [
                ['date' => '2026-01-05', 'total_sales' => 100, 'order_count' => 1],
                ['date' => '2026-01-12', 'total_sales' => 500, 'order_count' => 5],
            ],
            $result
        );
    }

    public function test_format_monthly_aggregates_into_month_buckets(): void
    {
        $startDate = Carbon::parse('2026-03-01');
        $endDate = Carbon::parse('2026-05-08');
        $dailyStats = [
            '2026-03-01' => ['total_sales' => 120, 'order_count' => 1],
            '2026-03-31' => ['total_sales' => 180, 'order_count' => 2],
            '2026-04-15' => ['total_sales' => 400, 'order_count' => 4],
            '2026-05-01' => ['total_sales' => 200, 'order_count' => 2],
            '2026-05-08' => ['total_sales' => 300, 'order_count' => 3],
        ];

        $result = $this->formatter->format(SalesPeriod::Monthly, $startDate, $endDate, $dailyStats);

        $this->assertSame(
            [
                ['date' => '2026-03', 'total_sales' => 300, 'order_count' => 3],
                ['date' => '2026-04', 'total_sales' => 400, 'order_count' => 4],
                ['date' => '2026-05', 'total_sales' => 500, 'order_count' => 5],
            ],
            $result
        );
    }

    public function test_format_with_empty_stats_returns_zero_filled(): void
    {
        $startDate = Carbon::parse('2026-01-01');
        $endDate = Carbon::parse('2026-01-03');

        $result = $this->formatter->format(SalesPeriod::Daily, $startDate, $endDate, []);

        $this->assertSame(
            [
                ['date' => '2026-01-01', 'total_sales' => 0, 'order_count' => 0],
                ['date' => '2026-01-02', 'total_sales' => 0, 'order_count' => 0],
                ['date' => '2026-01-03', 'total_sales' => 0, 'order_count' => 0],
            ],
            $result
        );
    }
}
