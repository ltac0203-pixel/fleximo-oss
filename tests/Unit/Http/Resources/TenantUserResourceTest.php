<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Resources;

use App\Enums\TenantUserRole;
use App\Http\Resources\TenantUserResource;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TenantUserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_resource_returns_correct_structure(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_active' => true,
        ]);

        $tenant = Tenant::factory()->create();

        $tenantUser = TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Admin,
        ]);

        $resource = new TenantUserResource($tenantUser);
        $request = Request::create('/test', 'GET');
        $response = $resource->toArray($request);

        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('user', $response);
        $this->assertArrayHasKey('role', $response);
        $this->assertArrayHasKey('role_label', $response);
        $this->assertArrayHasKey('created_at', $response);
    }

    public function test_tenant_user_resource_returns_correct_user_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'is_active' => true,
        ]);

        $tenant = Tenant::factory()->create();

        $tenantUser = TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $resource = new TenantUserResource($tenantUser);
        $request = Request::create('/test', 'GET');
        $response = $resource->toArray($request);

        $this->assertEquals($user->id, $response['user']['id']);
        $this->assertEquals('Jane Smith', $response['user']['name']);
        $this->assertEquals('jane@example.com', $response['user']['email']);
        $this->assertTrue($response['user']['is_active']);
    }

    public function test_tenant_user_resource_returns_correct_role_data(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $tenantUser = TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Admin,
        ]);

        $resource = new TenantUserResource($tenantUser);
        $request = Request::create('/test', 'GET');
        $response = $resource->toArray($request);

        $this->assertEquals(TenantUserRole::Admin->value, $response['role']);
        $this->assertEquals('管理者', $response['role_label']);
    }

    public function test_tenant_user_resource_formats_created_at_as_iso_string(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $tenantUser = TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $resource = new TenantUserResource($tenantUser);
        $request = Request::create('/test', 'GET');
        $response = $resource->toArray($request);

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/',
            $response['created_at']
        );
    }

    public function test_tenant_user_resource_handles_staff_role(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $tenantUser = TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $resource = new TenantUserResource($tenantUser);
        $request = Request::create('/test', 'GET');
        $response = $resource->toArray($request);

        $this->assertEquals(TenantUserRole::Staff->value, $response['role']);
        $this->assertEquals('スタッフ', $response['role_label']);
    }

    public function test_tenant_user_resource_handles_inactive_user(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $tenant = Tenant::factory()->create();

        $tenantUser = TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Admin,
        ]);

        $resource = new TenantUserResource($tenantUser);
        $request = Request::create('/test', 'GET');
        $response = $resource->toArray($request);

        $this->assertFalse($response['user']['is_active']);
    }
}
