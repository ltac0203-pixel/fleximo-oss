<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Customer;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteControllerTest extends TestCase
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

    public function test_index_returns_empty_for_new_customer(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/customer/favorites');

        $response->assertOk()
            ->assertJson(['data' => []]);
    }

    public function test_index_returns_favorited_tenant_ids(): void
    {
        $tenant2 = Tenant::factory()->create();
        $this->customer->favoriteTenants()->attach([$this->tenant->id, $tenant2->id]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/customer/favorites');

        $response->assertOk();

        $ids = $response->json('data');
        $this->assertCount(2, $ids);
        $this->assertContains($this->tenant->id, $ids);
        $this->assertContains($tenant2->id, $ids);
    }

    public function test_toggle_adds_tenant_to_favorites(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/customer/favorites/tenants/{$this->tenant->id}");

        $response->assertOk()
            ->assertJson(['is_favorited' => true]);

        $this->assertDatabaseHas('favorite_tenants', [
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_toggle_removes_existing_favorite(): void
    {
        $this->customer->favoriteTenants()->attach($this->tenant->id);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/customer/favorites/tenants/{$this->tenant->id}");

        $response->assertOk()
            ->assertJson(['is_favorited' => false]);

        $this->assertDatabaseMissing('favorite_tenants', [
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_non_customer_cannot_access_favorites(): void
    {
        $tenantAdmin = User::factory()->tenantAdmin()->create();

        $this->actingAs($tenantAdmin, 'sanctum')
            ->getJson('/api/customer/favorites')
            ->assertForbidden();

        $this->actingAs($tenantAdmin, 'sanctum')
            ->postJson("/api/customer/favorites/tenants/{$this->tenant->id}")
            ->assertForbidden();
    }

    public function test_unauthenticated_cannot_access_favorites(): void
    {
        $this->getJson('/api/customer/favorites')
            ->assertUnauthorized();

        $this->postJson("/api/customer/favorites/tenants/{$this->tenant->id}")
            ->assertUnauthorized();
    }

    public function test_toggle_with_nonexistent_tenant_returns_404(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/customer/favorites/tenants/99999');

        $response->assertNotFound();
    }
}
