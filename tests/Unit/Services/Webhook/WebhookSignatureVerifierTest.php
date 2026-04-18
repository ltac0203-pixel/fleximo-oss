<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Webhook;

use App\Services\Webhook\WebhookSignatureVerifier;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhookSignatureVerifierTest extends TestCase
{
    private string $secret = 'test-webhook-secret';

    #[Test]
    public function test_can_compute_signature(): void
    {
        $verifier = new WebhookSignatureVerifier($this->secret);
        $payload = '{"event":"payment.completed","id":"pay_123"}';

        $signature = $verifier->computeSignature($payload);

        $this->assertNotEmpty($signature);
        $this->assertEquals(64, strlen($signature));
    }

    #[Test]
    public function test_verifies_valid_signature(): void
    {
        $verifier = new WebhookSignatureVerifier($this->secret);
        $payload = '{"event":"payment.completed","id":"pay_123"}';
        $signature = hash_hmac('sha256', $payload, $this->secret);

        $request = Request::create('/api/webhooks/payments/1', 'POST', [], [], [], [
            'HTTP_FINCODE_SIGNATURE' => $signature,
        ], $payload);

        $this->assertTrue($verifier->verify($request));
    }

    #[Test]
    public function test_rejects_invalid_signature(): void
    {
        $verifier = new WebhookSignatureVerifier($this->secret);
        $payload = '{"event":"payment.completed","id":"pay_123"}';

        $request = Request::create('/api/webhooks/payments/1', 'POST', [], [], [], [
            'HTTP_FINCODE_SIGNATURE' => 'invalid-signature',
        ], $payload);

        $this->assertFalse($verifier->verify($request));
    }

    #[Test]
    public function test_rejects_missing_signature(): void
    {
        $verifier = new WebhookSignatureVerifier($this->secret);
        $payload = '{"event":"payment.completed","id":"pay_123"}';

        $request = Request::create('/api/webhooks/payments/1', 'POST', [], [], [], [], $payload);

        $this->assertFalse($verifier->verify($request));
    }

    #[Test]
    public function test_rejects_tampered_payload(): void
    {
        $verifier = new WebhookSignatureVerifier($this->secret);
        $originalPayload = '{"event":"payment.completed","id":"pay_123"}';
        $tamperedPayload = '{"event":"payment.completed","id":"pay_456"}';
        $signature = hash_hmac('sha256', $originalPayload, $this->secret);

        $request = Request::create('/api/webhooks/payments/1', 'POST', [], [], [], [
            'HTTP_FINCODE_SIGNATURE' => $signature,
        ], $tamperedPayload);

        $this->assertFalse($verifier->verify($request));
    }

    #[Test]
    public function test_checks_if_secret_is_set(): void
    {
        $verifierWithSecret = new WebhookSignatureVerifier($this->secret);
        $verifierWithoutSecret = new WebhookSignatureVerifier('');

        $this->assertTrue($verifierWithSecret->hasSecret());
        $this->assertFalse($verifierWithoutSecret->hasSecret());
    }

    #[Test]
    public function test_uses_custom_header_name(): void
    {
        $verifier = new WebhookSignatureVerifier($this->secret);
        $payload = '{"event":"payment.completed","id":"pay_123"}';
        $signature = hash_hmac('sha256', $payload, $this->secret);

        $request = Request::create('/api/webhooks/payments/1', 'POST', [], [], [], [
            'HTTP_X_CUSTOM_SIGNATURE' => $signature,
        ], $payload);

        $this->assertTrue($verifier->verify($request, 'X-Custom-Signature'));
    }
}
