<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrdersPageTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['is_active' => true]);
        $this->customer = User::factory()->customer()->create();
    }

    public function test_認証なしでアクセスするとログインにリダイレクト(): void
    {
        $response = $this->get(route('order.orders.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_顧客以外はアクセス不可(): void
    {
        $tenantAdmin = User::factory()->tenantAdmin()->create();
        $this->actingAs($tenantAdmin);

        $response = $this->get(route('order.orders.index'));

        $response->assertForbidden();
    }

    public function test_注文一覧ページが表示される(): void
    {
        $this->actingAs($this->customer);

        Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::Paid,
        ]);

        $response = $this->get(route('order.orders.index'));

        $response->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Customer/Orders/Index')
                    ->has('orders')
                    ->has('orders.data', 1)
            );
    }

    public function test_注文履歴一覧は決済完了のみ表示される(): void
    {
        $this->actingAs($this->customer);

        Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::Paid,
        ]);

        Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::Accepted,
        ]);

        Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::InProgress,
        ]);

        Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::Ready,
        ]);

        Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::Completed,
        ]);

        $response = $this->get(route('order.orders.index'));

        $response->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Customer/Orders/Index')
                    ->has('orders.data', 1)
                    ->where('orders.data.0.status', OrderStatus::Paid->value)
            );
    }

    public function test_statusクエリを指定しても一覧は決済完了のみ(): void
    {
        $this->actingAs($this->customer);

        Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::Paid,
        ]);

        Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::InProgress,
        ]);

        $response = $this->get(route('order.orders.index', ['status' => 'in_progress']));

        $response->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Customer/Orders/Index')
                    ->has('orders.data', 1)
                    ->where('orders.data.0.status', OrderStatus::Paid->value)
            );
    }

    public function test_決済完了以外の注文は一覧に表示されない(): void
    {
        $this->actingAs($this->customer);

        Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::PendingPayment,
        ]);

        Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::PaymentFailed,
        ]);

        Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::Cancelled,
        ]);

        Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::Refunded,
        ]);

        Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::Completed,
        ]);

        $response = $this->get(route('order.orders.index'));

        $response->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Customer/Orders/Index')
                    ->has('orders.data', 0)
            );
    }

    public function test_注文詳細ページが表示される(): void
    {
        $this->actingAs($this->customer);

        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::Completed,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->get(route('order.orders.show', $order));

        $response->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Customer/Orders/Show')
                    ->has('order')
                    ->where('order.id', $order->id)
                    ->where('order.order_code', $order->order_code)
            );
    }

    public function test_他ユーザーの注文詳細にはアクセス不可(): void
    {
        $this->actingAs($this->customer);

        $otherUser = User::factory()->customer()->create();
        $order = Order::factory()->create([
            'user_id' => $otherUser->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::Completed,
        ]);

        $response = $this->get(route('order.orders.show', $order));

        $response->assertForbidden();
    }

    public function test_他ユーザーの注文は一覧に表示されない(): void
    {
        $this->actingAs($this->customer);

        $otherUser = User::factory()->customer()->create();

        Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::Paid,
        ]);

        Order::factory()->create([
            'user_id' => $otherUser->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::Completed,
        ]);

        $response = $this->get(route('order.orders.index'));

        $response->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('orders.data', 1)
            );
    }

    public function test_複数テナントの注文が一覧に表示される(): void
    {
        $this->actingAs($this->customer);

        $tenant2 = Tenant::factory()->create(['is_active' => true]);

        Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::Paid,
        ]);

        Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $tenant2->id,
            'status' => OrderStatus::Paid,
        ]);

        $response = $this->get(route('order.orders.index'));

        $response->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('orders.data', 2)
            );
    }

    public function test_無効なステータスでフィルタするとバリデーションエラー(): void
    {
        $this->actingAs($this->customer);

        $response = $this->get(route('order.orders.index', ['status' => 'invalid']));

        $response->assertStatus(302);
    }

    public function test_存在しない注文にアクセスすると404(): void
    {
        $this->actingAs($this->customer);

        $response = $this->get(route('order.orders.show', 99999));

        $response->assertNotFound();
    }
}
