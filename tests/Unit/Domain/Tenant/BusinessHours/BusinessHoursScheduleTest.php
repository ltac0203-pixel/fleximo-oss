<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tenant\BusinessHours;

use App\Domain\Tenant\BusinessHours\BusinessHoursSchedule;
use App\Models\TenantBusinessHour;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class BusinessHoursScheduleTest extends TestCase
{
    /**
     * @param  array<int, array{weekday: int, open_time: string, close_time: string, sort_order?: int}>  $rows
     */
    private function scheduleFromRows(array $rows): BusinessHoursSchedule
    {
        $hours = Collection::make($rows)->map(
            fn (array $row): TenantBusinessHour => new TenantBusinessHour([
                'weekday' => $row['weekday'],
                'open_time' => $row['open_time'],
                'close_time' => $row['close_time'],
                'sort_order' => $row['sort_order'] ?? 0,
            ]),
        );

        return new BusinessHoursSchedule($hours);
    }

    public function test_is_open_at_returns_true_during_business_hours(): void
    {
        // 2024-01-01 は月曜日（weekday=1）
        $schedule = $this->scheduleFromRows([
            ['weekday' => 1, 'open_time' => '09:00', 'close_time' => '21:00'],
        ]);

        $this->assertTrue($schedule->isOpenAt(Carbon::parse('2024-01-01 15:00:00')));
    }

    public function test_is_open_at_returns_false_before_opening(): void
    {
        $schedule = $this->scheduleFromRows([
            ['weekday' => 1, 'open_time' => '09:00', 'close_time' => '21:00'],
        ]);

        $this->assertFalse($schedule->isOpenAt(Carbon::parse('2024-01-01 08:00:00')));
    }

    public function test_is_open_at_returns_false_after_closing(): void
    {
        $schedule = $this->scheduleFromRows([
            ['weekday' => 1, 'open_time' => '09:00', 'close_time' => '21:00'],
        ]);

        $this->assertFalse($schedule->isOpenAt(Carbon::parse('2024-01-01 22:00:00')));
    }

    public function test_is_open_at_handles_overnight_hours(): void
    {
        // 2024-01-01 は月曜日（weekday=1）, 2024-01-02 is Tuesday (weekday=2)
        $schedule = $this->scheduleFromRows([
            ['weekday' => 1, 'open_time' => '18:00', 'close_time' => '02:00'],
        ]);

        $this->assertTrue($schedule->isOpenAt(Carbon::parse('2024-01-01 20:00:00')));
        $this->assertTrue($schedule->isOpenAt(Carbon::parse('2024-01-02 01:00:00')));
        $this->assertFalse($schedule->isOpenAt(Carbon::parse('2024-01-01 10:00:00')));
    }

    public function test_is_open_at_returns_false_when_no_business_hours_set(): void
    {
        $schedule = $this->scheduleFromRows([]);

        $this->assertFalse($schedule->isOpenAt(Carbon::parse('2024-01-01 03:00:00')));
    }

    public function test_overnight_saturday_to_sunday_at_0100(): void
    {
        $schedule = $this->scheduleFromRows([
            ['weekday' => 6, 'open_time' => '22:00', 'close_time' => '02:00'],
        ]);

        // 2024-01-07 は日曜(0)、前日 2024-01-06 は土曜(6)
        $this->assertTrue($schedule->isOpenAt(Carbon::create(2024, 1, 7, 1, 0, 0)));
    }

    public function test_overnight_close_boundary(): void
    {
        $schedule = $this->scheduleFromRows([
            ['weekday' => 6, 'open_time' => '22:00', 'close_time' => '02:00'],
        ]);

        // 日曜 02:00 ちょうど閉店: $currentTime < $closeTime → 02:00 < 02:00 は false
        $this->assertFalse($schedule->isOpenAt(Carbon::create(2024, 1, 7, 2, 0, 0)));
    }

    public function test_overnight_does_not_apply_to_wrong_day(): void
    {
        $schedule = $this->scheduleFromRows([
            ['weekday' => 1, 'open_time' => '22:00', 'close_time' => '02:00'],
        ]);

        // 水曜(3) 01:00 → 前日は火曜(2)、月曜(1)の設定は無関係
        $this->assertFalse($schedule->isOpenAt(Carbon::create(2024, 1, 10, 1, 0, 0)));
    }

    public function test_midnight_with_overnight_hours(): void
    {
        $schedule = $this->scheduleFromRows([
            ['weekday' => 6, 'open_time' => '22:00', 'close_time' => '02:00'],
        ]);

        // 日曜 00:00 → 前日の土曜設定が適用され営業中
        $this->assertTrue($schedule->isOpenAt(Carbon::create(2024, 1, 7, 0, 0, 0)));
    }

    public function test_close_at_midnight_behavior(): void
    {
        // 18:00-00:00: isOvernight → 18:00 > 00:00 なので true
        $schedule = $this->scheduleFromRows([
            ['weekday' => 1, 'open_time' => '18:00', 'close_time' => '00:00'],
        ]);

        // 月曜 23:00 → isWithinTodayTimeRange: currentTime >= openTime → 営業中
        $this->assertTrue($schedule->isOpenAt(Carbon::create(2024, 1, 8, 23, 0, 0)));

        // 火曜 00:00 → 前日チェック: currentTime < closeTime = 00:00 < 00:00 = false → 閉店
        $this->assertFalse($schedule->isOpenAt(Carbon::create(2024, 1, 9, 0, 0, 0)));
    }

    public function test_multiple_slots_lunch_and_dinner(): void
    {
        $schedule = $this->scheduleFromRows([
            ['weekday' => 1, 'open_time' => '11:00', 'close_time' => '14:00', 'sort_order' => 0],
            ['weekday' => 1, 'open_time' => '17:00', 'close_time' => '22:00', 'sort_order' => 1],
        ]);

        // 月曜 12:00 → 営業中
        $this->assertTrue($schedule->isOpenAt(Carbon::create(2024, 1, 8, 12, 0, 0)));
        // 月曜 15:00 → 閉店
        $this->assertFalse($schedule->isOpenAt(Carbon::create(2024, 1, 8, 15, 0, 0)));
        // 月曜 19:00 → 営業中
        $this->assertTrue($schedule->isOpenAt(Carbon::create(2024, 1, 8, 19, 0, 0)));
    }

    public function test_multiple_slots_with_overnight(): void
    {
        $schedule = $this->scheduleFromRows([
            ['weekday' => 1, 'open_time' => '11:00', 'close_time' => '14:00', 'sort_order' => 0],
            ['weekday' => 1, 'open_time' => '22:00', 'close_time' => '02:00', 'sort_order' => 1],
        ]);

        // 月曜 13:00 → 営業中
        $this->assertTrue($schedule->isOpenAt(Carbon::create(2024, 1, 8, 13, 0, 0)));
        // 月曜 16:00 → 閉店
        $this->assertFalse($schedule->isOpenAt(Carbon::create(2024, 1, 8, 16, 0, 0)));
        // 火曜 01:00 → 前日(月曜)の深夜営業が適用、営業中
        $this->assertTrue($schedule->isOpenAt(Carbon::create(2024, 1, 9, 1, 0, 0)));
    }

    public function test_sunday_overnight_to_monday_morning(): void
    {
        $schedule = $this->scheduleFromRows([
            ['weekday' => 0, 'open_time' => '22:00', 'close_time' => '03:00'],
        ]);

        // 月曜 01:00 → 前日(日曜)の深夜営業が適用、営業中
        $this->assertTrue($schedule->isOpenAt(Carbon::create(2024, 1, 8, 1, 0, 0)));
    }

    public function test_today_hours_includes_previous_day_overnight(): void
    {
        $schedule = $this->scheduleFromRows([
            ['weekday' => 6, 'open_time' => '22:00', 'close_time' => '02:00'],
        ]);

        // 日曜に getTodayBusinessHours を取得
        $todayHours = $schedule->todayBusinessHoursAt(Carbon::create(2024, 1, 7, 10, 0, 0));

        $this->assertContains(
            ['open_time' => '00:00', 'close_time' => '02:00'],
            $todayHours,
        );
    }

    public function test_today_hours_lists_all_slots(): void
    {
        $schedule = $this->scheduleFromRows([
            ['weekday' => 1, 'open_time' => '11:00', 'close_time' => '14:00', 'sort_order' => 0],
            ['weekday' => 1, 'open_time' => '17:00', 'close_time' => '22:00', 'sort_order' => 1],
        ]);

        $todayHours = $schedule->todayBusinessHoursAt(Carbon::create(2024, 1, 8, 12, 0, 0));

        $this->assertCount(2, $todayHours);
        $this->assertContains(['open_time' => '11:00', 'close_time' => '14:00'], $todayHours);
        $this->assertContains(['open_time' => '17:00', 'close_time' => '22:00'], $todayHours);
    }

    public function test_status_at_is_consistent_with_is_open_at(): void
    {
        $schedule = $this->scheduleFromRows([
            ['weekday' => 1, 'open_time' => '11:00', 'close_time' => '22:00'],
        ]);

        $times = [
            Carbon::create(2024, 1, 8, 10, 0, 0), // 営業前
            Carbon::create(2024, 1, 8, 12, 0, 0), // 営業中
            Carbon::create(2024, 1, 8, 22, 0, 0), // 閉店時
            Carbon::create(2024, 1, 8, 23, 0, 0), // 営業後
        ];

        foreach ($times as $time) {
            $this->assertSame(
                $schedule->statusAt($time)->isOpen,
                $schedule->isOpenAt($time),
                "Inconsistency at {$time->format('H:i')}",
            );
        }
    }

    public function test_status_at_with_null_uses_current_time(): void
    {
        // 現在時刻を固定して、null 引数が「その時刻」として解釈されるか確認する
        Carbon::setTestNow(Carbon::create(2024, 1, 8, 12, 0, 0)); // 月曜 12:00
        try {
            $schedule = $this->scheduleFromRows([
                ['weekday' => 1, 'open_time' => '11:00', 'close_time' => '14:00'],
            ]);

            $this->assertTrue($schedule->statusAt()->isOpen);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_status_at_returns_consistent_results_for_same_time(): void
    {
        $schedule = $this->scheduleFromRows([
            ['weekday' => 1, 'open_time' => '09:00', 'close_time' => '21:00'],
        ]);

        $time = Carbon::parse('2024-01-01 10:00:30');

        $first = $schedule->statusAt($time);
        $second = $schedule->statusAt($time);

        $this->assertSame($first->isOpen, $second->isOpen);
        $this->assertSame($first->todayBusinessHours, $second->todayBusinessHours);
    }
}
