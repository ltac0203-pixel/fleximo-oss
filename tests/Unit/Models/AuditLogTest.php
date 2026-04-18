<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->clear();
    }

    public function test_audit_log_can_be_created(): void
    {
        $log = AuditLog::create([
            'action' => 'test.action',
            'created_at' => now(),
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'test.action',
        ]);
    }

    public function test_tenant_id_is_automatically_set_when_tenant_context_exists(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        $log = AuditLog::create([
            'action' => 'test.action.with-context',
            'created_at' => now(),
        ]);

        $this->assertEquals($tenant->id, $log->tenant_id);
    }

    public function test_query_is_scoped_to_current_tenant(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        AuditLog::create([
            'tenant_id' => $tenant1->id,
            'action' => 'audit.scope.tenant1',
            'created_at' => now(),
        ]);

        AuditLog::create([
            'tenant_id' => $tenant2->id,
            'action' => 'audit.scope.tenant2',
            'created_at' => now(),
        ]);

        app(TenantContext::class)->setTenant($tenant1->id);

        $logs = AuditLog::whereIn('action', ['audit.scope.tenant1', 'audit.scope.tenant2'])
            ->orderBy('id')
            ->get();

        $this->assertCount(1, $logs);
        $this->assertEquals($tenant1->id, $logs->first()->tenant_id);
        $this->assertEquals('audit.scope.tenant1', $logs->first()->action);
    }

    public function test_query_without_tenant_context_returns_all_tenants(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $tenant1Log = AuditLog::create([
            'tenant_id' => $tenant1->id,
            'action' => 'audit.no-context.tenant1',
            'created_at' => now(),
        ]);

        $tenant2Log = AuditLog::create([
            'tenant_id' => $tenant2->id,
            'action' => 'audit.no-context.tenant2',
            'created_at' => now(),
        ]);

        app(TenantContext::class)->clear();

        $logs = AuditLog::whereIn('action', ['audit.no-context.tenant1', 'audit.no-context.tenant2'])
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $logs);
        $this->assertEqualsCanonicalizing([$tenant1Log->id, $tenant2Log->id], $logs->pluck('id')->all());
    }

    public function test_user_relationship(): void
    {
        $user = User::factory()->create();

        $log = AuditLog::create([
            'user_id' => $user->id,
            'action' => 'test.action',
            'created_at' => now(),
        ]);

        $this->assertNotNull($log->user);
        $this->assertEquals($user->id, $log->user->id);
    }

    public function test_tenant_relationship(): void
    {
        $tenant = Tenant::factory()->create();

        $log = AuditLog::create([
            'tenant_id' => $tenant->id,
            'action' => 'test.action',
            'created_at' => now(),
        ]);

        $this->assertNotNull($log->tenant);
        $this->assertEquals($tenant->id, $log->tenant->id);
    }

    public function test_changes_cast_to_array(): void
    {
        $oldValues = ['field' => 'value1'];
        $newValues = ['field' => 'value2'];
        $metadata = ['source' => 'test'];

        $log = AuditLog::create([
            'action' => 'test.action',
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);

        $this->assertIsArray($log->old_values);
        $this->assertIsArray($log->new_values);
        $this->assertIsArray($log->metadata);
        $this->assertEquals($oldValues, $log->old_values);
        $this->assertEquals($newValues, $log->new_values);
        $this->assertEquals($metadata, $log->metadata);
    }

    public function test_auditable_polymorphic_relationship(): void
    {
        $tenant = Tenant::factory()->create();

        $log = AuditLog::create([
            'action' => 'test.action',
            'auditable_type' => Tenant::class,
            'auditable_id' => $tenant->id,
            'created_at' => now(),
        ]);

        $this->assertNotNull($log->auditable);
        $this->assertInstanceOf(Tenant::class, $log->auditable);
        $this->assertEquals($tenant->id, $log->auditable->id);
    }

    public function test_nullable_fields_are_optional(): void
    {
        $log = AuditLog::create([
            'action' => 'test.action',
            'created_at' => now(),
        ]);

        $this->assertNull($log->user_id);
        $this->assertNull($log->tenant_id);
        $this->assertNull($log->auditable_type);
        $this->assertNull($log->auditable_id);
        $this->assertNull($log->old_values);
        $this->assertNull($log->new_values);
        $this->assertNull($log->metadata);
        $this->assertNull($log->ip_address);
        $this->assertNull($log->user_agent);
    }

    public function test_ip_address_and_user_agent_can_be_stored(): void
    {
        $log = AuditLog::create([
            'action' => 'test.action',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'created_at' => now(),
        ]);

        $this->assertEquals('192.168.1.1', $log->ip_address);
        $this->assertEquals('Mozilla/5.0', $log->user_agent);
    }
}
