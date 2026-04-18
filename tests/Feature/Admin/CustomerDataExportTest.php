<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDataExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->customer()->create([
            'is_active' => true,
        ]);
    }

    public function test_admin_can_export_customer_data_as_json(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.customers.export', [
                'customer' => $this->customer,
                'format' => 'json',
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/json');

        $content = json_decode($response->streamedContent(), true);
        $this->assertArrayHasKey('exported_at', $content);
        $this->assertArrayHasKey('profile', $content);
        $this->assertArrayHasKey('orders', $content);
        $this->assertArrayHasKey('favorites', $content);
    }

    public function test_admin_can_export_customer_data_as_csv(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.customers.export', [
                'customer' => $this->customer,
                'format' => 'csv',
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_exported_data_excludes_sensitive_fields(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.customers.export', [
                'customer' => $this->customer,
                'format' => 'json',
            ]));

        $response->assertOk();

        $content = json_decode($response->streamedContent(), true);
        $profile = $content['profile'];

        $this->assertArrayNotHasKey('password', $profile);
        $this->assertArrayNotHasKey('remember_token', $profile);
        $this->assertArrayNotHasKey('fincode_customer_id', $profile);
        $this->assertArrayNotHasKey('fincode_card_id', $profile);
        $this->assertArrayNotHasKey('fincode_id', $profile);
        $this->assertArrayNotHasKey('fincode_access_id', $profile);
    }

    public function test_export_creates_audit_log(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.customers.export', [
                'customer' => $this->customer,
                'format' => 'json',
            ]));

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::CustomerDataExported->value,
            'auditable_type' => User::class,
            'auditable_id' => $this->customer->id,
        ]);
    }

    public function test_non_admin_cannot_export(): void
    {
        $customerUser = User::factory()->customer()->create();

        $this->actingAs($customerUser)
            ->get(route('admin.customers.export', [
                'customer' => $this->customer,
                'format' => 'json',
            ]))
            ->assertForbidden();
    }
}
