<?php

declare(strict_types=1);

namespace Tests\Feature\Performance;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOption;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\QueryCountAssertions;
use Tests\TestCase;

class CustomerOrderQueryTest extends TestCase
{
    use QueryCountAssertions;
    use RefreshDatabase;

    private Tenant $tenant;

    private User $customer;

    private Order $detailOrder;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->tenant = Tenant::factory()->create();
        $this->setTenantAlwaysOpen($this->tenant);

        $this->customer = User::factory()->create([
            'role' => UserRole::Customer,
        ]);

        // 50注文を作成（order_codeのユニーク制約回避のため連番指定）
        $orders = collect();
        for ($i = 0; $i < 50; $i++) {
            $orders->push(Order::factory()
                ->forTenant($this->tenant)
                ->forUser($this->customer)
                ->paid()
                ->withOrderCode(sprintf('T%03d', $i))
                ->create());
        }

        // 詳細テスト用の注文にアイテムとオプションを追加
        $this->detailOrder = $orders->first();
        $orderItems = OrderItem::factory()
            ->count(5)
            ->forOrder($this->detailOrder)
            ->create();

        foreach ($orderItems as $orderItem) {
            OrderItemOption::factory()
                ->count(2)
                ->forOrderItem($orderItem)
                ->create();
        }
    }

    public function test_order_list_query_count_constant_with_many_orders(): void
    {
        $this->assertQueryCountLessThan(15, function () {
            $this->actingAs($this->customer)
                ->getJson('/api/customer/orders')
                ->assertOk();
        });
    }

    public function test_order_detail_no_n_plus_one_for_items_and_options(): void
    {
        $this->assertQueryCountLessThan(15, function () {
            $this->actingAs($this->customer)
                ->getJson("/api/customer/orders/{$this->detailOrder->id}")
                ->assertOk();
        });
    }
}
