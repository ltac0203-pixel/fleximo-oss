<?php

declare(strict_types=1);

namespace Tests\Unit\Services\TenantApplication\Commands;

use App\Enums\AuditAction;
use App\Enums\TenantApplicationStatus;
use App\Enums\TenantStatus;
use App\Mail\TenantApplicationRejectedMail;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\TenantApplication\Commands\RejectTenantApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RejectTenantApplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RejectTenantApplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RejectTenantApplicationService::class);
    }

    public function test_handle_rejects_application_and_deactivates_precreated_assets(): void
    {
        Mail::fake();

        $reviewer = User::factory()->admin()->create();
        $user = User::factory()->tenantAdmin()->create([
            'email' => 'reject-target@example.com',
            'is_active' => true,
        ]);
        $tenant = Tenant::factory()->create([
            'status' => TenantStatus::Pending,
            'is_active' => true,
            'is_approved' => false,
        ]);
        $application = TenantApplication::factory()->create([
            'status' => TenantApplicationStatus::Pending,
            'applicant_email' => $user->email,
            'created_tenant_id' => $tenant->id,
            'applicant_user_id' => $user->id,
        ]);
        $reason = '審査基準を満たしませんでした';
        $this->actingAs($reviewer);

        $result = $this->service->handle($application, $reviewer, $reason);

        $tenant->refresh();
        $user->refresh();

        $this->assertEquals(TenantApplicationStatus::Rejected, $result->status);
        $this->assertEquals($reason, $result->rejection_reason);
        $this->assertEquals(TenantStatus::Rejected, $tenant->status);
        $this->assertFalse($tenant->is_active);
        $this->assertFalse($user->is_active);

        Mail::assertQueued(TenantApplicationRejectedMail::class, function (TenantApplicationRejectedMail $mail) use ($application) {
            return $mail->application->id === $application->id;
        });

        $log = AuditLog::where('action', AuditAction::TenantApplicationRejected->value)
            ->where('auditable_id', $application->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($reason, $log->metadata['rejection_reason']);
    }

    public function test_handle_rejects_legacy_application_without_related_assets(): void
    {
        Mail::fake();

        $reviewer = User::factory()->admin()->create();
        $application = TenantApplication::factory()->create([
            'status' => TenantApplicationStatus::UnderReview,
            'created_tenant_id' => null,
            'applicant_user_id' => null,
        ]);
        $this->actingAs($reviewer);

        $result = $this->service->handle($application, $reviewer, '却下理由');

        $this->assertEquals(TenantApplicationStatus::Rejected, $result->status);
        $this->assertEquals($reviewer->id, $result->reviewed_by);
        Mail::assertQueued(TenantApplicationRejectedMail::class);
    }

    public function test_handle_throws_exception_for_unrejectable_status(): void
    {
        $reviewer = User::factory()->admin()->create();
        $application = TenantApplication::factory()->rejected()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('この申し込みは却下できません');

        $this->service->handle($application, $reviewer, '理由');
    }
}
