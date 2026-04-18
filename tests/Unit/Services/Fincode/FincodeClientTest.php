<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Fincode;

use App\Services\Fincode\FincodeApiException;
use App\Services\Fincode\FincodeClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FincodeClientTest extends TestCase
{
    private FincodeClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'fincode.is_production' => false,
            'fincode.test_api_url' => 'https://api.test.fincode.jp/v1',
            'fincode.api_key' => 'test_api_key',
            'fincode.shop_id' => 'test_shop_id',
        ]);

        $this->client = app(FincodeClient::class);
    }

    public function test_creates_card_payment_successfully(): void
    {
        Http::fake([
            'api.test.fincode.jp/v1/payments' => Http::response([
                'id' => 'pay_123',
                'access_id' => 'acc_456',
                'status' => 'AWAITING_CUSTOMER_PAYMENT',
                'amount' => 1000,
                'pay_type' => 'Card',
            ], 200),
        ]);

        $response = $this->client->createCardPayment([
            'amount' => 1000,
            'order_id' => 'order_123',
        ]);

        $this->assertEquals('pay_123', $response->id);
        $this->assertEquals('acc_456', $response->accessId);
        $this->assertEquals(1000, $response->amount);

        Http::assertSent(function ($request) {
            $idempotencyKey = $request->header('Idempotency-Key')[0] ?? null;

            return is_string($idempotencyKey) && $idempotencyKey !== '';
        });
    }

    public function test_execute_card_payment_successfully(): void
    {
        Http::fake([
            'api.test.fincode.jp/v1/payments/pay_123' => Http::response([
                'id' => 'pay_123',
                'access_id' => 'acc_456',
                'status' => 'CAPTURED',
                'amount' => 1000,
                'pay_type' => 'Card',
            ], 200),
        ]);

        $response = $this->client->executeCardPayment('pay_123', [
            'access_id' => 'acc_456',
            'token' => 'tok_789',
        ]);

        $this->assertEquals('pay_123', $response->id);
        $this->assertEquals('CAPTURED', $response->status);
    }

    public function test_creates_paypay_payment_successfully(): void
    {
        Http::fake([
            'api.test.fincode.jp/v1/payments' => Http::response([
                'id' => 'pay_paypay_123',
                'access_id' => 'acc_paypay_456',
                'status' => 'UNPROCESSED',
                'amount' => 2000,
                'pay_type' => 'Paypay',
            ], 200),
        ]);

        $response = $this->client->createPayPayPayment([
            'amount' => 2000,
            'order_id' => 'order_456',
        ]);

        $this->assertEquals('pay_paypay_123', $response->id);
        $this->assertEquals('acc_paypay_456', $response->accessId);
        $this->assertEquals('UNPROCESSED', $response->status);
        $this->assertEquals(2000, $response->amount);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['pay_type'] === 'Paypay'
                && $body['job_code'] === 'CAPTURE'
                && $body['amount'] === '2000';
        });
    }

    public function test_executes_paypay_payment_successfully(): void
    {
        Http::fake([
            'api.test.fincode.jp/v1/payments/pay_paypay_123' => Http::response([
                'id' => 'pay_paypay_123',
                'access_id' => 'acc_paypay_456',
                'status' => 'AWAITING_CUSTOMER_PAYMENT',
                'amount' => 2000,
                'pay_type' => 'Paypay',
                'link_url' => 'https://paypay.example.com/checkout',
            ], 200),
        ]);

        $response = $this->client->executePayPayPayment('pay_paypay_123', [
            'access_id' => 'acc_paypay_456',
            'redirect_url' => 'https://example.com/callback',
        ]);

        $this->assertEquals('pay_paypay_123', $response->id);
        $this->assertEquals('https://paypay.example.com/checkout', $response->linkUrl);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['pay_type'] === 'Paypay'
                && $body['access_id'] === 'acc_paypay_456'
                && $body['redirect_url'] === 'https://example.com/callback'
                && $body['redirect_type'] === '1';
        });
    }

    public function test_get_payment_successfully(): void
    {
        Http::fake([
            'api.test.fincode.jp/v1/payments/pay_123*' => Http::response([
                'id' => 'pay_123',
                'access_id' => 'acc_456',
                'status' => 'CAPTURED',
                'amount' => 1000,
                'pay_type' => 'Card',
            ], 200),
        ]);

        $response = $this->client->getPayment('pay_123');

        $this->assertEquals('pay_123', $response->id);
        $this->assertEquals('CAPTURED', $response->status);
    }

    public function test_throws_exception_on_api_error(): void
    {
        Http::fake([
            'api.test.fincode.jp/v1/payments' => Http::response([
                'errors' => [
                    ['error_code' => 'E001', 'error_message' => 'Invalid request'],
                ],
            ], 400),
        ]);

        $this->expectException(FincodeApiException::class);
        $this->expectExceptionMessage('決済処理に失敗しました。');

        $this->client->createCardPayment([
            'amount' => 1000,
        ]);
    }

    public function test_adds_tenant_shop_id_header_when_provided(): void
    {
        Http::fake([
            'api.test.fincode.jp/v1/payments' => Http::response([
                'id' => 'pay_123',
                'access_id' => 'acc_456',
                'status' => 'AWAITING_CUSTOMER_PAYMENT',
                'amount' => 1000,
                'pay_type' => 'Card',
            ], 200),
        ]);

        $response = $this->client->createCardPayment([
            'amount' => 1000,
            'tenant_shop_id' => 'tenant_shop_789',
        ]);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Tenant-Shop-Id', 'tenant_shop_789');
        });

        $this->assertEquals('pay_123', $response->id);
    }

    // リトライ関連テスト

    public function test_retries_on_500_server_error_and_succeeds(): void
    {
        config(['fincode.retry_times' => 3, 'fincode.retry_sleep_ms' => 1]);

        $callCount = 0;
        $idempotencyKeys = [];
        Http::fake(function ($request) use (&$callCount, &$idempotencyKeys) {
            $callCount++;
            $idempotencyKeys[] = $request->header('Idempotency-Key')[0] ?? null;
            if ($callCount === 1) {
                return Http::response(
                    ['errors' => [['error_code' => 'E01100001', 'error_message' => 'Server Error']]],
                    500
                );
            }

            return Http::response([
                'id' => 'pay_123',
                'access_id' => 'acc_456',
                'status' => 'AWAITING_CUSTOMER_PAYMENT',
                'amount' => 1000,
                'pay_type' => 'Card',
            ], 200);
        });

        $response = $this->client->createCardPayment(['amount' => 1000, 'payment_id' => 101]);

        $this->assertEquals('pay_123', $response->id);
        $this->assertEquals(2, $callCount);
        $this->assertCount(2, $idempotencyKeys);
        $this->assertNotNull($idempotencyKeys[0]);
        $this->assertSame($idempotencyKeys[0], $idempotencyKeys[1]);
    }

    public function test_throws_after_exhausting_retries_on_persistent_500(): void
    {
        config(['fincode.retry_times' => 3, 'fincode.retry_sleep_ms' => 1]);

        Http::fake([
            'api.test.fincode.jp/v1/payments' => Http::response(
                ['errors' => [['error_code' => 'E01100001', 'error_message' => 'Server Error']]],
                500
            ),
        ]);

        try {
            $this->client->createCardPayment(['amount' => 1000]);
            $this->fail('Expected FincodeApiException was not thrown');
        } catch (FincodeApiException $e) {
            $this->assertEquals(500, $e->getCode());
            $this->assertTrue($e->isRetryable());
        }

        Http::assertSentCount(3);
    }

    public function test_does_not_retry_on_400_client_error(): void
    {
        config(['fincode.retry_times' => 3, 'fincode.retry_sleep_ms' => 1]);

        Http::fake([
            'api.test.fincode.jp/v1/payments' => Http::response(
                ['errors' => [['error_code' => 'E001', 'error_message' => 'Invalid request']]],
                400
            ),
        ]);

        try {
            $this->client->createCardPayment(['amount' => 1000]);
            $this->fail('Expected FincodeApiException was not thrown');
        } catch (FincodeApiException $e) {
            $this->assertEquals(400, $e->getCode());
            $this->assertFalse($e->isRetryable());
        }

        Http::assertSentCount(1);
    }

    public function test_retries_connection_exception_and_succeeds(): void
    {
        config(['fincode.retry_times' => 3, 'fincode.retry_sleep_ms' => 1]);

        $callCount = 0;
        $idempotencyKeys = [];
        Http::fake(function ($request) use (&$callCount, &$idempotencyKeys) {
            $callCount++;
            $idempotencyKeys[] = $request->header('Idempotency-Key')[0] ?? null;
            if ($callCount === 1) {
                throw new ConnectionException('Connection refused');
            }

            return Http::response([
                'id' => 'pay_123',
                'access_id' => 'acc_456',
                'status' => 'AWAITING_CUSTOMER_PAYMENT',
                'amount' => 1000,
                'pay_type' => 'Card',
            ], 200);
        });

        $response = $this->client->createCardPayment(['amount' => 1000, 'payment_id' => 202]);

        $this->assertEquals('pay_123', $response->id);
        $this->assertEquals(2, $callCount);
        $this->assertCount(2, $idempotencyKeys);
        $this->assertNotNull($idempotencyKeys[0]);
        $this->assertSame($idempotencyKeys[0], $idempotencyKeys[1]);
    }

    public function test_uses_stable_idempotency_key_when_payment_id_is_same(): void
    {
        $idempotencyKeys = [];
        Http::fake(function ($request) use (&$idempotencyKeys) {
            $idempotencyKeys[] = $request->header('Idempotency-Key')[0] ?? null;

            return Http::response([
                'id' => 'pay_123',
                'access_id' => 'acc_456',
                'status' => 'AWAITING_CUSTOMER_PAYMENT',
                'amount' => 1000,
                'pay_type' => 'Card',
            ], 200);
        });

        $this->client->createCardPayment(['amount' => 1000, 'payment_id' => 303]);
        $this->client->createCardPayment(['amount' => 1000, 'payment_id' => 303]);

        $this->assertCount(2, $idempotencyKeys);
        $this->assertNotNull($idempotencyKeys[0]);
        $this->assertSame($idempotencyKeys[0], $idempotencyKeys[1]);
    }

    public function test_connection_exception_throws_after_all_retries(): void
    {
        config(['fincode.retry_times' => 3, 'fincode.retry_sleep_ms' => 1]);

        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });

        try {
            $this->client->createCardPayment(['amount' => 1000]);
            $this->fail('Expected FincodeApiException was not thrown');
        } catch (FincodeApiException $e) {
            $this->assertEquals('E01100002', $e->errorCode);
            $this->assertStringContainsString('接続に失敗', $e->getMessage());
            $this->assertInstanceOf(ConnectionException::class, $e->getPrevious());
        }
    }

    // 3DS関連テスト

    public function test_creates_card_payment_with_3ds_successfully(): void
    {
        Http::fake([
            'api.test.fincode.jp/v1/payments' => Http::response([
                'id' => 'pay_3ds_123',
                'access_id' => 'acc_3ds_456',
                'status' => 'AWAITING_CUSTOMER_PAYMENT',
                'amount' => 1500,
                'pay_type' => 'Card',
                'tds2_trans_result' => null,
            ], 200),
        ]);

        $response = $this->client->createCardPaymentWith3ds([
            'amount' => 1500,
            'order_id' => 'order_3ds_123',
            'tds2_ret_url' => 'https://example.com/callback/3ds/1',
        ]);

        $this->assertEquals('pay_3ds_123', $response->id);
        $this->assertEquals('acc_3ds_456', $response->accessId);
        $this->assertEquals(1500, $response->amount);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['tds_type'] === '2'
                && $body['tds2_type'] === '2'
                && $body['tds2_ret_url'] === 'https://example.com/callback/3ds/1';
        });
    }

    public function test_execute_3ds_authentication_returns_authenticated(): void
    {
        Http::fake([
            'api.test.fincode.jp/v1/secure2/acc_3ds_456' => Http::response([
                'id' => 'pay_3ds_123',
                'access_id' => 'acc_3ds_456',
                'tds2_trans_result' => 'Y',
                'status' => 'AWAITING_CUSTOMER_PAYMENT',
            ], 200),
        ]);

        $response = $this->client->execute3dsAuthentication('acc_3ds_456', 'auth_param_token');

        $this->assertEquals('Y', $response->tds2TransResult);
        $this->assertTrue($response->is3dsAuthenticated());
        $this->assertFalse($response->requires3dsChallenge());
    }

    public function test_execute_3ds_authentication_returns_challenge_required(): void
    {
        Http::fake([
            'api.test.fincode.jp/v1/secure2/acc_3ds_456' => Http::response([
                'id' => 'pay_3ds_123',
                'access_id' => 'acc_3ds_456',
                'tds2_trans_result' => 'C',
                'challenge_url' => 'https://acs.example.com/challenge',
                'status' => 'AWAITING_AUTHENTICATION',
            ], 200),
        ]);

        $response = $this->client->execute3dsAuthentication('acc_3ds_456', 'auth_param_token');

        $this->assertEquals('C', $response->tds2TransResult);
        $this->assertTrue($response->requires3dsChallenge());
        $this->assertFalse($response->is3dsAuthenticated());
        $this->assertEquals('https://acs.example.com/challenge', $response->challengeUrl);
    }

    public function test_get_3ds_authentication_result_successfully(): void
    {
        Http::fake([
            'api.test.fincode.jp/v1/secure2/acc_3ds_456*' => Http::response([
                'id' => 'pay_3ds_123',
                'access_id' => 'acc_3ds_456',
                'tds2_trans_result' => 'Y',
                'status' => 'AUTHENTICATED',
            ], 200),
        ]);

        $response = $this->client->get3dsAuthenticationResult('acc_3ds_456');

        $this->assertEquals('Y', $response->tds2TransResult);
        $this->assertTrue($response->is3dsAuthenticated());
    }

    public function test_execute_payment_after_3ds_successfully(): void
    {
        Http::fake([
            'api.test.fincode.jp/v1/payments/pay_3ds_123/secure' => Http::response([
                'id' => 'pay_3ds_123',
                'access_id' => 'acc_3ds_456',
                'status' => 'CAPTURED',
                'amount' => 1500,
            ], 200),
        ]);

        $response = $this->client->executePaymentAfter3ds('pay_3ds_123', 'acc_3ds_456');

        $this->assertEquals('pay_3ds_123', $response->id);
        $this->assertEquals('CAPTURED', $response->status);
        $this->assertTrue($response->isCaptured());
    }

    public function test_3ds_authentication_failed_response(): void
    {
        Http::fake([
            'api.test.fincode.jp/v1/secure2/acc_3ds_456' => Http::response([
                'id' => 'pay_3ds_123',
                'access_id' => 'acc_3ds_456',
                'tds2_trans_result' => 'N',
                'status' => 'AUTHENTICATION_FAILED',
            ], 200),
        ]);

        $response = $this->client->execute3dsAuthentication('acc_3ds_456', 'auth_param_token');

        $this->assertEquals('N', $response->tds2TransResult);
        $this->assertTrue($response->is3dsAuthenticationFailed());
        $this->assertFalse($response->is3dsAuthenticated());
        $this->assertFalse($response->requires3dsChallenge());
    }
}
