<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_customer_cannot_access_cart_api(): void
    {
        $user = User::factory()->unverified()->customer()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/customer/cart');

        $response->assertStatus(403);
    }

    public function test_unverified_customer_cannot_execute_checkout_api(): void
    {
        $user = User::factory()->unverified()->customer()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/customer/checkout', []);

        $response->assertStatus(403);
    }

    public function test_unverified_tenant_admin_cannot_access_dashboard_api(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => UserRole::TenantAdmin,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenant/dashboard/sales');

        $response->assertStatus(403);
    }

    public function test_unverified_tenant_staff_cannot_access_kds_api(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => UserRole::TenantStaff,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tenant/kds/orders');

        $response->assertStatus(403);
    }
}
