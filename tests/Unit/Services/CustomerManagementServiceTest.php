<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Services\Admin\CustomerManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    private CustomerManagementService $service;

    private User $admin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CustomerManagementService::class);
        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->customer()->create([
            'is_active' => true,
        ]);
    }

    public function test_suspend_customer_changes_status(): void
    {
        $this->actingAs($this->admin);

        $result = $this->service->suspendCustomer($this->customer, '不正利用の疑い', $this->admin);

        $this->assertEquals(AccountStatus::Suspended, $result->account_status);
        $this->assertFalse($result->is_active);
        $this->assertEquals('不正利用の疑い', $result->account_status_reason);
    }

    public function test_suspend_customer_deletes_tokens(): void
    {
        $this->actingAs($this->admin);
        $this->customer->createToken('test-token');
        $this->assertEquals(1, $this->customer->tokens()->count());

        $this->service->suspendCustomer($this->customer, '不正利用の疑い', $this->admin);

        $this->assertEquals(0, $this->customer->tokens()->count());
    }

    public function test_ban_customer_changes_status(): void
    {
        $this->actingAs($this->admin);

        $result = $this->service->banCustomer($this->customer, '規約違反', $this->admin);

        $this->assertEquals(AccountStatus::Banned, $result->account_status);
        $this->assertFalse($result->is_active);
        $this->assertEquals('規約違反', $result->account_status_reason);
    }

    public function test_ban_customer_deletes_tokens(): void
    {
        $this->actingAs($this->admin);
        $this->customer->createToken('test-token');
        $this->assertEquals(1, $this->customer->tokens()->count());

        $this->service->banCustomer($this->customer, '規約違反', $this->admin);

        $this->assertEquals(0, $this->customer->tokens()->count());
    }

    public function test_reactivate_customer_changes_status(): void
    {
        $this->actingAs($this->admin);

        // まず一時停止にする
        $this->service->suspendCustomer($this->customer, '不正利用の疑い', $this->admin);
        $this->assertEquals(AccountStatus::Suspended, $this->customer->account_status);

        // 再有効化する
        $result = $this->service->reactivateCustomer($this->customer, $this->admin);

        $this->assertEquals(AccountStatus::Active, $result->account_status);
        $this->assertTrue($result->is_active);
    }

    public function test_reactivate_clears_reason(): void
    {
        $this->actingAs($this->admin);

        // まず一時停止にする
        $this->service->suspendCustomer($this->customer, '不正利用の疑い', $this->admin);
        $this->assertNotNull($this->customer->fresh()->account_status_reason);

        // 再有効化する
        $result = $this->service->reactivateCustomer($this->customer, $this->admin);

        $this->assertNull($result->account_status_reason);
    }
}
