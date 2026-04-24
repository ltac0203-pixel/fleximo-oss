<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Stats\Queries;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Services\Stats\Queries\PaymentMethodBreakdownQuery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodBreakdownQueryTest extends TestCase
{
    use RefreshDatabase;

    private PaymentMethodBreakdownQuery $query;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-03-15 12:00:00'));
        $this->query = app(PaymentMethodBreakdownQuery::class);
        $this->tenant = Tenant::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_for_range_aggregates_completed_payments_by_method(): void
    {
        $order1 = Order::factory()->forTenant($this->tenant)->forBusinessDate('2026-03-14')->create();
        $order2 = Order::factory()->forTenant($this->tenant)->forBusinessDate('2026-03-15')->create();

        Payment::factory()->forOrder($order1)->card()->completed()->amount(1000)->create();
        Payment::factory()->forOrder($order1)->paypay()->completed()->amount(500)->create();
        Payment::factory()->forOrder($order2)->card()->completed()->amount(2000)->create();

        $result = $this->query->forRange($this->tenant->id, Carbon::parse('2026-03-14'), Carbon::parse('2026-03-15'));

        $this->assertSame(2, $result['card']['count']);
        $this->assertSame(3000, $result['card']['amount']);
        $this->assertSame(1, $result['paypay']['count']);
        $this->assertSame(500, $result['paypay']['amount']);
    }

    public function test_for_range_excludes_non_completed_payments(): void
    {
        $order = Order::factory()->forTenant($this->tenant)->forBusinessDate('2026-03-14')->create();

        Payment::factory()->forOrder($order)->card()->completed()->amount(1000)->create();
        Payment::factory()->forOrder($order)->card()->failed()->amount(9999)->create();

        $result = $this->query->forRange($this->tenant->id, Carbon::parse('2026-03-14'), Carbon::parse('2026-03-14'));

        $this->assertSame(1, $result['card']['count']);
        $this->assertSame(1000, $result['card']['amount']);
    }

    public function test_for_range_does_not_leak_other_tenant_payments(): void
    {
        $myOrder = Order::factory()->forTenant($this->tenant)->forBusinessDate('2026-03-14')->create();
        Payment::factory()->forOrder($myOrder)->card()->completed()->amount(500)->create();

        $otherTenant = Tenant::factory()->create();
        $otherOrder = Order::factory()->forTenant($otherTenant)->forBusinessDate('2026-03-14')->create();
        Payment::factory()->forOrder($otherOrder)->card()->completed()->amount(8000)->create();

        $result = $this->query->forRange($this->tenant->id, Carbon::parse('2026-03-14'), Carbon::parse('2026-03-14'));

        $this->assertSame(1, $result['card']['count']);
        $this->assertSame(500, $result['card']['amount']);
    }
}
