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

    public function test_active_tenant_allows_access_to_tenant_show(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/tenants/{$this->activeTenant->id}");

        $response->assertOk();
    }

    public function test_inactive_tenant_returns_404_for_tenant_show(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/tenants/{$this->inactiveTenant->id}");

        $response->assertNotFound()
            ->assertJson(['message' => 'テナントが見つかりません']);
    }

    public function test_active_tenant_allows_access_to_tenant_menu(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/tenants/{$this->activeTenant->id}/menu");

        $response->assertOk();
    }

    public function test_inactive_tenant_returns_404_for_tenant_menu(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/tenants/{$this->inactiveTenant->id}/menu");

        $response->assertNotFound()
            ->assertJson(['message' => 'テナントが見つかりません']);
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

    public function test_suspended_tenant_returns_404(): void
    {
        $suspendedTenant = Tenant::factory()->suspended()->create([
            'fincode_shop_id' => 'shop_suspended',
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/tenants/{$suspendedTenant->id}");

        $response->assertNotFound()
            ->assertJson(['message' => 'テナントが見つかりません']);
    }

    public function test_tenant_index_still_accessible_without_middleware(): void
    {
        // テナント一覧はミドルウェアを通さないのでアクセス可能
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/tenants');

        $response->assertOk();
    }
}
