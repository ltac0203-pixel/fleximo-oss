<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KdsPageTest extends TestCase
{
    use RefreshDatabase;

    private User $tenantAdmin;

    private User $tenantStaff;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->clear();

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

        $this->tenantStaff = User::factory()->create([
            'role' => UserRole::TenantStaff,
        ]);

        TenantUser::factory()->create([
            'user_id' => $this->tenantStaff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);
        $this->tenantStaff->refresh();
    }

    public function test_tenant_admin_can_access_kds(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.kds'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Tenant/Kds/Index')
                ->has('orders')
                ->has('businessDate')
        );
    }

    public function test_tenant_staff_can_access_kds(): void
    {
        $response = $this->actingAs($this->tenantStaff)
            ->get(route('tenant.kds'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Tenant/Kds/Index')
                ->has('orders')
                ->has('businessDate')
        );
    }

    public function test_customer_cannot_access_kds(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)
            ->get(route('tenant.kds'))
            ->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('tenant.kds'))
            ->assertRedirect(route('login'));
    }

    public function test_only_active_orders_are_returned(): void
    {

        $acceptedOrder = Order::factory()
            ->forTenant($this->tenant)
            ->accepted()
            ->create();

        $inProgressOrder = Order::factory()
            ->forTenant($this->tenant)
            ->inProgress()
            ->create();

        Order::factory()
            ->forTenant($this->tenant)
            ->completed()
            ->create();

        Order::factory()
            ->forTenant($this->tenant)
            ->pendingPayment()
            ->create();

        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.kds'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Tenant/Kds/Index')
                ->has('orders', 2)
        );

        $orderIds = collect($response->viewData('page')['props']['orders'])
            ->pluck('id')
            ->toArray();

        $this->assertContains($acceptedOrder->id, $orderIds);
        $this->assertContains($inProgressOrder->id, $orderIds);
    }

    public function test_other_tenant_orders_are_not_visible(): void
    {
        $otherTenant = Tenant::factory()->create([
            'name' => 'Other Restaurant',
            'slug' => 'other-restaurant',
        ]);

        $myOrder = Order::factory()
            ->forTenant($this->tenant)
            ->accepted()
            ->create();

        Order::factory()
            ->forTenant($otherTenant)
            ->accepted()
            ->create();

        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.kds'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Tenant/Kds/Index')
                ->has('orders', 1)
        );

        $orderIds = collect($response->viewData('page')['props']['orders'])
            ->pluck('id')
            ->toArray();

        $this->assertContains($myOrder->id, $orderIds);
    }

    public function test_orders_are_filtered_by_business_date(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');

        $todayOrder = Order::factory()
            ->forTenant($this->tenant)
            ->accepted()
            ->forBusinessDate($today)
            ->create();

        Order::factory()
            ->forTenant($this->tenant)
            ->accepted()
            ->forBusinessDate($yesterday)
            ->create();

        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.kds'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Tenant/Kds/Index')
                ->has('orders', 1)
        );

        $orderIds = collect($response->viewData('page')['props']['orders'])
            ->pluck('id')
            ->toArray();

        $this->assertContains($todayOrder->id, $orderIds);
    }

    public function test_business_date_is_returned(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.kds'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Tenant/Kds/Index')
                ->where('businessDate', Carbon::today()->toDateString())
        );
    }
}
