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

class KdsLoadPerformanceTest extends TestCase
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

        // 100注文（ステータス混在）x 5アイテム
        $stateMethods = ['paid', 'accepted', 'inProgress', 'ready'];
        for ($i = 0; $i < 100; $i++) {
            $stateMethod = $stateMethods[$i % count($stateMethods)];
            $order = Order::factory()
                ->forTenant($this->tenant)
                ->forUser($customer)
                ->{$stateMethod}()
                ->withOrderCode(sprintf('K%03d', $i))
                ->create(['business_date' => today()->toDateString()]);

            OrderItem::factory(5)->forOrder($order)->forTenant($this->tenant)->create();
        }
    }

    public function test_kds_handles_100_orders_without_degradation(): void
    {
        $this->assertQueryCountLessThan(25, function () {
            $response = $this->actingAs($this->staff)->getJson('/api/tenant/kds/orders');
            $response->assertStatus(200);
        });
    }

    public function test_kds_status_filter_reduces_result_set(): void
    {
        $allResponse = $this->actingAs($this->staff)->getJson('/api/tenant/kds/orders');
        $allResponse->assertStatus(200);
        $allData = $allResponse->json('data');
        $allCount = is_countable($allData) ? count($allData) : 0;

        // statuses[] パラメータで accepted のみにフィルタ
        $filteredResponse = $this->actingAs($this->staff)->getJson('/api/tenant/kds/orders?statuses[]=accepted');
        $filteredResponse->assertStatus(200);
        $filteredData = $filteredResponse->json('data');
        $filteredCount = is_countable($filteredData) ? count($filteredData) : 0;

        $this->assertLessThan(
            $allCount,
            $filteredCount,
            "Filtered result set ({$filteredCount}) should be smaller than full result set ({$allCount})"
        );
    }
}
