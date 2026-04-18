<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Fincode;

use App\Services\Fincode\FincodeApiException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FincodeApiExceptionTest extends TestCase
{
    #[DataProvider('retryableErrorCodeProvider')]
    public function test_is_retryable_with_error_codes(string $errorCode, bool $expected): void
    {
        $exception = new FincodeApiException($errorCode, [], 'test', 0);

        $this->assertSame($expected, $exception->isRetryable());
    }

    public static function retryableErrorCodeProvider(): array
    {
        return [
            'server error code' => ['E01100001', true],
            'timeout error code' => ['E01100002', true],
            'non-retryable error code' => ['E001', false],
            'unknown error code' => ['E99999999', false],
        ];
    }

    #[DataProvider('retryableHttpStatusProvider')]
    public function test_is_retryable_with_http_status(int $httpStatus, bool $expected): void
    {
        $exception = new FincodeApiException(null, [], 'test', $httpStatus);

        $this->assertSame($expected, $exception->isRetryable());
    }

    public static function retryableHttpStatusProvider(): array
    {
        return [
            'HTTP 500 Internal Server Error' => [500, true],
            'HTTP 502 Bad Gateway' => [502, true],
            'HTTP 503 Service Unavailable' => [503, true],
            'HTTP 504 Gateway Timeout' => [504, true],
            'HTTP 400 Bad Request' => [400, false],
            'HTTP 401 Unauthorized' => [401, false],
            'HTTP 403 Forbidden' => [403, false],
            'HTTP 404 Not Found' => [404, false],
            'HTTP 422 Unprocessable Entity' => [422, false],
            'HTTP 200 OK' => [200, false],
            'HTTP 0 (no status)' => [0, false],
        ];
    }

    public function test_is_retryable_prefers_error_code_over_http_status(): void
    {
        // リトライ可能なエラーコード + 4xx ステータス → リトライ可能
        $exception = new FincodeApiException('E01100001', [], 'test', 400);

        $this->assertTrue($exception->isRetryable());
    }
}
