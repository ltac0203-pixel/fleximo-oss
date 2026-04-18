<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\TenantApplication;
use App\Models\User;
use App\Policies\TenantApplicationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantApplicationPolicyTest extends TestCase
{
    use RefreshDatabase;

    private TenantApplicationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new TenantApplicationPolicy;
    }

    // admin は全メソッドで許可される
    public function test_admin_can_view_any(): void
    {
        $admin = User::factory()->admin()->create();
        $this->assertTrue($this->policy->viewAny($admin));
    }

    public function test_admin_can_view(): void
    {
        $admin = User::factory()->admin()->create();
        $application = TenantApplication::factory()->create();
        $this->assertTrue($this->policy->view($admin, $application));
    }

    public function test_admin_can_start_review(): void
    {
        $admin = User::factory()->admin()->create();
        $application = TenantApplication::factory()->create();
        $this->assertTrue($this->policy->startReview($admin, $application));
    }

    public function test_admin_can_approve(): void
    {
        $admin = User::factory()->admin()->create();
        $application = TenantApplication::factory()->create();
        $this->assertTrue($this->policy->approve($admin, $application));
    }

    public function test_admin_can_reject(): void
    {
        $admin = User::factory()->admin()->create();
        $application = TenantApplication::factory()->create();
        $this->assertTrue($this->policy->reject($admin, $application));
    }

    public function test_admin_can_update_notes(): void
    {
        $admin = User::factory()->admin()->create();
        $application = TenantApplication::factory()->create();
        $this->assertTrue($this->policy->updateNotes($admin, $application));
    }

    // tenant_admin は全メソッドで拒否される
    public function test_tenant_admin_is_denied_all_actions(): void
    {
        $tenantAdmin = User::factory()->tenantAdmin()->create();
        $application = TenantApplication::factory()->create();

        $this->assertFalse($this->policy->viewAny($tenantAdmin));
        $this->assertFalse($this->policy->view($tenantAdmin, $application));
        $this->assertFalse($this->policy->startReview($tenantAdmin, $application));
        $this->assertFalse($this->policy->approve($tenantAdmin, $application));
        $this->assertFalse($this->policy->reject($tenantAdmin, $application));
        $this->assertFalse($this->policy->updateNotes($tenantAdmin, $application));
    }

    // tenant_staff は全メソッドで拒否される
    public function test_tenant_staff_is_denied_all_actions(): void
    {
        $tenantStaff = User::factory()->tenantStaff()->create();
        $application = TenantApplication::factory()->create();

        $this->assertFalse($this->policy->viewAny($tenantStaff));
        $this->assertFalse($this->policy->view($tenantStaff, $application));
        $this->assertFalse($this->policy->startReview($tenantStaff, $application));
        $this->assertFalse($this->policy->approve($tenantStaff, $application));
        $this->assertFalse($this->policy->reject($tenantStaff, $application));
        $this->assertFalse($this->policy->updateNotes($tenantStaff, $application));
    }

    // customer は全メソッドで拒否される
    public function test_customer_is_denied_all_actions(): void
    {
        $customer = User::factory()->customer()->create();
        $application = TenantApplication::factory()->create();

        $this->assertFalse($this->policy->viewAny($customer));
        $this->assertFalse($this->policy->view($customer, $application));
        $this->assertFalse($this->policy->startReview($customer, $application));
        $this->assertFalse($this->policy->approve($customer, $application));
        $this->assertFalse($this->policy->reject($customer, $application));
        $this->assertFalse($this->policy->updateNotes($customer, $application));
    }
}
