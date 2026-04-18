<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // テスト間でTenantContextが共有されないようクリア
        app(TenantContext::class)->clear();
    }

    public function test_tenant_admin_cannot_query_other_tenant_data(): void
    {
        // 2つのテナントと各テナントの注文を作成
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        $customer = User::factory()->customer()->create();

        $order1 = Order::factory()->forTenant($tenant1)->forUser($customer)->create();
        $order2 = Order::factory()->forTenant($tenant2)->forUser($customer)->create();

        // テナント1のコンテキストを設定
        app(TenantContext::class)->setTenant($tenant1->id);

        // テナント1の注文のみ取得可能
        $visibleOrders = Order::all();
        $this->assertCount(1, $visibleOrders);
        $this->assertEquals($tenant1->id, $visibleOrders->first()->tenant_id);

        // テナント2の注文はIDで検索しても取得不可
        $this->assertNull(Order::find($order2->id));

        // データは実際に存在する（セキュリティ検証）
        $allOrders = Order::withoutTenantScope()->get();
        $this->assertCount(2, $allOrders);
    }

    public function test_order_creation_auto_sets_tenant_id(): void
    {
        $tenant = Tenant::factory()->create();
        $customer = User::factory()->customer()->create();

        app(TenantContext::class)->setTenant($tenant->id);

        $order = new Order([
            'order_code' => 'T001',
            'business_date' => now()->toDateString(),
        ]);
        $order->user_id = $customer->id;
        $order->status = OrderStatus::PendingPayment;
        $order->total_amount = 1000;
        $order->save();

        $this->assertEquals($tenant->id, $order->tenant_id);
    }

    public function test_switching_tenant_context_changes_visible_data(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        $customer = User::factory()->customer()->create();

        Order::factory()->forTenant($tenant1)->forUser($customer)->create();
        Order::factory()->forTenant($tenant2)->forUser($customer)->create();

        // テナント1のコンテキスト
        app(TenantContext::class)->setTenant($tenant1->id);
        $this->assertCount(1, Order::all());

        // テナント2に切替
        app(TenantContext::class)->clear();
        app(TenantContext::class)->setTenant($tenant2->id);
        $orders = Order::all();
        $this->assertCount(1, $orders);
        $this->assertEquals($tenant2->id, $orders->first()->tenant_id);
    }

    public function test_tenant_context_is_properly_scoped(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin()->create();
        TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($user);
        app(TenantContext::class)->setTenant($tenant->id);

        $context = app(TenantContext::class);

        $this->assertEquals($tenant->id, $context->getTenantId());
    }

    public function test_customer_has_no_tenant_scope(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user);

        $context = app(TenantContext::class);

        $this->assertNull($context->getTenantId());
    }
}
