<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    use RefreshDatabase;

    protected TenantContext $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->context = app(TenantContext::class);
        $this->context->clear();
    }

    public function test_set_tenant_stores_tenant_id(): void
    {
        $this->context->setTenant(123);

        $this->assertEquals(123, $this->context->getTenantId());
    }

    public function test_has_tenant_returns_true_when_set(): void
    {
        $this->context->setTenant(1);

        $this->assertTrue($this->context->hasTenant());
    }

    public function test_has_tenant_returns_false_when_not_set(): void
    {
        $this->assertFalse($this->context->hasTenant());
    }

    public function test_get_tenant_returns_tenant_instance(): void
    {
        $tenant = Tenant::factory()->create();
        $this->context->setTenant($tenant->id);

        $result = $this->context->getTenant();

        $this->assertInstanceOf(Tenant::class, $result);
        $this->assertEquals($tenant->id, $result->id);
    }

    public function test_set_tenant_instance_stores_both_id_and_instance(): void
    {
        $tenant = Tenant::factory()->create();
        $this->context->setTenantInstance($tenant);

        $this->assertEquals($tenant->id, $this->context->getTenantId());
        $this->assertSame($tenant, $this->context->getTenant());
    }

    public function test_clear_resets_all_state(): void
    {
        $tenant = Tenant::factory()->create();
        $this->context->setTenantInstance($tenant);

        $this->context->clear();

        $this->assertNull($this->context->getTenantId());
        $this->assertNull($this->context->getTenant());
        $this->assertFalse($this->context->hasTenant());
    }

    public function test_context_is_accessible_via_alias(): void
    {
        $context = app('tenant.context');

        $this->assertInstanceOf(TenantContext::class, $context);
    }
}
