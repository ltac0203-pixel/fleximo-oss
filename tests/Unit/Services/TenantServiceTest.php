<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\Tenant\UpdateTenantProfileData;
use App\Http\Requests\SearchTenantsRequest;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantServiceTest extends TestCase
{
    use RefreshDatabase;

    private TenantService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TenantService;
    }

    public function test_search_returns_only_active_tenants(): void
    {
        Tenant::factory()->count(2)->create();
        Tenant::factory()->inactive()->create();
        Tenant::factory()->suspended()->create();

        $request = $this->createSearchRequest();

        $result = $this->service->search($request);

        $this->assertCount(2, $result);
    }

    public function test_search_filters_by_keyword(): void
    {
        Tenant::factory()->create(['name' => 'カフェレストラン']);
        Tenant::factory()->create(['name' => '寿司屋']);
        Tenant::factory()->create(['name' => 'カフェバー']);

        $request = $this->createSearchRequest(['query' => 'カフェ']);

        $result = $this->service->search($request);

        $this->assertCount(2, $result);
    }

    public function test_search_orders_by_name(): void
    {
        Tenant::factory()->create(['name' => 'Cカフェ']);
        Tenant::factory()->create(['name' => 'Aレストラン']);
        Tenant::factory()->create(['name' => 'Bバー']);

        $request = $this->createSearchRequest();

        $result = $this->service->search($request);

        $names = $result->pluck('name')->toArray();
        $this->assertEquals(['Aレストラン', 'Bバー', 'Cカフェ'], $names);
    }

    public function test_search_respects_per_page_parameter(): void
    {
        Tenant::factory()->count(25)->create();

        $request = $this->createSearchRequest(['per_page' => 10]);

        $result = $this->service->search($request);

        $this->assertCount(10, $result);
        $this->assertEquals(25, $result->total());
    }

    public function test_search_uses_default_per_page_when_not_specified(): void
    {
        Tenant::factory()->count(25)->create();

        $request = $this->createSearchRequest();

        $result = $this->service->search($request);

        $this->assertCount(20, $result);
    }

    public function test_search_eager_loads_business_hours(): void
    {
        Tenant::factory()->count(3)->create();

        $request = $this->createSearchRequest();
        $result = $this->service->search($request);

        $allBusinessHoursLoaded = $result->getCollection()
            ->every(fn (Tenant $tenant): bool => $tenant->relationLoaded('businessHours'));

        $this->assertTrue($allBusinessHoursLoaded);
    }

    public function test_update_profile_updates_tenant_data(): void
    {
        $tenant = Tenant::factory()->create(['name' => '元の名前']);
        $this->actAsAdmin();

        $result = $this->service->updateProfile($tenant, new UpdateTenantProfileData(name: '新しい名前', presentFields: ['name']));

        $this->assertEquals('新しい名前', $result->name);
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => '新しい名前',
        ]);
    }

    public function test_update_profile_invalidates_cache(): void
    {
        $tenant = Tenant::factory()->create();
        $this->actAsAdmin();

        $cacheKey = "tenant:{$tenant->id}:profile";
        Cache::put($cacheKey, 'cached_value', 3600);

        $this->service->updateProfile($tenant, new UpdateTenantProfileData(name: '新しい名前', presentFields: ['name']));

        $this->assertNull(Cache::get($cacheKey));
    }

    public function test_update_profile_creates_audit_log(): void
    {
        $tenant = Tenant::factory()->create(['name' => '元の名前']);
        $user = $this->actAsAdmin();

        $this->service->updateProfile($tenant, new UpdateTenantProfileData(name: '新しい名前', presentFields: ['name']));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'action' => 'tenant.updated',
            'auditable_type' => Tenant::class,
            'auditable_id' => $tenant->id,
        ]);
    }

    public function test_update_profile_records_old_and_new_values(): void
    {
        $tenant = Tenant::factory()->create(['name' => '元の名前']);
        $this->actAsAdmin();

        $this->service->updateProfile($tenant, new UpdateTenantProfileData(name: '新しい名前', presentFields: ['name']));

        $auditLog = AuditLog::where('action', 'tenant.updated')->first();

        $this->assertEquals('元の名前', $auditLog->old_values['name']);
        $this->assertEquals('新しい名前', $auditLog->new_values['name']);
    }

    public function test_update_profile_uses_transaction(): void
    {
        $tenant = Tenant::factory()->create(['name' => '元の名前']);
        $this->actAsAdmin();

        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception('Transaction failed'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Transaction failed');

        $this->service->updateProfile($tenant, new UpdateTenantProfileData(name: '新しい名前', presentFields: ['name']));
    }

    public function test_update_profile_returns_updated_instance(): void
    {
        $tenant = Tenant::factory()->create(['name' => '元の名前']);
        $this->actAsAdmin();

        $result = $this->service->updateProfile($tenant, new UpdateTenantProfileData(name: '新しい名前', presentFields: ['name']));

        $this->assertEquals('新しい名前', $result->name);
    }

    private function createSearchRequest(array $data = []): SearchTenantsRequest
    {
        $request = new SearchTenantsRequest;
        $request->merge($data);

        return $request;
    }

    private function actAsAdmin(): User
    {
        $user = User::factory()->tenantAdmin()->create();
        $this->actingAs($user);

        return $user;
    }
}
