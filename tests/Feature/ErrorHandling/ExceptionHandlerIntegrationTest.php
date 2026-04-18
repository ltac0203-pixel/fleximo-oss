<?php

declare(strict_types=1);

namespace Tests\Feature\ErrorHandling;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExceptionHandlerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_mismatch_on_logout_returns_419(): void
    {
        Route::post('/logout', function () {
            throw new TokenMismatchException;
        })->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        $response = $this->post('/logout');

        // TokenMismatchException は prepareException で HttpException(419) に変換され
        // respond コールバックで Inertia エラーページとして419が返される
        $response->assertStatus(419);
    }

    public function test_token_mismatch_on_other_route_returns_419(): void
    {
        Route::post('/test-csrf-route', function () {
            throw new TokenMismatchException;
        })->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        $response = $this->post('/test-csrf-route');

        $response->assertStatus(419);
    }

    public function test_render_callback_handles_token_mismatch_for_logout(): void
    {
        // render コールバックが TokenMismatchException を直接受け取った場合の動作を検証
        $handler = $this->getExceptionHandler();

        $renderCallbacks = new \ReflectionProperty($handler, 'renderCallbacks');
        $renderCallbacks->setAccessible(true);
        $callbacks = $renderCallbacks->getValue($handler);

        $request = \Illuminate\Http\Request::create('/logout', 'POST');
        $exception = new TokenMismatchException;

        $response = $callbacks[0]($exception, $request);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(url('/'), $response->getTargetUrl());
    }

    public function test_render_callback_redirects_tenant_to_business_login(): void
    {
        $handler = $this->getExceptionHandler();

        $renderCallbacks = new \ReflectionProperty($handler, 'renderCallbacks');
        $renderCallbacks->setAccessible(true);
        $callbacks = $renderCallbacks->getValue($handler);

        $request = \Illuminate\Http\Request::create('/logout', 'POST', server: ['HTTP_REFERER' => 'https://example.com/tenant/dashboard']);
        $exception = new TokenMismatchException;

        $response = $callbacks[0]($exception, $request);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('for-business/login', $response->getTargetUrl());
    }

    private function getExceptionHandler(): \Illuminate\Foundation\Exceptions\Handler
    {
        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);

        // Collision ラッパーの場合は内部ハンドラーを取得
        if ($handler instanceof \Illuminate\Foundation\Exceptions\Handler) {
            return $handler;
        }

        $inner = new \ReflectionProperty($handler, 'appExceptionHandler');
        $inner->setAccessible(true);

        return $inner->getValue($handler);
    }

    public function test_api_404_returns_json(): void
    {
        $response = $this->getJson('/api/nonexistent-route');

        $response->assertStatus(404)->assertJson([]);
    }

    public function test_web_404_returns_inertia_error_page(): void
    {
        $response = $this->get('/nonexistent-page');

        $response->assertStatus(404);
    }
}
