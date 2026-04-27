<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    private const HSTS_POLICY = 'max-age=31536000; includeSubDomains';

    private const REFERRER_POLICY = 'strict-origin-when-cross-origin';

    private const PERMISSIONS_POLICY = 'accelerometer=(), autoplay=(), camera=(), display-capture=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(self), publickey-credentials-get=(self), usb=()';

    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);

        $this->setIfMissing(
            $response,
            'Content-Security-Policy',
            $this->buildContentSecurityPolicy($request, Vite::cspNonce())
        );
        $this->setIfMissing($response, 'X-Frame-Options', 'SAMEORIGIN');
        $this->setIfMissing($response, 'X-Content-Type-Options', 'nosniff');
        $this->setIfMissing($response, 'Referrer-Policy', self::REFERRER_POLICY);
        $this->setIfMissing($response, 'Permissions-Policy', self::PERMISSIONS_POLICY);

        $this->setIfMissing($response, 'Strict-Transport-Security', self::HSTS_POLICY);

        if ($request->user()) {
            $this->setIfMissing($response, 'Cache-Control', 'no-store, no-cache, must-revalidate');
            $this->setIfMissing($response, 'Pragma', 'no-cache');
        }

        return $response;
    }

    private function buildContentSecurityPolicy(Request $request, ?string $nonce): string
    {
        $scriptSrc = [
            "'self'",
            'https://js.fincode.jp',
            'https://js.test.fincode.jp',
        ];

        if ($nonce !== null) {
            $scriptSrc[] = "'nonce-{$nonce}'";
        }

        $connectSrc = [
            "'self'",
            'https://api.fincode.jp',
            'https://api.test.fincode.jp',
        ];

        if (app()->environment('local', 'testing')) {
            $scriptSrc[] = "'unsafe-eval'";
            $scriptSrc[] = 'http://localhost:*';
            $scriptSrc[] = 'http://127.0.0.1:*';
            $scriptSrc[] = 'http://[::1]:*';

            $connectSrc[] = 'http://localhost:*';
            $connectSrc[] = 'http://127.0.0.1:*';
            $connectSrc[] = 'http://[::1]:*';
            $connectSrc[] = 'ws://localhost:*';
            $connectSrc[] = 'ws://127.0.0.1:*';
            $connectSrc[] = 'ws://[::1]:*';
        }

        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "frame-src 'self' https://*.fincode.jp https://fincode.jp",
            "object-src 'none'",
            'script-src '.implode(' ', array_unique($scriptSrc)),
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "style-src-elem 'self' 'nonce-{$nonce}' https://fonts.bunny.net",
            "style-src-attr 'unsafe-inline'",
            "font-src 'self' data: https://fonts.bunny.net",
            "img-src 'self' data: blob: https:",
            'connect-src '.implode(' ', array_unique($connectSrc)),
        ];

        if ($request->isSecure()) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }

    private function setIfMissing(Response $response, string $name, string $value): void
    {
        if (! $response->headers->has($name)) {
            $response->headers->set($name, $value);
        }
    }
}
