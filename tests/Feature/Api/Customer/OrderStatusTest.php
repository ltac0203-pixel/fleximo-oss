<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Customer;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->customer()->create();
        $this->tenant = Tenant::factory()->create();
    }

    public function test_status_returns_order_status_for_owner(): void
    {
        $order = Order::factory()
            ->forUser($this->customer)
            ->forTenant($this->tenant)
            ->accepted()
            ->create();

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/customer/orders/{$order->id}/status");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'status_label',
                    'is_terminal',
                    'ready_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.status', 'accepted')
            ->assertJsonPath('data.status_label', '受付済み')
            ->assertJsonPath('data.is_terminal', false)
            ->assertJsonPath('data.ready_at', null);
    }

    public function test_status_returns_ready_order_with_ready_at(): void
    {
        $order = Order::factory()
            ->forUser($this->customer)
            ->forTenant($this->tenant)
            ->ready()
            ->create();

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/customer/orders/{$order->id}/status");

        $response->assertOk()
            ->assertJsonPath('data.status', 'ready')
            ->assertJsonPath('data.status_label', '準備完了')
            ->assertJsonPath('data.is_terminal', false);

        $this->assertNotNull($response->json('data.ready_at'));
    }

    public function test_status_returns_terminal_flag_for_completed_order(): void
    {
        $order = Order::factory()
            ->forUser($this->customer)
            ->forTenant($this->tenant)
            ->completed()
            ->create();

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/customer/orders/{$order->id}/status");

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.is_terminal', true);
    }

    public function test_status_returns_terminal_flag_for_payment_failed_order(): void
    {
        $order = Order::factory()
            ->forUser($this->customer)
            ->forTenant($this->tenant)
            ->paymentFailed()
            ->create();

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/customer/orders/{$order->id}/status");

        $response->assertOk()
            ->assertJsonPath('data.status', 'payment_failed')
            ->assertJsonPath('data.is_terminal', true);
    }

    public function test_status_returns_non_terminal_for_cancelled_order(): void
    {
        $order = Order::factory()
            ->forUser($this->customer)
            ->forTenant($this->tenant)
            ->cancelled()
            ->create();

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/customer/orders/{$order->id}/status");

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.is_terminal', false);
    }

    public function test_status_denies_access_to_other_users_order(): void
    {
        $otherCustomer = User::factory()->customer()->create();
        $order = Order::factory()
            ->forUser($otherCustomer)
            ->forTenant($this->tenant)
            ->accepted()
            ->create();

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/customer/orders/{$order->id}/status");

        $response->assertForbidden();
    }

    public function test_status_requires_authentication(): void
    {
        $order = Order::factory()
            ->forUser($this->customer)
            ->forTenant($this->tenant)
            ->accepted()
            ->create();

        $response = $this->getJson("/api/customer/orders/{$order->id}/status");

        $response->assertUnauthorized();
    }

    public function test_status_returns_404_for_nonexistent_order(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/customer/orders/99999/status');

        $response->assertNotFound();
    }
}
