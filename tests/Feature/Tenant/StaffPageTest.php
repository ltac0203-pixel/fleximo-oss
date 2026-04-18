<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPageTest extends TestCase
{
    use RefreshDatabase;

    private User $tenantAdmin;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Restaurant',
            'slug' => 'test-restaurant',
        ]);

        $this->tenantAdmin = User::factory()->create([
            'role' => UserRole::TenantAdmin,
        ]);

        TenantUser::factory()->create([
            'user_id' => $this->tenantAdmin->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Admin,
        ]);
        $this->tenantAdmin->refresh();
    }

    public function test_tenant_admin_can_view_staff_page(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.staff.page'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Tenant/Staff/Index')
                ->has('staff')
        );
    }

    public function test_staff_list_includes_tenant_staff(): void
    {
        $newStaff = User::factory()->create([
            'name' => 'Test Staff',
            'email' => 'staff@example.com',
            'role' => UserRole::TenantStaff,
        ]);

        TenantUser::factory()->create([
            'user_id' => $newStaff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.staff.page'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Tenant/Staff/Index')
                ->has('staff', 2)
        );

        $staffList = $response->viewData('page')['props']['staff'];
        $staffEmails = array_column($staffList, 'email');
        $this->assertContains('staff@example.com', $staffEmails);
    }

    public function test_tenant_staff_can_access_staff_page(): void
    {
        $staff = User::factory()->create(['role' => UserRole::TenantStaff]);

        TenantUser::factory()->create([
            'user_id' => $staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);
        $staff->refresh();

        $response = $this->actingAs($staff)
            ->get(route('tenant.staff.page'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Tenant/Staff/Index')
                ->has('staff')
        );
    }

    public function test_customer_cannot_access_staff_page(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)
            ->get(route('tenant.staff.page'))
            ->assertForbidden();
    }

    public function test_other_tenant_staff_not_visible(): void
    {
        $otherTenant = Tenant::factory()->create([
            'name' => 'Other Restaurant',
            'slug' => 'other-restaurant',
        ]);

        $otherStaff = User::factory()->create([
            'name' => 'Other Staff',
            'email' => 'other@example.com',
            'role' => UserRole::TenantStaff,
        ]);

        TenantUser::factory()->create([
            'user_id' => $otherStaff->id,
            'tenant_id' => $otherTenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.staff.page'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Tenant/Staff/Index')
                ->has('staff', 1)
        );
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('tenant.staff.page'))
            ->assertRedirect(route('login'));
    }
}
