<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->clear();
    }

    public function test_payment_can_be_created(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->forTenant($tenant)->create();

        $payment = new Payment([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'method' => PaymentMethod::Card,
        ]);
        $payment->status = PaymentStatus::Pending;
        $payment->amount = 1500;
        $payment->save();

        // fincode関連フィールドはMass Assignment防止のため$fillable外で、Service層から直接代入する
        $payment->fincode_id = 'pay_123456';
        $payment->save();

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'method' => 'card',
            'fincode_id' => 'pay_123456',
            'status' => 'pending',
            'amount' => 1500,
        ]);
    }

    public function test_belongs_to_order(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->forTenant($tenant)->create();

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertInstanceOf(Order::class, $payment->order);
        $this->assertEquals($order->id, $payment->order->id);
    }

    public function test_belongs_to_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->forTenant($tenant)->create();

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $payment->tenant);
        $this->assertEquals($tenant->id, $payment->tenant->id);
    }

    public function test_status_is_cast_to_payment_status_enum(): void
    {
        $payment = Payment::factory()->create();

        $this->assertInstanceOf(PaymentStatus::class, $payment->status);
    }

    public function test_method_is_cast_to_payment_method_enum(): void
    {
        $payment = Payment::factory()->create();

        $this->assertInstanceOf(PaymentMethod::class, $payment->method);
    }

    public function test_is_pending(): void
    {
        $payment = Payment::factory()->pending()->create();
        $this->assertTrue($payment->isPending());
    }

    public function test_is_processing(): void
    {
        $payment = Payment::factory()->processing()->create();
        $this->assertTrue($payment->isProcessing());
    }

    public function test_is_completed(): void
    {
        $payment = Payment::factory()->completed()->create();
        $this->assertTrue($payment->isCompleted());
    }

    public function test_is_failed(): void
    {
        $payment = Payment::factory()->failed()->create();
        $this->assertTrue($payment->isFailed());
    }

    public function test_mark_as_processing_from_pending(): void
    {
        $payment = Payment::factory()->pending()->create();

        $payment->markAsProcessing();

        $this->assertTrue($payment->isProcessing());
    }

    public function test_mark_as_completed_from_processing(): void
    {
        $payment = Payment::factory()->processing()->create();

        $payment->markAsCompleted();

        $this->assertTrue($payment->isCompleted());
    }

    public function test_mark_as_failed_from_pending(): void
    {
        $payment = Payment::factory()->pending()->create();

        $payment->markAsFailed();

        $this->assertTrue($payment->isFailed());
    }

    public function test_mark_as_failed_from_processing(): void
    {
        $payment = Payment::factory()->processing()->create();

        $payment->markAsFailed();

        $this->assertTrue($payment->isFailed());
    }

    public function test_mark_as_processing_throws_exception_for_invalid_transition(): void
    {
        $payment = Payment::factory()->completed()->create();

        $this->expectException(InvalidArgumentException::class);

        $payment->markAsProcessing();
    }

    public function test_mark_as_completed_throws_exception_for_invalid_transition(): void
    {
        $payment = Payment::factory()->pending()->create();

        $this->expectException(InvalidArgumentException::class);

        $payment->markAsCompleted();
    }

    public function test_mark_as_failed_throws_exception_for_terminal_status(): void
    {
        $payment = Payment::factory()->completed()->create();

        $this->expectException(InvalidArgumentException::class);

        $payment->markAsFailed();
    }

    public function test_concurrent_terminal_transition_rejects_stale_overwrite(): void
    {
        $payment = Payment::factory()->processing()->create();
        $firstActor = Payment::findOrFail($payment->id);
        $staleActor = Payment::findOrFail($payment->id);

        $firstActor->markAsCompleted();

        try {
            $staleActor->markAsFailed();
            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Cannot transition from completed to failed', $e->getMessage());
        }

        $this->assertEquals(PaymentStatus::Completed, $payment->fresh()->status);
    }

    public function test_same_status_transition_is_idempotent_for_stale_instance(): void
    {
        $payment = Payment::factory()->processing()->create();
        $firstActor = Payment::findOrFail($payment->id);
        $staleActor = Payment::findOrFail($payment->id);

        $firstActor->markAsCompleted();
        $staleActor->markAsCompleted();

        $this->assertEquals(PaymentStatus::Completed, $staleActor->status);
        $this->assertEquals(PaymentStatus::Completed, $payment->fresh()->status);
    }

    public function test_tenant_scope_filters_payments_by_tenant(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $order1 = Order::factory()->forTenant($tenant1)->create();
        $order2 = Order::factory()->forTenant($tenant1)->create();
        $order3 = Order::factory()->forTenant($tenant2)->create();

        Payment::factory()->create([
            'order_id' => $order1->id,
            'tenant_id' => $tenant1->id,
        ]);
        Payment::factory()->create([
            'order_id' => $order2->id,
            'tenant_id' => $tenant1->id,
        ]);
        Payment::factory()->create([
            'order_id' => $order3->id,
            'tenant_id' => $tenant2->id,
        ]);

        app(TenantContext::class)->setTenantInstance($tenant1);

        $payments = Payment::all();

        $this->assertCount(2, $payments);
        $this->assertTrue($payments->every(fn ($payment) => $payment->tenant_id === $tenant1->id));
    }

    public function test_payment_method_labels(): void
    {
        $this->assertEquals('クレジットカード', PaymentMethod::Card->label());
        $this->assertEquals('PayPay', PaymentMethod::PayPay->label());
    }

    public function test_payment_method_values(): void
    {
        $values = PaymentMethod::values();

        $this->assertContains('card', $values);
        $this->assertContains('paypay', $values);
        $this->assertCount(2, $values);
    }

    public function test_payment_status_labels(): void
    {
        $this->assertEquals('決済待ち', PaymentStatus::Pending->label());
        $this->assertEquals('処理中', PaymentStatus::Processing->label());
        $this->assertEquals('完了', PaymentStatus::Completed->label());
        $this->assertEquals('失敗', PaymentStatus::Failed->label());
    }

    public function test_payment_status_values(): void
    {
        $values = PaymentStatus::values();

        $this->assertContains('pending', $values);
        $this->assertContains('processing', $values);
        $this->assertContains('completed', $values);
        $this->assertContains('failed', $values);
        $this->assertCount(4, $values);
    }

    public function test_payment_status_is_terminal(): void
    {
        $this->assertFalse(PaymentStatus::Pending->isTerminal());
        $this->assertFalse(PaymentStatus::Processing->isTerminal());
        $this->assertTrue(PaymentStatus::Completed->isTerminal());
        $this->assertTrue(PaymentStatus::Failed->isTerminal());
    }

    public function test_payment_status_can_transition_to(): void
    {

        $this->assertTrue(PaymentStatus::Pending->canTransitionTo(PaymentStatus::Processing));
        $this->assertTrue(PaymentStatus::Pending->canTransitionTo(PaymentStatus::Failed));
        $this->assertFalse(PaymentStatus::Pending->canTransitionTo(PaymentStatus::Completed));

        $this->assertTrue(PaymentStatus::Processing->canTransitionTo(PaymentStatus::Completed));
        $this->assertTrue(PaymentStatus::Processing->canTransitionTo(PaymentStatus::Failed));
        $this->assertFalse(PaymentStatus::Processing->canTransitionTo(PaymentStatus::Pending));

        $this->assertFalse(PaymentStatus::Completed->canTransitionTo(PaymentStatus::Pending));
        $this->assertFalse(PaymentStatus::Failed->canTransitionTo(PaymentStatus::Pending));
    }

    public function test_factory_for_order(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->forTenant($tenant)->totalAmount(2500)->create();

        $payment = Payment::factory()->forOrder($order)->create();

        $this->assertEquals($order->id, $payment->order_id);
        $this->assertEquals($tenant->id, $payment->tenant_id);
        $this->assertEquals(2500, $payment->amount);
    }

    public function test_factory_card_method(): void
    {
        $payment = Payment::factory()->card()->create();

        $this->assertEquals(PaymentMethod::Card, $payment->method);
    }

    public function test_factory_paypay_method(): void
    {
        $payment = Payment::factory()->paypay()->create();

        $this->assertEquals(PaymentMethod::PayPay, $payment->method);
    }
}
