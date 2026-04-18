<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\MetricType;
use App\Models\AnalyticsCache;
use App\Models\Tenant;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(TenantContext::class)->clear();
    }

    public function test_all_returns_only_current_tenant_records_when_tenant_context_is_set(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $this->seedAnalyticsCacheRecords($tenant1->id, $tenant2->id);

        app(TenantContext::class)->setTenant($tenant1->id);

        $records = AnalyticsCache::all();

        $this->assertCount(1, $records);
        $this->assertTrue($records->every(fn (AnalyticsCache $cache): bool => $cache->tenant_id === $tenant1->id));
    }

    public function test_all_returns_all_records_when_tenant_context_is_missing(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $this->seedAnalyticsCacheRecords($tenant1->id, $tenant2->id);

        app(TenantContext::class)->clear();

        $records = AnalyticsCache::all();

        $this->assertCount(3, $records);
        $this->assertEqualsCanonicalizing(
            [$tenant1->id, $tenant2->id, null],
            $records->pluck('tenant_id')->all()
        );
    }

    public function test_for_tenant_scope_returns_explicit_tenant_records_without_context(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $this->seedAnalyticsCacheRecords($tenant1->id, $tenant2->id);

        app(TenantContext::class)->clear();

        $records = AnalyticsCache::forTenant($tenant1->id)->get();

        $this->assertCount(1, $records);
        $this->assertTrue($records->every(fn (AnalyticsCache $cache): bool => $cache->tenant_id === $tenant1->id));
    }

    public function test_for_tenant_scope_can_target_platform_records_with_tenant_context(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $this->seedAnalyticsCacheRecords($tenant1->id, $tenant2->id);

        app(TenantContext::class)->setTenant($tenant1->id);

        $records = AnalyticsCache::forTenant(null)->get();

        $this->assertCount(1, $records);
        $this->assertTrue($records->every(fn (AnalyticsCache $cache): bool => $cache->tenant_id === null));
    }

    public function test_for_platform_scope_returns_only_platform_records_without_context(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $this->seedAnalyticsCacheRecords($tenant1->id, $tenant2->id);

        app(TenantContext::class)->clear();

        $records = AnalyticsCache::forPlatform()->get();

        $this->assertCount(1, $records);
        $this->assertTrue($records->every(fn (AnalyticsCache $cache): bool => $cache->tenant_id === null));
    }

    public function test_for_all_tenants_scope_returns_all_records_regardless_of_context(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $this->seedAnalyticsCacheRecords($tenant1->id, $tenant2->id);

        app(TenantContext::class)->setTenant($tenant1->id);

        $records = AnalyticsCache::forAllTenants()->get();

        $this->assertCount(3, $records);
    }

    private function seedAnalyticsCacheRecords(int $tenant1Id, int $tenant2Id): void
    {
        AnalyticsCache::withoutGlobalScopes()->create([
            'tenant_id' => $tenant1Id,
            'metric_type' => MetricType::DailySales->value,
            'date' => Carbon::parse('2026-01-01')->toDateString(),
            'data' => ['total_sales' => 1000],
        ]);

        AnalyticsCache::withoutGlobalScopes()->create([
            'tenant_id' => $tenant2Id,
            'metric_type' => MetricType::MonthlySales->value,
            'date' => Carbon::parse('2026-01-01')->toDateString(),
            'data' => ['total_sales' => 2000],
        ]);

        AnalyticsCache::withoutGlobalScopes()->create([
            'tenant_id' => null,
            'metric_type' => MetricType::TopMenuItems->value,
            'date' => Carbon::parse('2026-01-01')->toDateString(),
            'data' => ['items' => []],
        ]);
    }
}
