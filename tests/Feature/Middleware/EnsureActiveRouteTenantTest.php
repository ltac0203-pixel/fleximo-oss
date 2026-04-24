<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureActiveRouteTenantTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Tenant $activeTenant;

    private Tenant $inactiveTenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->customer()->create();
        $this->activeTenant = Tenant::factory()->create([
            'status' => 'active',
            'fincode_shop_id' => 'shop_active',
        ]);
        $this->inactiveTenant = Tenant::factory()->inactive()->create([
            'fincode_shop_id' => 'shop_inactive',
        ]);
    }

    public function test_active_tenant_allows_access_to_cards_index(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/customer/tenants/{$this->activeTenant->id}/cards");

        $response->assertOk();
    }

    public function test_inactive_tenant_returns_404_for_cards_index(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/customer/tenants/{$this->inactiveTenant->id}/cards");

        $response->assertNotFound()
            ->assertJson(['message' => 'テナントが見つかりません']);
    }

    public function test_inactive_tenant_returns_404_for_cards_store(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/customer/tenants/{$this->inactiveTenant->id}/cards", [
                'token' => 'tok_test_123',
            ]);

        $response->assertNotFound()
            ->assertJson(['message' => 'テナントが見つかりません']);
    }

    public function test_inactive_tenant_returns_404_for_cards_destroy(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->deleteJson("/api/customer/tenants/{$this->inactiveTenant->id}/cards/1");

        $response->assertNotFound()
            ->assertJson(['message' => 'テナントが見つかりません']);
    }

    public function test_suspended_tenant_returns_404_for_cards_index(): void
    {
        $suspendedTenant = Tenant::factory()->suspended()->create([
            'fincode_shop_id' => 'shop_suspended',
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/customer/tenants/{$suspendedTenant->id}/cards");

        $response->assertNotFound()
            ->assertJson(['message' => 'テナントが見つかりません']);
    }
}
