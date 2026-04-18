<?php

declare(strict_types=1);

namespace Tests\Unit\Services\TenantApplication\Commands;

use App\Enums\AuditAction;
use App\Enums\TenantApplicationStatus;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\TenantApplication\Commands\StartReviewTenantApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartReviewTenantApplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    private StartReviewTenantApplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(StartReviewTenantApplicationService::class);
    }

    public function test_handle_starts_review_and_creates_audit_log(): void
    {
        $application = TenantApplication::factory()->create([
            'status' => TenantApplicationStatus::Pending,
        ]);
        $reviewer = User::factory()->admin()->create();
        $this->actingAs($reviewer);

        $result = $this->service->handle($application, $reviewer);

        $this->assertEquals(TenantApplicationStatus::UnderReview, $result->status);
        $this->assertEquals($reviewer->id, $result->reviewed_by);
        $this->assertNotNull($result->reviewed_at);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $reviewer->id,
            'action' => AuditAction::TenantApplicationReviewStarted->value,
            'auditable_type' => TenantApplication::class,
            'auditable_id' => $application->id,
        ]);
    }

    public function test_handle_throws_exception_for_non_pending_application(): void
    {
        $application = TenantApplication::factory()->underReview()->create();
        $reviewer = User::factory()->admin()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('この申し込みは審査開始できません');

        $this->service->handle($application, $reviewer);
    }
}
