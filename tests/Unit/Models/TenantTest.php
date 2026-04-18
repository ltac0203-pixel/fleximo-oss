<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\TenantUserRole;
use App\Models\Tenant;
use App\Models\TenantBusinessHour;
use App\Models\TenantUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_be_created(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Test Restaurant',
            'slug' => 'test-restaurant',
        ]);

        $this->assertDatabaseHas('tenants', [
            'name' => 'Test Restaurant',
            'slug' => 'test-restaurant',
        ]);
    }

    public function test_tenant_slug_is_unique(): void
    {
        Tenant::factory()->create(['slug' => 'unique-slug']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Tenant::factory()->create(['slug' => 'unique-slug']);
    }

    public function test_tenant_is_active_returns_true_when_active(): void
    {
        $tenant = Tenant::factory()->create([
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->assertTrue($tenant->isActive());
    }

    public function test_tenant_is_active_returns_false_when_inactive(): void
    {
        $tenant = Tenant::factory()->create([
            'is_active' => false,
        ]);

        $this->assertFalse($tenant->isActive());
    }

    public function test_tenant_is_active_returns_false_when_suspended(): void
    {
        $tenant = Tenant::factory()->create([
            'is_active' => true,
            'status' => 'suspended',
        ]);

        $this->assertFalse($tenant->isActive());
    }

    public function test_active_scope_filters_inactive_tenants(): void
    {
        Tenant::factory()->create(['is_active' => true, 'status' => 'active']);
        Tenant::factory()->create(['is_active' => false, 'status' => 'active']);
        Tenant::factory()->create(['is_active' => true, 'status' => 'suspended']);

        $activeTenants = Tenant::active()->get();

        $this->assertCount(1, $activeTenants);
    }

    public function test_search_scope_filters_by_name(): void
    {
        Tenant::factory()->create(['name' => 'Cafe Sunshine']);
        Tenant::factory()->create(['name' => 'Restaurant Moon']);
        Tenant::factory()->create(['name' => 'Bakery Star']);

        $results = Tenant::search('Cafe')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Cafe Sunshine', $results->first()->name);
    }

    public function test_search_scope_filters_by_address(): void
    {
        Tenant::factory()->create(['name' => 'Restaurant A', 'address' => 'Tokyo Shibuya']);
        Tenant::factory()->create(['name' => 'Restaurant B', 'address' => 'Osaka Namba']);
        Tenant::factory()->create(['name' => 'Restaurant C', 'address' => 'Tokyo Shinjuku']);

        $results = Tenant::search('Tokyo')->get();

        $this->assertCount(2, $results);
    }

    public function test_search_scope_with_empty_keyword_returns_all(): void
    {
        Tenant::factory()->count(3)->create();

        $results = Tenant::search(null)->get();

        $this->assertCount(3, $results);
    }

    public function test_is_open_at_returns_true_during_business_hours(): void
    {
        $tenant = Tenant::factory()->create();
        // 2024-01-01 は月曜日（weekday=1）
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => 1,
            'open_time' => '09:00',
            'close_time' => '21:00',
            'sort_order' => 0,
        ]);

        $testTime = Carbon::parse('2024-01-01 15:00:00');

        $this->assertTrue($tenant->isOpenAt($testTime));
    }

    public function test_is_open_at_returns_false_before_opening(): void
    {
        $tenant = Tenant::factory()->create();
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => 1,
            'open_time' => '09:00',
            'close_time' => '21:00',
            'sort_order' => 0,
        ]);

        $testTime = Carbon::parse('2024-01-01 08:00:00');

        $this->assertFalse($tenant->isOpenAt($testTime));
    }

    public function test_is_open_at_returns_false_after_closing(): void
    {
        $tenant = Tenant::factory()->create();
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => 1,
            'open_time' => '09:00',
            'close_time' => '21:00',
            'sort_order' => 0,
        ]);

        $testTime = Carbon::parse('2024-01-01 22:00:00');

        $this->assertFalse($tenant->isOpenAt($testTime));
    }

    public function test_is_open_at_handles_overnight_hours(): void
    {
        $tenant = Tenant::factory()->create();
        // 2024-01-01 は月曜日（weekday=1）, 2024-01-02 is Tuesday (weekday=2)
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => 1,
            'open_time' => '18:00',
            'close_time' => '02:00',
            'sort_order' => 0,
        ]);

        $this->assertTrue($tenant->isOpenAt(Carbon::parse('2024-01-01 20:00:00')));

        $this->assertTrue($tenant->isOpenAt(Carbon::parse('2024-01-02 01:00:00')));

        $this->assertFalse($tenant->isOpenAt(Carbon::parse('2024-01-01 10:00:00')));
    }

    public function test_is_open_at_returns_false_when_no_business_hours_set(): void
    {
        $tenant = Tenant::factory()->create();

        $testTime = Carbon::parse('2024-01-01 03:00:00');

        $this->assertFalse($tenant->isOpenAt($testTime));
    }

    public function test_is_open_attribute_returns_current_status(): void
    {
        $tenant = Tenant::factory()->create();
        $weekday = Carbon::now()->dayOfWeek;
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => $weekday,
            'open_time' => '09:00',
            'close_time' => '21:00',
            'sort_order' => 0,
        ]);

        $isOpen = $tenant->is_open;

        $this->assertIsBool($isOpen);
    }

    public function test_get_business_status_uses_cache_for_same_second_even_if_relation_is_unset(): void
    {
        $tenant = Tenant::factory()->create();
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => 1,
            'open_time' => '09:00',
            'close_time' => '21:00',
            'sort_order' => 0,
        ]);

        $first = $tenant->getBusinessStatus(Carbon::parse('2024-01-01 10:00:30'));
        $tenant->unsetRelation('businessHours');

        DB::flushQueryLog();
        DB::enableQueryLog();
        $second = $tenant->getBusinessStatus(Carbon::parse('2024-01-01 10:00:30'));
        DB::disableQueryLog();

        $businessHourQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'tenant_business_hours'))
            ->count();

        $this->assertSame($first, $second);
        $this->assertSame(0, $businessHourQueries);
    }

    public function test_get_business_status_recalculates_for_different_second(): void
    {
        $tenant = Tenant::factory()->create();
        TenantBusinessHour::create([
            'tenant_id' => $tenant->id,
            'weekday' => 1,
            'open_time' => '09:00',
            'close_time' => '21:00',
            'sort_order' => 0,
        ]);

        $tenant->getBusinessStatus(Carbon::parse('2024-01-01 10:00:00'));
        $tenant->unsetRelation('businessHours');

        DB::flushQueryLog();
        DB::enableQueryLog();
        $tenant->getBusinessStatus(Carbon::parse('2024-01-01 10:00:01'));
        DB::disableQueryLog();

        $businessHourQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'tenant_business_hours'))
            ->count();

        $this->assertGreaterThanOrEqual(1, $businessHourQueries);
    }

    public function test_tenant_has_many_tenant_users(): void
    {
        $tenant = Tenant::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user1->id,
            'role' => TenantUserRole::Admin,
        ]);

        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user2->id,
            'role' => TenantUserRole::Staff,
        ]);

        $this->assertCount(2, $tenant->tenantUsers);
    }

    public function test_tenant_admins_returns_only_admin_users(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create();
        $staff = User::factory()->create();

        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'role' => TenantUserRole::Admin,
        ]);

        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $staff->id,
            'role' => TenantUserRole::Staff,
        ]);

        $admins = $tenant->admins;

        $this->assertCount(1, $admins);
        $this->assertEquals($admin->id, $admins->first()->id);
    }

    public function test_tenant_staff_returns_only_staff_users(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create();
        $staff = User::factory()->create();

        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'role' => TenantUserRole::Admin,
        ]);

        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $staff->id,
            'role' => TenantUserRole::Staff,
        ]);

        $staffUsers = $tenant->staff;

        $this->assertCount(1, $staffUsers);
        $this->assertEquals($staff->id, $staffUsers->first()->id);
    }

    public function test_tenant_all_staff_returns_both_admins_and_staff(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create();
        $staff = User::factory()->create();

        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'role' => TenantUserRole::Admin,
        ]);

        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $staff->id,
            'role' => TenantUserRole::Staff,
        ]);

        $allStaff = $tenant->allStaff;

        $this->assertCount(2, $allStaff);
        $this->assertTrue($allStaff->contains('id', $admin->id));
        $this->assertTrue($allStaff->contains('id', $staff->id));
    }
}
