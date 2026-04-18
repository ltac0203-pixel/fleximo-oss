<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\FincodeCustomer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FincodeCustomerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->clear();
    }

    public function test_tenant_scope_is_applied_to_all_queries(): void
    {
        $user = User::factory()->create();
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        FincodeCustomer::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant1->id,
        ]);
        FincodeCustomer::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant2->id,
        ]);

        app(TenantContext::class)->setTenant($tenant1->id);
        $customers = FincodeCustomer::all();

        $this->assertCount(1, $customers);
        $this->assertEquals($tenant1->id, $customers->first()->tenant_id);
    }

    public function test_tenant_scope_is_applied_to_filtered_queries(): void
    {
        $user = User::factory()->create();
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        FincodeCustomer::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant1->id,
        ]);
        FincodeCustomer::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant2->id,
        ]);

        app(TenantContext::class)->setTenant($tenant1->id);
        $customers = FincodeCustomer::where('user_id', $user->id)->get();

        $this->assertCount(1, $customers);
        $this->assertEquals($tenant1->id, $customers->first()->tenant_id);
    }

    public function test_tenant_id_is_automatically_set_on_create_when_context_exists(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        app(TenantContext::class)->setTenant($tenant->id);
        $customer = FincodeCustomer::create([
            'user_id' => $user->id,
            'fincode_customer_id' => 'cus_auto_tenant',
        ]);

        $this->assertEquals($tenant->id, $customer->tenant_id);
    }

    public function test_tenant_id_is_not_overwritten_when_explicitly_set(): void
    {
        $tenantFromContext = Tenant::factory()->create();
        $explicitTenant = Tenant::factory()->create();
        $user = User::factory()->create();

        app(TenantContext::class)->setTenant($tenantFromContext->id);
        $customer = FincodeCustomer::create([
            'user_id' => $user->id,
            'tenant_id' => $explicitTenant->id,
            'fincode_customer_id' => 'cus_explicit_tenant',
        ]);

        $this->assertEquals($explicitTenant->id, $customer->tenant_id);
    }

    public function test_find_by_user_and_tenant_returns_matching_record(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $target = FincodeCustomer::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        FincodeCustomer::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $otherTenant->id,
        ]);
        FincodeCustomer::factory()->create([
            'user_id' => $otherUser->id,
            'tenant_id' => $tenant->id,
        ]);

        $found = FincodeCustomer::findByUserAndTenant($user, $tenant);

        $this->assertNotNull($found);
        $this->assertTrue($target->is($found));
    }

    public function test_find_by_user_and_tenant_returns_null_when_no_matching_customer_exists(): void
    {
        $tenant = Tenant::factory()->create();
        $userWithCustomer = User::factory()->create();
        $userWithoutCustomer = User::factory()->create();

        FincodeCustomer::factory()->create([
            'user_id' => $userWithCustomer->id,
            'tenant_id' => $tenant->id,
        ]);

        $found = FincodeCustomer::findByUserAndTenant($userWithoutCustomer, $tenant);

        $this->assertNull($found);
    }

    public function test_find_by_user_and_tenant_returns_null_for_deleted_user_or_deleted_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        FincodeCustomer::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $deletedUser = User::factory()->create();
        $deletedUser->delete();

        $deletedTenant = Tenant::factory()->create();
        $deletedTenant->delete();

        $this->assertNull(FincodeCustomer::findByUserAndTenant($deletedUser, $tenant));
        $this->assertNull(FincodeCustomer::findByUserAndTenant($user, $deletedTenant));
    }
}
