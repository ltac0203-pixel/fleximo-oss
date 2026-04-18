<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Tenant;
use App\Models\TenantBusinessHour;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    // 冪等性キーヘッダーを設定する（決済系テスト用）
    protected function withIdempotencyKey(): static
    {
        return $this->withHeaders([
            'Idempotency-Key' => (string) Str::uuid(),
        ]);
    }

    // テナントに全曜日・全時間帯の営業時間を設定し、常にis_open=trueにする
    protected function setTenantAlwaysOpen(Tenant $tenant): void
    {
        for ($weekday = 0; $weekday <= 6; $weekday++) {
            TenantBusinessHour::create([
                'tenant_id' => $tenant->id,
                'weekday' => $weekday,
                'open_time' => '00:00',
                'close_time' => '23:59',
                'sort_order' => 0,
            ]);
        }
    }
}
