<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\OrderNumberGenerationException;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class OrderNumberGenerationExceptionTest extends TestCase
{
    public function test_stores_tenant_id(): void
    {
        $e = new OrderNumberGenerationException(42, Carbon::parse('2024-01-15'), 'max_retries');
        $this->assertEquals(42, $e->tenantId);
    }

    public function test_stores_business_date(): void
    {
        $date = Carbon::parse('2024-01-15');
        $e = new OrderNumberGenerationException(1, $date, 'timeout');
        $this->assertEquals('2024-01-15', $e->businessDate->toDateString());
    }

    public function test_stores_reason(): void
    {
        $e = new OrderNumberGenerationException(1, Carbon::parse('2024-01-15'), 'max_retries_exceeded');
        $this->assertEquals('max_retries_exceeded', $e->reason);
    }

    public function test_generates_default_message(): void
    {
        $e = new OrderNumberGenerationException(5, Carbon::parse('2024-03-01'), 'lock_timeout');
        $this->assertStringContainsString('テナントID: 5', $e->getMessage());
        $this->assertStringContainsString('2024-03-01', $e->getMessage());
        $this->assertStringContainsString('lock_timeout', $e->getMessage());
    }

    public function test_accepts_custom_message(): void
    {
        $e = new OrderNumberGenerationException(1, Carbon::parse('2024-01-01'), 'test', 'カスタムメッセージ');
        $this->assertEquals('カスタムメッセージ', $e->getMessage());
    }

    public function test_accepts_previous_exception(): void
    {
        $prev = new \RuntimeException('DB error');
        $e = new OrderNumberGenerationException(1, Carbon::parse('2024-01-01'), 'db_error', '', 0, $prev);
        $this->assertSame($prev, $e->getPrevious());
    }
}
