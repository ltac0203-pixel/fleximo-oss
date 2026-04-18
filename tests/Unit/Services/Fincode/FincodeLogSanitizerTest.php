<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Fincode;

use App\Services\Fincode\FincodeLogSanitizer;
use Tests\TestCase;

class FincodeLogSanitizerTest extends TestCase
{
    public function test_sanitize_uses_whitelist_approach(): void
    {
        $sanitizer = new FincodeLogSanitizer;
        $data = [
            // ホワイトリスト内 (許可)
            'id' => 'pay_12345',
            'status' => 'CAPTURED',
            'amount' => 1000,
            'pay_type' => 'Card',
            'error_code' => null,
            'client_field_1' => 'order_123',
            'tds2_trans_result' => 'Y',

            // 既知キーだが機微情報のため常時マスク
            'link_url' => 'https://paypay.example.com/checkout',
            'code_url' => 'https://paypay.example.com/code',
            'tds2_ret_url' => 'https://example.com/callback/3ds',
            'return_url' => 'https://example.com/callback/return',
            'challenge_url' => 'https://acs.example.com/challenge',
            'redirect_url' => 'https://acs.example.com/redirect',
            'acs_url' => 'https://acs.example.com/method',
            'customer_id' => 'cus_123',
            'brand' => 'VISA',
            'expire' => '2612',
            'default_flag' => '1',

            // ホワイトリスト外 (マスク対象)
            'access_id' => 'a_test_12345',
            'token' => 'tok_test_12345',
            'shop_id' => 's_shop_67890',
            'api_key' => 'dummy_api_key_value',
            'secret_key' => 'secret_xyz',
            'card_no' => '4111111111111111',
            'cvv' => '123',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'unknown_field' => 'some_value',
        ];

        $result = $sanitizer->sanitize($data);

        $this->assertEquals('pay_12345', $result['id']);
        $this->assertEquals('CAPTURED', $result['status']);
        $this->assertEquals(1000, $result['amount']);
        $this->assertEquals('Card', $result['pay_type']);
        $this->assertEquals('order_123', $result['client_field_1']);
        $this->assertEquals('Y', $result['tds2_trans_result']);
        $this->assertEquals('***MASKED***', $result['link_url']);
        $this->assertEquals('***MASKED***', $result['code_url']);
        $this->assertEquals('***MASKED***', $result['tds2_ret_url']);
        $this->assertEquals('***MASKED***', $result['return_url']);
        $this->assertEquals('***MASKED***', $result['challenge_url']);
        $this->assertEquals('***MASKED***', $result['redirect_url']);
        $this->assertEquals('***MASKED***', $result['acs_url']);
        $this->assertEquals('***MASKED***', $result['customer_id']);
        $this->assertEquals('***MASKED***', $result['brand']);
        $this->assertEquals('***MASKED***', $result['expire']);
        $this->assertEquals('***MASKED***', $result['default_flag']);
        $this->assertEquals('***MASKED***', $result['access_id']);
        $this->assertEquals('***MASKED***', $result['token']);
        $this->assertEquals('***MASKED***', $result['shop_id']);
        $this->assertEquals('***MASKED***', $result['api_key']);
        $this->assertEquals('***MASKED***', $result['secret_key']);
        $this->assertEquals('***MASKED***', $result['card_no']);
        $this->assertEquals('***MASKED***', $result['cvv']);
        $this->assertEquals('***MASKED***', $result['name']);
        $this->assertEquals('***MASKED***', $result['email']);
        $this->assertEquals('***MASKED***', $result['unknown_field']);
    }

    public function test_sanitize_handles_nested_arrays(): void
    {
        $sanitizer = new FincodeLogSanitizer;
        $data = [
            'id' => 'cus_123',
            'list' => [
                [
                    'id' => 'card_1',
                    'brand' => 'VISA',
                    'redirect_url' => 'https://acs.example.com/redirect',
                    'card_no' => '****1234',
                    'token' => 'tok_123',
                ],
                [
                    'id' => 'card_2',
                    'brand' => 'MASTERCARD',
                    'expire' => '2612',
                    'customer_id' => 'cus_2',
                    'default_flag' => '1',
                    'name' => 'Jane Doe',
                ],
            ],
        ];

        $result = $sanitizer->sanitize($data);

        $this->assertEquals('cus_123', $result['id']);
        $this->assertEquals('card_1', $result['list'][0]['id']);
        $this->assertEquals('***MASKED***', $result['list'][0]['brand']);
        $this->assertEquals('***MASKED***', $result['list'][0]['redirect_url']);
        $this->assertEquals('***MASKED***', $result['list'][0]['card_no']);
        $this->assertEquals('***MASKED***', $result['list'][0]['token']);
        $this->assertEquals('card_2', $result['list'][1]['id']);
        $this->assertEquals('***MASKED***', $result['list'][1]['brand']);
        $this->assertEquals('***MASKED***', $result['list'][1]['expire']);
        $this->assertEquals('***MASKED***', $result['list'][1]['customer_id']);
        $this->assertEquals('***MASKED***', $result['list'][1]['default_flag']);
        $this->assertEquals('***MASKED***', $result['list'][1]['name']);
    }

    public function test_sanitize_masks_unknown_future_fields(): void
    {
        $sanitizer = new FincodeLogSanitizer;
        $data = [
            'id' => 'pay_123',
            'status' => 'CAPTURED',
            'future_sensitive_field' => 'secret_value',
            'another_unknown_field' => 'confidential',
        ];

        $result = $sanitizer->sanitize($data);

        $this->assertEquals('pay_123', $result['id']);
        $this->assertEquals('CAPTURED', $result['status']);
        $this->assertEquals('***MASKED***', $result['future_sensitive_field']);
        $this->assertEquals('***MASKED***', $result['another_unknown_field']);
    }

    public function test_sanitize_masks_all_sensitive_payment_metadata(): void
    {
        $sanitizer = new FincodeLogSanitizer;
        $data = [
            'status' => 'AWAITING_CUSTOMER_PAYMENT',
            'tds2_trans_result' => 'C',
            'link_url' => 'https://paypay.example.com/checkout',
            'code_url' => 'https://paypay.example.com/code',
            'tds2_ret_url' => 'https://example.com/callback/3ds',
            'return_url' => 'https://example.com/callback/return',
            'challenge_url' => 'https://acs.example.com/challenge',
            'redirect_url' => 'https://acs.example.com/redirect',
            'acs_url' => 'https://acs.example.com/method',
            'customer_id' => 'cus_sensitive',
            'brand' => 'JCB',
            'expire' => '3001',
            'default_flag' => '1',
        ];

        $result = $sanitizer->sanitize($data);

        $this->assertEquals('AWAITING_CUSTOMER_PAYMENT', $result['status']);
        $this->assertEquals('C', $result['tds2_trans_result']);
        $this->assertEquals('***MASKED***', $result['link_url']);
        $this->assertEquals('***MASKED***', $result['code_url']);
        $this->assertEquals('***MASKED***', $result['tds2_ret_url']);
        $this->assertEquals('***MASKED***', $result['return_url']);
        $this->assertEquals('***MASKED***', $result['challenge_url']);
        $this->assertEquals('***MASKED***', $result['redirect_url']);
        $this->assertEquals('***MASKED***', $result['acs_url']);
        $this->assertEquals('***MASKED***', $result['customer_id']);
        $this->assertEquals('***MASKED***', $result['brand']);
        $this->assertEquals('***MASKED***', $result['expire']);
        $this->assertEquals('***MASKED***', $result['default_flag']);
    }
}
