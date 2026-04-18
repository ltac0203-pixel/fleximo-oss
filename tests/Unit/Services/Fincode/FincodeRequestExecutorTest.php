<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Fincode;

use App\Services\Fincode\FincodeApiException;
use App\Services\Fincode\FincodeLogSanitizer;
use App\Services\Fincode\FincodeRequestExecutor;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FincodeRequestExecutorTest extends TestCase
{
    private FincodeRequestExecutor $executor;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'fincode.timeout' => 30,
            'fincode.retry_times' => 3,
            'fincode.retry_sleep_ms' => 1,
        ]);

        $this->executor = new FincodeRequestExecutor(new FincodeLogSanitizer);
    }

    public function test_execute_sends_get_request(): void
    {
        Http::fake([
            'api.test.fincode.jp/v1/payments/pay_123*' => Http::response([
                'id' => 'pay_123',
                'status' => 'CAPTURED',
            ], 200),
        ]);

        $response = $this->executor->execute(
            'GET',
            'https://api.test.fincode.jp/v1/payments/pay_123',
            '/payments/pay_123',
            ['Authorization' => 'Bearer test'],
            ['pay_type' => 'Card']
        );

        $this->assertSame('pay_123', $response['id']);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://api.test.fincode.jp/v1/payments/pay_123?pay_type=Card';
        });
    }

    public function test_execute_logs_sanitized_response(): void
    {
        Log::spy();

        Http::fake([
            'api.test.fincode.jp/v1/payments/pay_123' => Http::response([
                'id' => 'pay_123',
                'status' => 'AWAITING_CUSTOMER_PAYMENT',
                'link_url' => 'https://sensitive.example.com',
                'access_id' => 'acc_sensitive',
            ], 200),
        ]);

        $this->executor->execute(
            'GET',
            'https://api.test.fincode.jp/v1/payments/pay_123',
            '/payments/pay_123',
            ['Authorization' => 'Bearer test']
        );

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'fincode API response'
                    && $context['response']['id'] === 'pay_123'
                    && $context['response']['status'] === 'AWAITING_CUSTOMER_PAYMENT'
                    && $context['response']['link_url'] === '***MASKED***'
                    && $context['response']['access_id'] === '***MASKED***';
            });
    }

    public function test_execute_retries_server_errors_until_success(): void
    {
        $callCount = 0;

        Http::fake(function () use (&$callCount) {
            $callCount++;

            if ($callCount < 3) {
                return Http::response([
                    'errors' => [
                        ['error_code' => 'E01100001', 'error_message' => 'Server Error'],
                    ],
                ], 500);
            }

            return Http::response([
                'id' => 'pay_123',
                'status' => 'CAPTURED',
            ], 200);
        });

        $response = $this->executor->execute(
            'POST',
            'https://api.test.fincode.jp/v1/payments',
            '/payments',
            ['Authorization' => 'Bearer test'],
            ['amount' => '1000']
        );

        $this->assertSame('pay_123', $response['id']);
        $this->assertSame(3, $callCount);
    }

    public function test_execute_throws_immediately_on_client_error(): void
    {
        Http::fake([
            'api.test.fincode.jp/v1/payments' => Http::response([
                'errors' => [
                    ['error_code' => 'E001', 'error_message' => 'Invalid request'],
                ],
            ], 400),
        ]);

        try {
            $this->executor->execute(
                'POST',
                'https://api.test.fincode.jp/v1/payments',
                '/payments',
                ['Authorization' => 'Bearer test'],
                ['amount' => '1000']
            );
            $this->fail('Expected FincodeApiException was not thrown');
        } catch (FincodeApiException $e) {
            $this->assertSame(400, $e->getCode());
            $this->assertFalse($e->isRetryable());
        }

        Http::assertSentCount(1);
    }

    public function test_execute_converts_connection_exception_after_retries(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });

        try {
            $this->executor->execute(
                'POST',
                'https://api.test.fincode.jp/v1/payments',
                '/payments',
                ['Authorization' => 'Bearer test'],
                ['amount' => '1000']
            );
            $this->fail('Expected FincodeApiException was not thrown');
        } catch (FincodeApiException $e) {
            $this->assertSame('E01100002', $e->errorCode);
            $this->assertStringContainsString('接続に失敗', $e->getMessage());
            $this->assertInstanceOf(ConnectionException::class, $e->getPrevious());
        }
    }

    public function test_execute_converts_request_exception(): void
    {
        Http::fake(function () {
            throw new RequestException(new Response(new PsrResponse(400, [], json_encode([
                'errors' => [
                    ['error_code' => 'E001', 'error_message' => 'Invalid request'],
                ],
            ]))));
        });

        $this->expectException(FincodeApiException::class);
        $this->expectExceptionMessage('決済処理に失敗しました。');

        $this->executor->execute(
            'POST',
            'https://api.test.fincode.jp/v1/payments',
            '/payments',
            ['Authorization' => 'Bearer test'],
            ['amount' => '1000']
        );
    }

    public function test_execute_converts_unexpected_exception(): void
    {
        Http::fake(function () {
            throw new \RuntimeException('boom');
        });

        $this->expectException(FincodeApiException::class);
        $this->expectExceptionMessage('決済処理中に予期しないエラーが発生しました。');

        $this->executor->execute(
            'POST',
            'https://api.test.fincode.jp/v1/payments',
            '/payments',
            ['Authorization' => 'Bearer test'],
            ['amount' => '1000']
        );
    }
}
