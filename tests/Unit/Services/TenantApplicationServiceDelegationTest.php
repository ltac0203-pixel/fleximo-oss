<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\TenantApplication;
use App\Models\User;
use App\Services\TenantApplication\Commands\ApproveTenantApplicationService;
use App\Services\TenantApplication\Commands\RejectTenantApplicationService;
use App\Services\TenantApplication\Commands\StartReviewTenantApplicationService;
use App\Services\TenantApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantApplicationServiceDelegationTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_review_delegates_to_start_review_command_service(): void
    {
        $application = TenantApplication::factory()->create();
        $reviewer = User::factory()->admin()->create();

        $this->mock(StartReviewTenantApplicationService::class, function ($mock) use ($application, $reviewer) {
            $mock->shouldReceive('handle')
                ->once()
                ->with($application, $reviewer)
                ->andReturn($application);
        });

        $service = app(TenantApplicationService::class);
        $result = $service->startReview($application, $reviewer);

        $this->assertSame($application, $result);
    }

    public function test_approve_delegates_to_approve_command_service(): void
    {
        $application = TenantApplication::factory()->create();
        $reviewer = User::factory()->admin()->create();

        $this->mock(ApproveTenantApplicationService::class, function ($mock) use ($application, $reviewer) {
            $mock->shouldReceive('handle')
                ->once()
                ->with($application, $reviewer)
                ->andReturn($application);
        });

        $service = app(TenantApplicationService::class);
        $result = $service->approve($application, $reviewer);

        $this->assertSame($application, $result);
    }

    public function test_reject_delegates_to_reject_command_service(): void
    {
        $application = TenantApplication::factory()->create();
        $reviewer = User::factory()->admin()->create();
        $reason = '却下理由';

        $this->mock(RejectTenantApplicationService::class, function ($mock) use ($application, $reviewer, $reason) {
            $mock->shouldReceive('handle')
                ->once()
                ->with($application, $reviewer, $reason)
                ->andReturn($application);
        });

        $service = app(TenantApplicationService::class);
        $result = $service->reject($application, $reviewer, $reason);

        $this->assertSame($application, $result);
    }
}
