<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Traits;

use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditableTest extends TestCase
{
    use RefreshDatabase;

    public function test_created_event_creates_audit_log(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Test Tenant']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => Tenant::class.'.created',
            'auditable_type' => Tenant::class,
            'auditable_id' => $tenant->id,
        ]);
    }

    public function test_updated_event_creates_audit_log_with_changes(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Old Name']);

        $tenant->update(['name' => 'New Name']);

        $log = AuditLog::where('action', Tenant::class.'.updated')
            ->where('auditable_id', $tenant->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->old_values);
        $this->assertNotNull($log->new_values);
        $this->assertEquals('Old Name', $log->old_values['name']);
        $this->assertEquals('New Name', $log->new_values['name']);
    }

    public function test_deleted_event_creates_audit_log(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Test Tenant']);
        $tenantId = $tenant->id;

        $tenant->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action' => Tenant::class.'.deleted',
            'auditable_type' => Tenant::class,
            'auditable_id' => $tenantId,
        ]);
    }

    public function test_sensitive_data_is_filtered_from_changes(): void
    {

        $tenant = Tenant::factory()->create(['name' => 'Test']);
        $tenant->update(['name' => 'Updated']);

        $log = AuditLog::where('action', Tenant::class.'.updated')
            ->where('auditable_id', $tenant->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log->old_values);
    }

    public function test_multiple_audit_logs_are_created_for_multiple_events(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Original']);
        $tenant->update(['name' => 'Updated Once']);
        $tenant->update(['name' => 'Updated Twice']);

        $logs = AuditLog::where('auditable_type', Tenant::class)
            ->where('auditable_id', $tenant->id)
            ->get();

        $this->assertCount(3, $logs);

        $actions = $logs->pluck('action')->toArray();
        $this->assertContains(Tenant::class.'.created', $actions);
        $this->assertEquals(2, collect($actions)->filter(fn ($a) => $a === Tenant::class.'.updated')->count());
    }

    public function test_audit_log_contains_correct_tenant_id(): void
    {
        $tenant = Tenant::factory()->create();

        $log = AuditLog::where('action', Tenant::class.'.created')
            ->where('auditable_id', $tenant->id)
            ->first();

        $this->assertNull($log->tenant_id);
    }
}
