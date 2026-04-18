<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHeadersMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/test/security-headers', fn () => response('ok'));
    }

    public function test_security_headers_are_attached_to_http_responses(): void
    {
        $response = $this->get('/test/security-headers');

        $response->assertOk();
        $response->assertHeader('Content-Security-Policy');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy');
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        $contentSecurityPolicy = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $contentSecurityPolicy);
        $this->assertStringContainsString("frame-ancestors 'self'", $contentSecurityPolicy);

        preg_match('/script-src[^;]+/', $contentSecurityPolicy, $matches);
        $scriptSrc = $matches[0] ?? '';

        $this->assertNotSame('', $scriptSrc);
        $this->assertStringNotContainsString("'unsafe-inline'", $scriptSrc);
        $this->assertMatchesRegularExpression("/'nonce-[^']+'/", $scriptSrc);
    }

    public function test_hsts_is_added_for_secure_requests(): void
    {
        $response = $this
            ->withServerVariables([
                'HTTPS' => 'on',
                'SERVER_PORT' => '443',
            ])
            ->get('/test/security-headers');

        $response->assertOk();
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
