<?php

declare(strict_types=1);

namespace Tests\Feature\Performance;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\QueryCountAssertions;
use Tests\TestCase;

class DashboardAggregationPerformanceTest extends TestCase
{
    use QueryCountAssertions;
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->tenant = Tenant::factory()->create(['status' => 'active', 'is_active' => true, 'is_approved' => true]);
        $this->staff = User::factory()->tenantAdmin()->create();
        TenantUser::factory()->create(['tenant_id' => $this->tenant->id, 'user_id' => $this->staff->id]);
        $this->setTenantAlwaysOpen($this->tenant);

        $customer = User::factory()->customer()->create();

        // 1000注文（ステータス混在）+ 各1-2アイテム
        $stateMethods = ['completed', 'accepted', 'inProgress', 'paid'];
        for ($i = 0; $i < 1000; $i++) {
            $stateMethod = $stateMethods[$i % count($stateMethods)];
            $order = Order::factory()
                ->forTenant($this->tenant)
                ->forUser($customer)
                ->{$stateMethod}()
                ->withOrderCode(chr(65 + intdiv($i, 100)).sprintf('%02d', $i % 100))
                ->create(['business_date' => today()->toDateString()]);

            $itemCount = ($i % 2) + 1;
            OrderItem::factory($itemCount)->forOrder($order)->forTenant($this->tenant)->create();
        }
    }

    public function test_summary_with_large_order_volume(): void
    {
        $this->assertQueryCountLessThan(30, function () {
            $response = $this->actingAs($this->staff)->getJson('/api/tenant/dashboard/summary');
            $response->assertStatus(200);
        });
    }

    public function test_hourly_distribution_with_large_volume(): void
    {
        $this->assertQueryCountLessThan(30, function () {
            $response = $this->actingAs($this->staff)->getJson('/api/tenant/dashboard/hourly');
            $response->assertStatus(200);
        });
    }

    public function test_top_items_with_many_menu_items(): void
    {
        $this->assertQueryCountLessThan(30, function () {
            $response = $this->actingAs($this->staff)->getJson('/api/tenant/dashboard/top-items');
            $response->assertStatus(200);
        });
    }
}
