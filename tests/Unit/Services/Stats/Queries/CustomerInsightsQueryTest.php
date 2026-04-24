<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Stats\Queries;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Stats\Queries\CustomerInsightsQuery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerInsightsQueryTest extends TestCase
{
    use RefreshDatabase;

    private CustomerInsightsQuery $query;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-03-15 12:00:00'));
        $this->query = app(CustomerInsightsQuery::class);
        $this->tenant = Tenant::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_for_range_classifies_new_vs_repeat_customers(): void
    {
        $repeat = User::factory()->customer()->create();
        $newbie = User::factory()->customer()->create();

        // リピーター: 期間前と期間内の両方に注文がある
        Order::factory()->forUser($repeat)->forTenant($this->tenant)->paid()->forBusinessDate('2026-03-10')->create();
        Order::factory()->forUser($repeat)->forTenant($this->tenant)->completed()->forBusinessDate('2026-03-14')->create();

        // 新規: 期間内のみ
        Order::factory()->forUser($newbie)->forTenant($this->tenant)->paid()->forBusinessDate('2026-03-14')->create();

        $result = $this->query->forRange($this->tenant->id, Carbon::parse('2026-03-14'), Carbon::parse('2026-03-15'));

        $this->assertSame(2, $result['unique_customers']);
        $this->assertSame(1, $result['new_customers']);
        $this->assertSame(1, $result['repeat_customers']);
        $this->assertSame(50.0, $result['repeat_rate']);
    }

    public function test_for_range_returns_zero_repeat_rate_when_no_customers(): void
    {
        $result = $this->query->forRange($this->tenant->id, Carbon::parse('2026-03-14'), Carbon::parse('2026-03-15'));

        $this->assertSame(0, $result['unique_customers']);
        $this->assertSame(0, $result['new_customers']);
        $this->assertSame(0, $result['repeat_customers']);
        $this->assertSame(0, $result['repeat_rate']);
    }

    public function test_for_range_ignores_other_tenant_orders_in_prior_check(): void
    {
        $user = User::factory()->customer()->create();

        // 他テナントでの過去注文は「当テナントから見て新規」判定に影響しない
        $otherTenant = Tenant::factory()->create();
        Order::factory()->forUser($user)->forTenant($otherTenant)->paid()->forBusinessDate('2026-03-10')->create();

        Order::factory()->forUser($user)->forTenant($this->tenant)->paid()->forBusinessDate('2026-03-14')->create();

        $result = $this->query->forRange($this->tenant->id, Carbon::parse('2026-03-14'), Carbon::parse('2026-03-15'));

        $this->assertSame(1, $result['unique_customers']);
        $this->assertSame(1, $result['new_customers']);
        $this->assertSame(0, $result['repeat_customers']);
    }
}
