<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->clear();
    }

    public function test_order_can_be_created(): void
    {
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'order_code' => 'A001',
            'business_date' => now()->toDateString(),
            'status' => OrderStatus::PendingPayment,
            'total_amount' => 1500,
        ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'order_code' => 'A001',
            'total_amount' => 1500,
        ]);
    }

    public function test_tenant_context_sets_tenant_id_on_create(): void
    {
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();

        app(TenantContext::class)->setTenant($tenant->id);

        $order = new Order([
            'order_code' => 'T001',
            'business_date' => now()->toDateString(),
        ]);
        $order->user_id = $user->id;
        $order->status = OrderStatus::PendingPayment;
        $order->total_amount = 1000;
        $order->save();

        $this->assertEquals($tenant->id, $order->tenant_id);
    }

    public function test_order_code_must_be_unique_within_same_tenant_and_business_date(): void
    {
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();
        $businessDate = now()->toDateString();

        Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'order_code' => 'A001',
            'business_date' => $businessDate,
            'status' => OrderStatus::PendingPayment,
            'total_amount' => 1000,
        ]);

        $this->expectException(QueryException::class);

        Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'order_code' => 'A001',
            'business_date' => $businessDate,
            'status' => OrderStatus::PendingPayment,
            'total_amount' => 2000,
        ]);
    }

    public function test_same_order_code_can_be_used_on_different_business_dates(): void
    {
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();

        Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'order_code' => 'A001',
            'business_date' => now()->toDateString(),
            'status' => OrderStatus::PendingPayment,
            'total_amount' => 1000,
        ]);

        $order2 = Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'order_code' => 'A001',
            'business_date' => now()->addDay()->toDateString(),
            'status' => OrderStatus::PendingPayment,
            'total_amount' => 2000,
        ]);

        $this->assertDatabaseCount('orders', 2);
    }

    public function test_same_order_code_can_be_used_by_different_tenants(): void
    {
        $user = User::factory()->customer()->create();
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        $businessDate = now()->toDateString();

        Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant1->id,
            'order_code' => 'A001',
            'business_date' => $businessDate,
            'status' => OrderStatus::PendingPayment,
            'total_amount' => 1000,
        ]);

        $order2 = Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant2->id,
            'order_code' => 'A001',
            'business_date' => $businessDate,
            'status' => OrderStatus::PendingPayment,
            'total_amount' => 2000,
        ]);

        $this->assertDatabaseCount('orders', 2);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($user->id, $order->user->id);
    }

    public function test_belongs_to_tenant(): void
    {
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $order->tenant);
        $this->assertEquals($tenant->id, $order->tenant->id);
    }

    public function test_has_many_items(): void
    {
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertCount(2, $order->items);
    }

    public function test_status_is_cast_to_order_status_enum(): void
    {
        $order = Order::factory()->create();

        $this->assertInstanceOf(OrderStatus::class, $order->status);
    }

    public function test_business_date_is_cast_to_carbon(): void
    {
        $order = Order::factory()->create([
            'business_date' => '2026-01-20',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $order->business_date);
        $this->assertEquals('2026-01-20', $order->business_date->format('Y-m-d'));
    }

    public function test_is_pending_payment(): void
    {
        $order = Order::factory()->pendingPayment()->create();
        $this->assertTrue($order->isPendingPayment());
    }

    public function test_is_paid(): void
    {
        $order = Order::factory()->paid()->create();
        $this->assertTrue($order->isPaid());
    }

    public function test_is_accepted(): void
    {
        $order = Order::factory()->accepted()->create();
        $this->assertTrue($order->isAccepted());
    }

    public function test_is_in_progress(): void
    {
        $order = Order::factory()->inProgress()->create();
        $this->assertTrue($order->isInProgress());
    }

    public function test_is_ready(): void
    {
        $order = Order::factory()->ready()->create();
        $this->assertTrue($order->isReady());
    }

    public function test_is_completed(): void
    {
        $order = Order::factory()->completed()->create();
        $this->assertTrue($order->isCompleted());
    }

    public function test_is_cancelled(): void
    {
        $order = Order::factory()->cancelled()->create();
        $this->assertTrue($order->isCancelled());
    }

    public function test_is_payment_failed(): void
    {
        $order = Order::factory()->paymentFailed()->create();
        $this->assertTrue($order->isPaymentFailed());
    }

    public function test_is_refunded(): void
    {
        $order = Order::factory()->refunded()->create();
        $this->assertTrue($order->isRefunded());
    }

    public function test_mark_as_paid_sets_status_and_timestamp(): void
    {
        $order = Order::factory()->pendingPayment()->create();

        $order->markAsPaid();

        $this->assertTrue($order->isPaid());
        $this->assertNotNull($order->paid_at);
    }

    public function test_mark_as_paid_throws_exception_for_invalid_transition(): void
    {
        $order = Order::factory()->completed()->create();

        $this->expectException(InvalidArgumentException::class);

        $order->markAsPaid();
    }

    public function test_mark_as_payment_failed_sets_status(): void
    {
        $order = Order::factory()->pendingPayment()->create();

        $order->markAsPaymentFailed();

        $this->assertTrue($order->isPaymentFailed());
    }

    public function test_mark_as_accepted_sets_status_and_timestamp(): void
    {
        $order = Order::factory()->paid()->create();

        $order->markAsAccepted();

        $this->assertTrue($order->isAccepted());
        $this->assertNotNull($order->accepted_at);
    }

    public function test_mark_as_in_progress_sets_status_and_timestamp(): void
    {
        $order = Order::factory()->accepted()->create();

        $order->markAsInProgress();

        $this->assertTrue($order->isInProgress());
        $this->assertNotNull($order->in_progress_at);
    }

    public function test_mark_as_ready_sets_status_and_timestamp(): void
    {
        $order = Order::factory()->inProgress()->create();

        $order->markAsReady();

        $this->assertTrue($order->isReady());
        $this->assertNotNull($order->ready_at);
    }

    public function test_mark_as_completed_sets_status_and_timestamp(): void
    {
        $order = Order::factory()->ready()->create();

        $order->markAsCompleted();

        $this->assertTrue($order->isCompleted());
        $this->assertNotNull($order->completed_at);
    }

    public function test_mark_as_cancelled_sets_status_and_timestamp(): void
    {
        $order = Order::factory()->paid()->create();

        $order->markAsCancelled();

        $this->assertTrue($order->isCancelled());
        $this->assertNotNull($order->cancelled_at);
    }

    public function test_mark_as_refunded_sets_status(): void
    {
        $order = Order::factory()->cancelled()->create();

        $order->markAsRefunded();

        $this->assertTrue($order->isRefunded());
    }

    public function test_can_be_cancelled_returns_correct_value(): void
    {
        $paidOrder = Order::factory()->paid()->create();
        $this->assertTrue($paidOrder->canBeCancelled());

        $completedOrder = Order::factory()->completed()->create();
        $this->assertFalse($completedOrder->canBeCancelled());
    }

    public function test_is_active_returns_correct_value(): void
    {
        $acceptedOrder = Order::factory()->accepted()->create();
        $this->assertTrue($acceptedOrder->isActive());

        $paidOrder = Order::factory()->paid()->create();
        $this->assertFalse($paidOrder->isActive());
    }

    public function test_active_scope_returns_only_active_orders(): void
    {
        $tenant = Tenant::factory()->create();

        app(TenantContext::class)->setTenantInstance($tenant);

        Order::factory()->accepted()->create(['tenant_id' => $tenant->id, 'order_code' => 'A001']);
        Order::factory()->inProgress()->create(['tenant_id' => $tenant->id, 'order_code' => 'A002']);
        Order::factory()->ready()->create(['tenant_id' => $tenant->id, 'order_code' => 'A003']);
        Order::factory()->completed()->create(['tenant_id' => $tenant->id, 'order_code' => 'A004']);
        Order::factory()->paid()->create(['tenant_id' => $tenant->id, 'order_code' => 'A005']);

        $activeOrders = Order::active()->get();

        $this->assertCount(3, $activeOrders);
    }

    public function test_for_business_date_scope(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $targetDate = \Carbon\Carbon::parse('2026-01-20');

        Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'business_date' => $targetDate,
            'order_code' => 'A001',
        ]);
        Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'business_date' => $targetDate,
            'order_code' => 'A002',
        ]);
        Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'business_date' => $targetDate->copy()->addDay(),
            'order_code' => 'A003',
        ]);

        $allOrders = Order::withoutGlobalScopes()->get();
        $this->assertCount(3, $allOrders);

        $orders = Order::withoutGlobalScopes()
            ->forBusinessDate($targetDate)
            ->get();

        $this->assertCount(2, $orders);
    }

    public function test_with_status_scope(): void
    {
        $tenant = Tenant::factory()->create();

        app(TenantContext::class)->setTenantInstance($tenant);

        Order::factory()->paid()->create(['tenant_id' => $tenant->id, 'order_code' => 'B001']);
        Order::factory()->paid()->create(['tenant_id' => $tenant->id, 'order_code' => 'B002']);
        Order::factory()->accepted()->create(['tenant_id' => $tenant->id, 'order_code' => 'B003']);

        $paidOrders = Order::withStatus(OrderStatus::Paid)->get();

        $this->assertCount(2, $paidOrders);
    }

    public function test_for_customer_across_tenants_scope_returns_only_target_user_orders(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        $targetUser = User::factory()->customer()->create();
        $otherUser = User::factory()->customer()->create();

        app(TenantContext::class)->setTenantInstance($tenant1);

        Order::factory()->create(['tenant_id' => $tenant1->id, 'user_id' => $targetUser->id, 'order_code' => 'C001']);
        Order::factory()->create(['tenant_id' => $tenant2->id, 'user_id' => $targetUser->id, 'order_code' => 'C002']);
        Order::factory()->create(['tenant_id' => $tenant2->id, 'user_id' => $otherUser->id, 'order_code' => 'C003']);

        $scopedOrders = Order::where('user_id', $targetUser->id)->get();
        $this->assertCount(1, $scopedOrders);

        $orders = Order::forCustomerAcrossTenants($targetUser->id)->get();

        $this->assertCount(2, $orders);
        $this->assertTrue($orders->every(fn (Order $order) => $order->user_id === $targetUser->id));
        $this->assertEqualsCanonicalizing([$tenant1->id, $tenant2->id], $orders->pluck('tenant_id')->all());
    }

    public function test_tenant_scope_filters_orders_by_tenant(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        Order::factory()->create(['tenant_id' => $tenant1->id]);
        Order::factory()->create(['tenant_id' => $tenant1->id]);
        Order::factory()->create(['tenant_id' => $tenant2->id]);

        app(TenantContext::class)->setTenantInstance($tenant1);

        $orders = Order::all();

        $this->assertCount(2, $orders);
        $this->assertTrue($orders->every(fn ($order) => $order->tenant_id === $tenant1->id));
    }

    public function test_order_items_are_deleted_when_order_is_deleted(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->create(['tenant_id' => $tenant->id]);

        $item1 = OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
        ]);

        $item2 = OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
        ]);

        $orderId = $order->id;
        $order->delete();

        $this->assertDatabaseMissing('orders', ['id' => $orderId]);
        $this->assertDatabaseMissing('order_items', ['id' => $item1->id]);
        $this->assertDatabaseMissing('order_items', ['id' => $item2->id]);
    }
}
