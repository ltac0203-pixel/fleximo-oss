<?php

declare(strict_types=1);

namespace Tests\Unit\Services\TenantApplication\Commands;

use App\Enums\AuditAction;
use App\Enums\TenantApplicationStatus;
use App\Enums\TenantStatus;
use App\Mail\TenantApplicationApprovedMail;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\TenantApplication\Commands\ApproveTenantApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ApproveTenantApplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ApproveTenantApplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ApproveTenantApplicationService::class);
    }

    public function test_handle_approves_new_flow_application(): void
    {
        Mail::fake();

        $reviewer = User::factory()->admin()->create();
        $user = User::factory()->tenantAdmin()->create([
            'email' => 'new-flow@example.com',
            'is_active' => true,
        ]);
        $tenant = Tenant::factory()->create([
            'status' => TenantStatus::Pending,
            'is_active' => false,
            'is_approved' => false,
        ]);
        $application = TenantApplication::factory()->create([
            'status' => TenantApplicationStatus::Pending,
            'applicant_email' => $user->email,
            'created_tenant_id' => $tenant->id,
            'applicant_user_id' => $user->id,
        ]);
        $this->actingAs($reviewer);

        $result = $this->service->handle($application, $reviewer);

        $tenant->refresh();
        $this->assertEquals(TenantStatus::Active, $tenant->status);
        $this->assertTrue($tenant->is_active);
        $this->assertTrue($tenant->is_approved);
        $this->assertEquals(TenantApplicationStatus::Approved, $result->status);

        Mail::assertQueued(TenantApplicationApprovedMail::class, function (TenantApplicationApprovedMail $mail) use ($application, $tenant, $user) {
            return $mail->application->id === $application->id
                && $mail->tenant->id === $tenant->id
                && $mail->user->id === $user->id
                && $mail->requiresPasswordReset === false;
        });

        $log = AuditLog::where('action', AuditAction::TenantApplicationApproved->value)
            ->where('auditable_id', $application->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($tenant->id, $log->metadata['tenant_id']);
        $this->assertEquals($user->id, $log->metadata['user_id']);
    }

    public function test_handle_throws_exception_for_unapprovable_status(): void
    {
        $application = TenantApplication::factory()->approved()->create();
        $reviewer = User::factory()->admin()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('この申し込みは承認できません');

        $this->service->handle($application, $reviewer);
    }
}
