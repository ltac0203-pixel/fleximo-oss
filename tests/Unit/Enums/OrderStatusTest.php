<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\OrderStatus;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_all_statuses_have_labels(): void
    {
        foreach (OrderStatus::cases() as $status) {
            $this->assertNotEmpty($status->label());
        }
    }

    public function test_labels_are_in_japanese(): void
    {
        $labels = [
            OrderStatus::PendingPayment->label() => '決済待ち',
            OrderStatus::Paid->label() => '決済完了',
            OrderStatus::Accepted->label() => '受付済み',
            OrderStatus::InProgress->label() => '調理中',
            OrderStatus::Ready->label() => '準備完了',
            OrderStatus::Completed->label() => '完了',
            OrderStatus::Cancelled->label() => 'キャンセル',
            OrderStatus::PaymentFailed->label() => '決済失敗',
            OrderStatus::Refunded->label() => '返金済み',
        ];

        foreach ($labels as $actual => $expected) {
            $this->assertEquals($expected, $actual);
        }
    }

    public function test_pending_payment_can_transition_to_paid(): void
    {
        $this->assertTrue(
            OrderStatus::PendingPayment->canTransitionTo(OrderStatus::Paid)
        );
    }

    public function test_pending_payment_can_transition_to_payment_failed(): void
    {
        $this->assertTrue(
            OrderStatus::PendingPayment->canTransitionTo(OrderStatus::PaymentFailed)
        );
    }

    public function test_paid_can_transition_to_accepted(): void
    {
        $this->assertTrue(
            OrderStatus::Paid->canTransitionTo(OrderStatus::Accepted)
        );
    }

    public function test_paid_can_transition_to_cancelled(): void
    {
        $this->assertTrue(
            OrderStatus::Paid->canTransitionTo(OrderStatus::Cancelled)
        );
    }

    public function test_accepted_can_transition_to_in_progress(): void
    {
        $this->assertTrue(
            OrderStatus::Accepted->canTransitionTo(OrderStatus::InProgress)
        );
    }

    public function test_accepted_can_transition_to_cancelled(): void
    {
        $this->assertTrue(
            OrderStatus::Accepted->canTransitionTo(OrderStatus::Cancelled)
        );
    }

    public function test_in_progress_can_transition_to_ready(): void
    {
        $this->assertTrue(
            OrderStatus::InProgress->canTransitionTo(OrderStatus::Ready)
        );
    }

    public function test_in_progress_can_transition_to_cancelled(): void
    {
        $this->assertTrue(
            OrderStatus::InProgress->canTransitionTo(OrderStatus::Cancelled)
        );
    }

    public function test_ready_can_transition_to_completed(): void
    {
        $this->assertTrue(
            OrderStatus::Ready->canTransitionTo(OrderStatus::Completed)
        );
    }

    public function test_ready_can_transition_to_cancelled(): void
    {
        $this->assertTrue(
            OrderStatus::Ready->canTransitionTo(OrderStatus::Cancelled)
        );
    }

    public function test_cancelled_can_transition_to_refunded(): void
    {
        $this->assertTrue(
            OrderStatus::Cancelled->canTransitionTo(OrderStatus::Refunded)
        );
    }

    public function test_completed_cannot_transition_to_any_status(): void
    {
        foreach (OrderStatus::cases() as $status) {
            $this->assertFalse(
                OrderStatus::Completed->canTransitionTo($status),
                "Completed should not be able to transition to {$status->value}"
            );
        }
    }

    public function test_payment_failed_cannot_transition_to_any_status(): void
    {
        foreach (OrderStatus::cases() as $status) {
            $this->assertFalse(
                OrderStatus::PaymentFailed->canTransitionTo($status),
                "PaymentFailed should not be able to transition to {$status->value}"
            );
        }
    }

    public function test_refunded_cannot_transition_to_any_status(): void
    {
        foreach (OrderStatus::cases() as $status) {
            $this->assertFalse(
                OrderStatus::Refunded->canTransitionTo($status),
                "Refunded should not be able to transition to {$status->value}"
            );
        }
    }

    public function test_pending_payment_cannot_directly_transition_to_accepted(): void
    {
        $this->assertFalse(
            OrderStatus::PendingPayment->canTransitionTo(OrderStatus::Accepted)
        );
    }

    public function test_paid_cannot_directly_transition_to_in_progress(): void
    {
        $this->assertFalse(
            OrderStatus::Paid->canTransitionTo(OrderStatus::InProgress)
        );
    }

    public function test_terminal_statuses(): void
    {
        $this->assertTrue(OrderStatus::Completed->isTerminal());
        $this->assertTrue(OrderStatus::PaymentFailed->isTerminal());
        $this->assertTrue(OrderStatus::Refunded->isTerminal());

        $this->assertFalse(OrderStatus::PendingPayment->isTerminal());
        $this->assertFalse(OrderStatus::Paid->isTerminal());
        $this->assertFalse(OrderStatus::Accepted->isTerminal());
        $this->assertFalse(OrderStatus::InProgress->isTerminal());
        $this->assertFalse(OrderStatus::Ready->isTerminal());
        $this->assertFalse(OrderStatus::Cancelled->isTerminal());
    }

    public function test_can_be_cancelled(): void
    {
        $this->assertTrue(OrderStatus::Paid->canBeCancelled());
        $this->assertTrue(OrderStatus::Accepted->canBeCancelled());
        $this->assertTrue(OrderStatus::InProgress->canBeCancelled());
        $this->assertTrue(OrderStatus::Ready->canBeCancelled());

        $this->assertFalse(OrderStatus::PendingPayment->canBeCancelled());
        $this->assertFalse(OrderStatus::Completed->canBeCancelled());
        $this->assertFalse(OrderStatus::Cancelled->canBeCancelled());
        $this->assertFalse(OrderStatus::PaymentFailed->canBeCancelled());
        $this->assertFalse(OrderStatus::Refunded->canBeCancelled());
    }

    public function test_is_active_for_kds(): void
    {
        $this->assertTrue(OrderStatus::Accepted->isActive());
        $this->assertTrue(OrderStatus::InProgress->isActive());
        $this->assertTrue(OrderStatus::Ready->isActive());

        $this->assertFalse(OrderStatus::PendingPayment->isActive());
        $this->assertFalse(OrderStatus::Paid->isActive());
        $this->assertFalse(OrderStatus::Completed->isActive());
        $this->assertFalse(OrderStatus::Cancelled->isActive());
        $this->assertFalse(OrderStatus::PaymentFailed->isActive());
        $this->assertFalse(OrderStatus::Refunded->isActive());
    }

    public function test_values_returns_all_status_values(): void
    {
        $values = OrderStatus::values();

        $this->assertContains('pending_payment', $values);
        $this->assertContains('paid', $values);
        $this->assertContains('accepted', $values);
        $this->assertContains('in_progress', $values);
        $this->assertContains('ready', $values);
        $this->assertContains('completed', $values);
        $this->assertContains('cancelled', $values);
        $this->assertContains('payment_failed', $values);
        $this->assertContains('refunded', $values);
        $this->assertCount(9, $values);
    }

    public function test_sales_statuses_returns_correct_statuses(): void
    {
        $expected = [
            OrderStatus::Paid,
            OrderStatus::Accepted,
            OrderStatus::InProgress,
            OrderStatus::Ready,
            OrderStatus::Completed,
        ];

        $this->assertEquals($expected, OrderStatus::salesStatuses());
    }

    public function test_sales_status_values_returns_string_values(): void
    {
        $expected = ['paid', 'accepted', 'in_progress', 'ready', 'completed'];
        $this->assertEquals($expected, OrderStatus::salesStatusValues());
    }

    public function test_sales_statuses_excludes_non_sales_statuses(): void
    {
        $salesStatuses = OrderStatus::salesStatuses();

        $this->assertNotContains(OrderStatus::PendingPayment, $salesStatuses);
        $this->assertNotContains(OrderStatus::Cancelled, $salesStatuses);
        $this->assertNotContains(OrderStatus::PaymentFailed, $salesStatuses);
        $this->assertNotContains(OrderStatus::Refunded, $salesStatuses);
    }
}
