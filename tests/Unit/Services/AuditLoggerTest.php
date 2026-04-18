<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\AuditAction;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_creates_audit_log(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $log = AuditLogger::log('test.action', null, ['metadata' => ['key' => 'value']]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'test.action',
        ]);

        $this->assertEquals(['key' => 'value'], $log->metadata);
    }

    public function test_log_records_ip_address_and_user_agent(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        request()->server->set('REMOTE_ADDR', '192.168.1.1');
        request()->headers->set('User-Agent', 'Test Browser');

        $log = AuditLogger::log('test.action');

        $this->assertEquals('192.168.1.1', $log->ip_address);
        $this->assertEquals('Test Browser', $log->user_agent);
    }

    public function test_log_with_target_model(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        $this->actingAs($user);

        $log = AuditLogger::log('test.action', $tenant);

        $this->assertEquals(Tenant::class, $log->auditable_type);
        $this->assertEquals($tenant->id, $log->auditable_id);
    }

    public function test_log_with_explicit_tenant_id(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        $this->actingAs($user);

        $log = AuditLogger::log('test.action', null, null, $tenant->id);

        $this->assertEquals($tenant->id, $log->tenant_id);
    }

    public function test_log_model_event_created(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Test Tenant']);

        $log = AuditLogger::logModelEvent('created', $tenant);

        $this->assertEquals(Tenant::class.'.created', $log->action);
        $this->assertNotNull($log->new_values);
        $this->assertEquals('Test Tenant', $log->new_values['name']);
    }

    public function test_log_model_event_updated(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Old Name']);
        $oldAttributes = $tenant->getAttributes();

        $tenant->update(['name' => 'New Name']);

        $log = AuditLogger::logModelEvent('updated', $tenant, $oldAttributes);

        $this->assertEquals(Tenant::class.'.updated', $log->action);
        $this->assertNotNull($log->old_values);
        $this->assertNotNull($log->new_values);
        $this->assertEquals('Old Name', $log->old_values['name']);
        $this->assertEquals('New Name', $log->new_values['name']);
    }

    public function test_log_model_event_deleted(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Test Tenant']);
        $attributes = $tenant->getAttributes();

        $log = AuditLogger::logModelEvent('deleted', $tenant);

        $this->assertEquals(Tenant::class.'.deleted', $log->action);
        $this->assertNotNull($log->old_values);
    }

    public function test_log_with_audit_action_enum(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        $this->actingAs($user);

        $log = AuditLogger::log(
            action: AuditAction::StaffCreated,
            target: $user,
            tenantId: $tenant->id
        );

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'staff.created',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_log_without_authenticated_user(): void
    {
        $log = AuditLogger::log('test.action');

        $this->assertNull($log->user_id);
        $this->assertEquals('test.action', $log->action);
    }
}
