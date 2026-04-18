<?php

declare(strict_types=1);

namespace Tests\Unit\Services\TenantApplication;

use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\TenantApplication\TenantApplicationLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantApplicationLookupServiceTest extends TestCase
{
    use RefreshDatabase;

    private TenantApplicationLookupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TenantApplicationLookupService::class);
    }

    public function test_find_for_tenant_or_user_returns_application_for_matching_tenant_id(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $application = TenantApplication::factory()->create([
            'created_tenant_id' => $tenant->id,
            'applicant_user_id' => null,
        ]);

        $result = $this->service->findForTenantOrUser($tenant->id, $user->id);

        $this->assertNotNull($result);
        $this->assertSame($application->id, $result->id);
    }

    public function test_find_for_tenant_or_user_returns_application_for_matching_user_id(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $application = TenantApplication::factory()->create([
            'created_tenant_id' => null,
            'applicant_user_id' => $user->id,
        ]);

        $result = $this->service->findForTenantOrUser($tenant->id, $user->id);

        $this->assertNotNull($result);
        $this->assertSame($application->id, $result->id);
    }

    public function test_find_for_tenant_or_user_returns_null_when_no_match_exists(): void
    {
        TenantApplication::factory()->create();
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        $result = $this->service->findForTenantOrUser($tenant->id, $user->id);

        $this->assertNull($result);
    }
}
