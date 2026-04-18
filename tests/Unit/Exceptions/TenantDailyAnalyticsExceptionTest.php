<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\TenantDailyAnalyticsPartialFailureException;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class TenantDailyAnalyticsExceptionTest extends TestCase
{
    private function makeFailures(array $tenantIds): array
    {
        return array_map(fn (int $id) => [
            'tenant_id' => $id,
            'error' => "Error for tenant {$id}",
            'exception_class' => 'RuntimeException',
        ], $tenantIds);
    }

    public function test_stores_failed_tenant_ids(): void
    {
        $failures = $this->makeFailures([1, 2, 3]);
        $e = new TenantDailyAnalyticsPartialFailureException(Carbon::parse('2024-01-15'), $failures);
        $this->assertEquals([1, 2, 3], $e->failedTenantIds);
    }

    public function test_stores_failure_count(): void
    {
        $failures = $this->makeFailures([10, 20, 30, 40]);
        $e = new TenantDailyAnalyticsPartialFailureException(Carbon::parse('2024-01-15'), $failures);
        $this->assertEquals(4, $e->failureCount);
    }

    public function test_limits_sample_errors(): void
    {
        $failures = $this->makeFailures(range(1, 10));
        $e = new TenantDailyAnalyticsPartialFailureException(Carbon::parse('2024-01-15'), $failures, sampleSize: 3);
        $this->assertCount(3, $e->sampleErrors);
    }

    public function test_default_sample_size_is_five(): void
    {
        $failures = $this->makeFailures(range(1, 10));
        $e = new TenantDailyAnalyticsPartialFailureException(Carbon::parse('2024-01-15'), $failures);
        $this->assertCount(5, $e->sampleErrors);
    }

    public function test_stores_date(): void
    {
        $date = Carbon::parse('2024-06-15');
        $e = new TenantDailyAnalyticsPartialFailureException($date, $this->makeFailures([1]));
        $this->assertEquals('2024-06-15', $e->date->toDateString());
    }

    public function test_generates_default_message(): void
    {
        $failures = $this->makeFailures([5, 10]);
        $e = new TenantDailyAnalyticsPartialFailureException(Carbon::parse('2024-01-15'), $failures);
        $this->assertStringContainsString('2024-01-15', $e->getMessage());
        $this->assertStringContainsString('失敗件数: 2', $e->getMessage());
        $this->assertStringContainsString('5, 10', $e->getMessage());
    }

    public function test_from_failures_factory_method(): void
    {
        $failures = $this->makeFailures([1, 2, 3]);
        $e = TenantDailyAnalyticsPartialFailureException::fromFailures(Carbon::parse('2024-01-15'), $failures);
        $this->assertInstanceOf(TenantDailyAnalyticsPartialFailureException::class, $e);
        $this->assertEquals(3, $e->failureCount);
    }

    public function test_from_failures_respects_sample_size(): void
    {
        $failures = $this->makeFailures(range(1, 20));
        $e = TenantDailyAnalyticsPartialFailureException::fromFailures(Carbon::parse('2024-01-15'), $failures, 2);
        $this->assertCount(2, $e->sampleErrors);
    }

    public function test_accepts_custom_message(): void
    {
        $e = new TenantDailyAnalyticsPartialFailureException(
            Carbon::parse('2024-01-15'),
            $this->makeFailures([1]),
            message: 'カスタム'
        );
        $this->assertEquals('カスタム', $e->getMessage());
    }
}
