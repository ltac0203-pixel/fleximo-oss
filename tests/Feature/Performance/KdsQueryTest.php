<?php

declare(strict_types=1);

namespace Tests\Feature\Performance;

use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOption;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\QueryCountAssertions;
use Tests\TestCase;

class KdsQueryTest extends TestCase
{
    use QueryCountAssertions;
    use RefreshDatabase;

    private Tenant $tenant;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->tenant = Tenant::factory()->create();

        $this->staff = User::factory()->create([
            'role' => UserRole::TenantAdmin,
        ]);
        TenantUser::factory()->create([
            'user_id' => $this->staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Admin,
        ]);

        $this->setTenantAlwaysOpen($this->tenant);

        $today = Carbon::today()->format('Y-m-d');
        $statuses = ['paid', 'accepted', 'inProgress'];

        for ($i = 0; $i < 20; $i++) {
            $statusMethod = $statuses[$i % 3];
            $order = Order::factory()
                ->forTenant($this->tenant)
                ->forBusinessDate($today)
                ->{$statusMethod}()
                ->withOrderCode(sprintf('K%03d', $i))
                ->create();

            $orderItems = OrderItem::factory()
                ->count(3)
                ->forOrder($order)
                ->create();

            foreach ($orderItems as $orderItem) {
                OrderItemOption::factory()
                    ->count(2)
                    ->forOrderItem($orderItem)
                    ->create();
            }
        }
    }

    public function test_kds_index_query_count_constant_regardless_of_order_count(): void
    {
        $this->assertQueryCountLessThan(25, function () {
            $this->actingAs($this->staff)
                ->getJson('/api/tenant/kds/orders')
                ->assertOk();
        });
    }
}
