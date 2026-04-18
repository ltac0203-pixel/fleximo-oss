<?php

declare(strict_types=1);

namespace App\Exceptions;

use Carbon\Carbon;
use Exception;
use Throwable;

class TenantDailyAnalyticsPartialFailureException extends Exception
{
    /** @var array<int, int> */
    public readonly array $failedTenantIds;

    public readonly int $failureCount;

    /** @var array<int, array{tenant_id:int,error:string,exception_class:string}> */
    public readonly array $sampleErrors;

    /**
     * @param  array<int, array{tenant_id:int,error:string,exception_class:string}>  $failures
     */
    public function __construct(
        public readonly Carbon $date,
        array $failures,
        int $sampleSize = 5,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $this->failedTenantIds = array_values(array_map(
            static fn (array $failure): int => (int) $failure['tenant_id'],
            $failures
        ));
        $this->failureCount = count($this->failedTenantIds);
        $this->sampleErrors = array_slice($failures, 0, $sampleSize);

        if ($message === '') {
            $tenantIdList = implode(', ', $this->failedTenantIds);
            $message = "日次分析の全テナント集計で一部テナントの処理に失敗しました。対象日: {$date->toDateString()}, 失敗件数: {$this->failureCount}, 失敗テナントID: [{$tenantIdList}]";
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @param  array<int, array{tenant_id:int,error:string,exception_class:string}>  $failures
     */
    public static function fromFailures(
        Carbon $date,
        array $failures,
        int $sampleSize = 5
    ): self {
        return new self($date, $failures, $sampleSize);
    }
}
