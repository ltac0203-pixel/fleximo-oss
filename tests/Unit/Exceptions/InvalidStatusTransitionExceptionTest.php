<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidStatusTransitionException;
use PHPUnit\Framework\TestCase;

class InvalidStatusTransitionExceptionTest extends TestCase
{
    public function test_stores_current_and_target_status(): void
    {
        $e = new InvalidStatusTransitionException(OrderStatus::Completed, OrderStatus::Accepted);
        $this->assertSame(OrderStatus::Completed, $e->currentStatus);
        $this->assertSame(OrderStatus::Accepted, $e->targetStatus);
    }

    public function test_generates_default_message(): void
    {
        $e = new InvalidStatusTransitionException(OrderStatus::Completed, OrderStatus::Accepted);
        $this->assertEquals('Cannot transition from completed to accepted', $e->getMessage());
    }

    public function test_get_user_message_returns_japanese(): void
    {
        $e = new InvalidStatusTransitionException(OrderStatus::Completed, OrderStatus::Accepted);
        $this->assertEquals('「完了」から「受付済み」への変更はできません。', $e->getUserMessage());
    }

    public function test_accepts_custom_message(): void
    {
        $e = new InvalidStatusTransitionException(OrderStatus::Completed, OrderStatus::Accepted, 'カスタム');
        $this->assertEquals('カスタム', $e->getMessage());
    }

    public function test_all_status_pairs_produce_valid_user_messages(): void
    {
        foreach (OrderStatus::cases() as $current) {
            foreach (OrderStatus::cases() as $target) {
                $e = new InvalidStatusTransitionException($current, $target);
                $this->assertNotEmpty($e->getUserMessage());
            }
        }
    }
}
