<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Fincode;

use App\Enums\PaymentStatus;
use App\Services\Fincode\FincodePaymentResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FincodePaymentResponseTest extends TestCase
{
    public function test_creates_instance_from_array(): void
    {
        $data = [
            'id' => 'p_123456',
            'access_id' => 'a_789012',
            'status' => 'CAPTURED',
            'amount' => 1000,
            'pay_type' => 'Card',
            'client_field_1' => '123',
        ];

        $response = FincodePaymentResponse::fromArray($data);

        $this->assertEquals('p_123456', $response->id);
        $this->assertEquals('a_789012', $response->accessId);
        $this->assertEquals('CAPTURED', $response->status);
        $this->assertEquals(1000, $response->amount);
        $this->assertEquals('Card', $response->payType);
        $this->assertEquals('123', $response->clientField1);
        $this->assertNull($response->errorCode);
        $this->assertNull($response->linkUrl);
    }

    public function test_creates_instance_with_paypay_data(): void
    {
        $data = [
            'id' => 's_123456',
            'link_url' => 'https://paypay.example.com/checkout',
            'pay_type' => 'Paypay',
        ];

        $response = FincodePaymentResponse::fromArray($data);

        $this->assertEquals('s_123456', $response->id);
        $this->assertEquals('https://paypay.example.com/checkout', $response->linkUrl);
        $this->assertEquals('Paypay', $response->payType);
    }

    public function test_creates_instance_with_error(): void
    {
        $data = [
            'id' => null,
            'error_code' => 'E01100101',
        ];

        $response = FincodePaymentResponse::fromArray($data);

        $this->assertNull($response->id);
        $this->assertEquals('E01100101', $response->errorCode);
    }

    public function test_is_success_returns_true_when_no_error(): void
    {
        $response = FincodePaymentResponse::fromArray([
            'id' => 'p_123456',
            'status' => 'CAPTURED',
        ]);

        $this->assertTrue($response->isSuccess());
    }

    public function test_is_success_returns_false_when_error_exists(): void
    {
        $response = FincodePaymentResponse::fromArray([
            'error_code' => 'E01100101',
        ]);

        $this->assertFalse($response->isSuccess());
    }

    public function test_is_captured_returns_true_for_captured_status(): void
    {
        $response = FincodePaymentResponse::fromArray([
            'status' => 'CAPTURED',
        ]);

        $this->assertTrue($response->isCaptured());
    }

    public function test_is_captured_returns_false_for_other_status(): void
    {
        $response = FincodePaymentResponse::fromArray([
            'status' => 'UNPROCESSED',
        ]);

        $this->assertFalse($response->isCaptured());
    }

    public function test_requires_redirect_returns_true_when_link_url_exists(): void
    {
        $response = FincodePaymentResponse::fromArray([
            'link_url' => 'https://paypay.example.com/checkout',
        ]);

        $this->assertTrue($response->requiresRedirect());
    }

    public function test_requires_redirect_returns_false_when_no_link_url(): void
    {
        $response = FincodePaymentResponse::fromArray([
            'id' => 'p_123456',
        ]);

        $this->assertFalse($response->requiresRedirect());
    }

    public function test_acs_url_from_redirect_url(): void
    {
        $response = FincodePaymentResponse::fromArray([
            'id' => 'p_123456',
            'redirect_url' => 'https://3ds.example.com/redirect',
        ]);

        $this->assertEquals('https://3ds.example.com/redirect', $response->acsUrl);
    }

    public function test_acs_url_fallback_from_acs_url_field(): void
    {
        $response = FincodePaymentResponse::fromArray([
            'id' => 'p_123456',
            'acs_url' => 'https://3ds.example.com/acs',
        ]);

        $this->assertEquals('https://3ds.example.com/acs', $response->acsUrl);
    }

    public function test_acs_url_prefers_redirect_url_over_acs_url(): void
    {
        $response = FincodePaymentResponse::fromArray([
            'id' => 'p_123456',
            'redirect_url' => 'https://3ds.example.com/redirect',
            'acs_url' => 'https://3ds.example.com/acs',
        ]);

        $this->assertEquals('https://3ds.example.com/redirect', $response->acsUrl);
    }

    #[DataProvider('statusMappingProvider')]
    public function test_to_payment_status_conversion(string $fincodeStatus, PaymentStatus $expectedStatus): void
    {
        $response = FincodePaymentResponse::fromArray([
            'status' => $fincodeStatus,
        ]);

        $this->assertEquals($expectedStatus, $response->toPaymentStatus());
    }

    public static function statusMappingProvider(): array
    {
        return [
            'UNPROCESSED maps to Pending' => ['UNPROCESSED', PaymentStatus::Pending],
            'AWAITING_AUTHENTICATION maps to Processing' => ['AWAITING_AUTHENTICATION', PaymentStatus::Processing],
            'AUTHORIZED maps to Processing' => ['AUTHORIZED', PaymentStatus::Processing],
            'CAPTURED maps to Completed' => ['CAPTURED', PaymentStatus::Completed],
            'CANCELED maps to Failed' => ['CANCELED', PaymentStatus::Failed],
            'ERROR maps to Failed' => ['ERROR', PaymentStatus::Failed],
            'unknown status maps to Pending' => ['UNKNOWN', PaymentStatus::Pending],
        ];
    }
}
