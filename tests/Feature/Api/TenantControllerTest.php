<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_tenants(): void
    {
        $user = User::factory()->customer()->create();
        Tenant::factory()->count(3)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'address',
                        'today_business_hours',
                        'is_open',
                    ],
                ],
                'links',
                'meta',
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_inactive_tenants_are_excluded_from_list(): void
    {
        $user = User::factory()->customer()->create();
        Tenant::factory()->count(2)->create();
        Tenant::factory()->inactive()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_suspended_tenants_are_excluded_from_list(): void
    {
        $user = User::factory()->customer()->create();
        Tenant::factory()->count(2)->create();
        Tenant::factory()->suspended()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_search_tenants_by_keyword(): void
    {
        $user = User::factory()->customer()->create();
        Tenant::factory()->create(['name' => 'カフェレストラン']);
        Tenant::factory()->create(['name' => '寿司屋']);
        Tenant::factory()->create(['name' => 'カフェバー']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants?query=カフェ');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_pagination_works_correctly(): void
    {
        $user = User::factory()->customer()->create();
        Tenant::factory()->count(25)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants?per_page=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.total'));
        $this->assertEquals(3, $response->json('meta.last_page'));
    }

    public function test_unauthenticated_user_cannot_list_tenants(): void
    {
        Tenant::factory()->count(3)->create();

        $response = $this->getJson('/api/tenants');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_tenant_detail(): void
    {
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create([
            'name' => 'テストテナント',
            'email' => 'test@example.com',
            'phone' => '03-1234-5678',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/tenants/{$tenant->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'slug',
                'address',
                'today_business_hours',
                'is_open',
            ]);

        $response->assertJsonMissing(['email'])
            ->assertJsonMissing(['phone']);
    }

    public function test_viewing_nonexistent_tenant_returns_404(): void
    {
        $user = User::factory()->customer()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants/99999');

        $response->assertNotFound();
    }

    public function test_viewing_inactive_tenant_returns_404(): void
    {
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->inactive()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/tenants/{$tenant->id}");

        $response->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_view_tenant_detail(): void
    {
        $tenant = Tenant::factory()->create();

        $response = $this->getJson("/api/tenants/{$tenant->id}");

        $response->assertUnauthorized();
    }

    public function test_tenant_admin_can_also_list_tenants(): void
    {
        $admin = User::factory()->tenantAdmin()->create();
        Tenant::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/tenants');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_validation_error_on_invalid_per_page(): void
    {
        $user = User::factory()->customer()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants?per_page=100');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_validation_error_on_invalid_lat_lng(): void
    {
        $user = User::factory()->customer()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenants?lat=100&lng=200');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['lat', 'lng']);
    }
}
