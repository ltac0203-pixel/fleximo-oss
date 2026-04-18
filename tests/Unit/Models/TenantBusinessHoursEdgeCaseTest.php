<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Tenant;
use App\Models\TenantBusinessHour;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantBusinessHoursEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_overnight_saturday_to_sunday_at_0100(): void
    {
        $tenant = Tenant::factory()->create();
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => 6, // 土曜
            'open_time' => '22:00',
            'close_time' => '02:00',
            'sort_order' => 0,
        ]);

        // 2024-01-07 は日曜(0)、前日 2024-01-06 は土曜(6)
        $time = Carbon::create(2024, 1, 7, 1, 0, 0);
        $tenant = $tenant->fresh();

        $this->assertTrue($tenant->isOpenAt($time));
    }

    public function test_overnight_close_boundary(): void
    {
        $tenant = Tenant::factory()->create();
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => 6, // 土曜
            'open_time' => '22:00',
            'close_time' => '02:00',
            'sort_order' => 0,
        ]);

        // 日曜 02:00 ちょうど閉店: $currentTime < $closeTime → 02:00 < 02:00 は false
        $time = Carbon::create(2024, 1, 7, 2, 0, 0);
        $tenant = $tenant->fresh();

        $this->assertFalse($tenant->isOpenAt($time));
    }

    public function test_overnight_does_not_apply_to_wrong_day(): void
    {
        $tenant = Tenant::factory()->create();
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => 1, // 月曜
            'open_time' => '22:00',
            'close_time' => '02:00',
            'sort_order' => 0,
        ]);

        // 水曜(3) 01:00 → 前日は火曜(2)、月曜(1)の設定は無関係
        $time = Carbon::create(2024, 1, 10, 1, 0, 0); // 2024-01-10 は水曜
        $tenant = $tenant->fresh();

        $this->assertFalse($tenant->isOpenAt($time));
    }

    public function test_midnight_with_overnight_hours(): void
    {
        $tenant = Tenant::factory()->create();
        // 土曜の深夜営業
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => 6, // 土曜
            'open_time' => '22:00',
            'close_time' => '02:00',
            'sort_order' => 0,
        ]);

        // 日曜 00:00 → 前日の土曜設定が適用され営業中
        $time = Carbon::create(2024, 1, 7, 0, 0, 0);
        $tenant = $tenant->fresh();

        $this->assertTrue($tenant->isOpenAt($time));
    }

    public function test_close_at_midnight_behavior(): void
    {
        $tenant = Tenant::factory()->create();
        // 18:00-00:00: isOvernight → 18:00 > 00:00 なので true
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => 1, // 月曜
            'open_time' => '18:00',
            'close_time' => '00:00',
            'sort_order' => 0,
        ]);

        // 月曜 23:00 → isWithinTodayTimeRange: currentTime >= openTime → 営業中
        $time = Carbon::create(2024, 1, 8, 23, 0, 0); // 2024-01-08 は月曜
        $tenant = $tenant->fresh();
        $this->assertTrue($tenant->isOpenAt($time));

        // 火曜 00:00 → 前日チェック: currentTime < closeTime = 00:00 < 00:00 = false → 閉店
        $time2 = Carbon::create(2024, 1, 9, 0, 0, 0); // 火曜
        $tenant2 = $tenant->fresh();
        $this->assertFalse($tenant2->isOpenAt($time2));
    }

    public function test_multiple_slots_lunch_and_dinner(): void
    {
        $tenant = Tenant::factory()->create();
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => 1, // 月曜
            'open_time' => '11:00',
            'close_time' => '14:00',
            'sort_order' => 0,
        ]);
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => 1,
            'open_time' => '17:00',
            'close_time' => '22:00',
            'sort_order' => 1,
        ]);

        // 月曜 12:00 → 営業中
        $time1 = Carbon::create(2024, 1, 8, 12, 0, 0);
        $tenant1 = $tenant->fresh();
        $this->assertTrue($tenant1->isOpenAt($time1));

        // 月曜 15:00 → 閉店
        $time2 = Carbon::create(2024, 1, 8, 15, 0, 0);
        $tenant2 = $tenant->fresh();
        $this->assertFalse($tenant2->isOpenAt($time2));

        // 月曜 19:00 → 営業中
        $time3 = Carbon::create(2024, 1, 8, 19, 0, 0);
        $tenant3 = $tenant->fresh();
        $this->assertTrue($tenant3->isOpenAt($time3));
    }

    public function test_multiple_slots_with_overnight(): void
    {
        $tenant = Tenant::factory()->create();
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => 1, // 月曜
            'open_time' => '11:00',
            'close_time' => '14:00',
            'sort_order' => 0,
        ]);
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => 1,
            'open_time' => '22:00',
            'close_time' => '02:00',
            'sort_order' => 1,
        ]);

        // 月曜 13:00 → 営業中
        $time1 = Carbon::create(2024, 1, 8, 13, 0, 0);
        $tenant1 = $tenant->fresh();
        $this->assertTrue($tenant1->isOpenAt($time1));

        // 月曜 16:00 → 閉店
        $time2 = Carbon::create(2024, 1, 8, 16, 0, 0);
        $tenant2 = $tenant->fresh();
        $this->assertFalse($tenant2->isOpenAt($time2));

        // 火曜 01:00 → 前日(月曜)の深夜営業が適用、営業中
        $time3 = Carbon::create(2024, 1, 9, 1, 0, 0);
        $tenant3 = $tenant->fresh();
        $this->assertTrue($tenant3->isOpenAt($time3));
    }

    public function test_sunday_overnight_to_monday_morning(): void
    {
        $tenant = Tenant::factory()->create();
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => 0, // 日曜
            'open_time' => '22:00',
            'close_time' => '03:00',
            'sort_order' => 0,
        ]);

        // 月曜 01:00 → 前日(日曜)の深夜営業が適用、営業中
        $time = Carbon::create(2024, 1, 8, 1, 0, 0); // 2024-01-08 は月曜
        $tenant = $tenant->fresh();

        $this->assertTrue($tenant->isOpenAt($time));
    }

    public function test_today_hours_includes_previous_day_overnight(): void
    {
        $tenant = Tenant::factory()->create();
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => 6, // 土曜
            'open_time' => '22:00',
            'close_time' => '02:00',
            'sort_order' => 0,
        ]);

        // 日曜に getTodayBusinessHours を取得
        $time = Carbon::create(2024, 1, 7, 10, 0, 0); // 日曜
        $tenant = $tenant->fresh();
        $todayHours = $tenant->getTodayBusinessHours($time);

        $this->assertContains(
            ['open_time' => '00:00', 'close_time' => '02:00'],
            $todayHours
        );
    }

    public function test_today_hours_lists_all_slots(): void
    {
        $tenant = Tenant::factory()->create();
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => 1, // 月曜
            'open_time' => '11:00',
            'close_time' => '14:00',
            'sort_order' => 0,
        ]);
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => 1,
            'open_time' => '17:00',
            'close_time' => '22:00',
            'sort_order' => 1,
        ]);

        $time = Carbon::create(2024, 1, 8, 12, 0, 0); // 月曜
        $tenant = $tenant->fresh();
        $todayHours = $tenant->getTodayBusinessHours($time);

        $this->assertCount(2, $todayHours);
        $this->assertContains(['open_time' => '11:00', 'close_time' => '14:00'], $todayHours);
        $this->assertContains(['open_time' => '17:00', 'close_time' => '22:00'], $todayHours);
    }

    public function test_business_status_consistent_with_is_open_at(): void
    {
        $tenant = Tenant::factory()->create();
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => 1, // 月曜
            'open_time' => '11:00',
            'close_time' => '22:00',
            'sort_order' => 0,
        ]);

        $times = [
            Carbon::create(2024, 1, 8, 10, 0, 0), // 営業前
            Carbon::create(2024, 1, 8, 12, 0, 0), // 営業中
            Carbon::create(2024, 1, 8, 22, 0, 0), // 閉店時
            Carbon::create(2024, 1, 8, 23, 0, 0), // 営業後
        ];

        foreach ($times as $time) {
            $tenant = $tenant->fresh();
            $status = $tenant->getBusinessStatus($time);
            $isOpen = $tenant->isOpenAt($time);

            $this->assertSame(
                $status['is_open'],
                $isOpen,
                "Inconsistency at {$time->format('H:i')}: getBusinessStatus={$status['is_open']}, isOpenAt={$isOpen}"
            );
        }
    }
}
