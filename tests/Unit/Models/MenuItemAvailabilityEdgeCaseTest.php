<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\MenuItem;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MenuItemAvailabilityEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    public function test_weekdays_only_available_monday_through_friday(): void
    {
        $tenant = Tenant::factory()->create();
        $item = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'available_days' => MenuItem::WEEKDAYS,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        // 月曜(1)〜金曜(5): true
        for ($day = 1; $day <= 5; $day++) {
            $this->assertTrue($item->isAvailableOn($day), "Day {$day} should be available");
        }

        // 日曜(0), 土曜(6): false
        $this->assertFalse($item->isAvailableOn(0), 'Sunday should not be available');
        $this->assertFalse($item->isAvailableOn(6), 'Saturday should not be available');
    }

    public function test_weekends_only_available_saturday_and_sunday(): void
    {
        $tenant = Tenant::factory()->create();
        $item = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'available_days' => MenuItem::WEEKENDS,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        // 日曜(0), 土曜(6): true
        $this->assertTrue($item->isAvailableOn(0), 'Sunday should be available');
        $this->assertTrue($item->isAvailableOn(6), 'Saturday should be available');

        // 月曜(1)〜金曜(5): false
        for ($day = 1; $day <= 5; $day++) {
            $this->assertFalse($item->isAvailableOn($day), "Day {$day} should not be available");
        }
    }

    public function test_single_day_flag_isolation(): void
    {
        $tenant = Tenant::factory()->create();
        $item = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'available_days' => MenuItem::WEDNESDAY, // 8
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        // 水曜(3)のみ true
        $this->assertTrue($item->isAvailableOn(3), 'Wednesday should be available');

        // 他の曜日は false
        foreach ([0, 1, 2, 4, 5, 6] as $day) {
            $this->assertFalse($item->isAvailableOn($day), "Day {$day} should not be available");
        }
    }

    public function test_weekday_morning_item_before_available_time(): void
    {
        $tenant = Tenant::factory()->create();
        $item = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'available_days' => MenuItem::WEEKDAYS,
            'available_from' => '09:00:00',
            'available_until' => '11:00:00',
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        // 2024-01-01 は月曜、08:59 → 提供時間外
        Carbon::setTestNow('2024-01-01 08:59:00');

        $this->assertFalse($item->isAvailableNow());
    }

    public function test_weekday_morning_item_during_available_time(): void
    {
        $tenant = Tenant::factory()->create();
        $item = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'available_days' => MenuItem::WEEKDAYS,
            'available_from' => '09:00:00',
            'available_until' => '11:00:00',
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        // 2024-01-01 は月曜、10:00 → 提供時間内
        Carbon::setTestNow('2024-01-01 10:00:00');

        $this->assertTrue($item->isAvailableNow());
    }

    public function test_weekend_morning_item_blocked_by_weekday_restriction(): void
    {
        $tenant = Tenant::factory()->create();
        $item = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'available_days' => MenuItem::WEEKDAYS,
            'available_from' => '09:00:00',
            'available_until' => '11:00:00',
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        // 2024-01-06 は土曜、10:00 → 時間内だが曜日制限でブロック
        Carbon::setTestNow('2024-01-06 10:00:00');

        $this->assertFalse($item->isAvailableNow());
    }

    public function test_available_at_exactly_from_time(): void
    {
        $tenant = Tenant::factory()->create();
        $item = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'available_days' => MenuItem::ALL_DAYS,
            'available_from' => '09:00:00',
            'available_until' => '17:00:00',
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        // 09:00:00 ぴったり → available (>= チェック)
        Carbon::setTestNow('2024-01-01 09:00:00');

        $this->assertTrue($item->isAvailableNow());
    }

    public function test_available_at_exactly_until_time(): void
    {
        $tenant = Tenant::factory()->create();
        $item = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'available_days' => MenuItem::ALL_DAYS,
            'available_from' => '09:00:00',
            'available_until' => '17:00:00',
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        // 17:00:00 ぴったり → available ($currentTime > $this->available_until で判定、17:00:00 > 17:00:00 は false → 販売可能)
        Carbon::setTestNow('2024-01-01 17:00:00');

        $this->assertTrue($item->isAvailableNow());
    }
}
