<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\AddSecurityHeaders::class);
        $middleware->prependToPriorityList(SubstituteBindings::class, \App\Http\Middleware\SetTenantContext::class);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\SetTenantContext::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserRole::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'tenant.context' => \App\Http\Middleware\SetTenantContext::class,
            'tenant.user-assigned' => \App\Http\Middleware\EnsureUserTenantAssignment::class,
            'tenant.user-approved' => \App\Http\Middleware\EnsureApprovedUserTenant::class,
            'tenant.route-active' => \App\Http\Middleware\EnsureActiveRouteTenant::class,
            'idempotent' => \App\Http\Middleware\EnsureIdempotencyKey::class,
            'signed.3ds' => \App\Http\Middleware\ValidateThreeDsCallbackSignature::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'ip.whitelist' => \App\Http\Middleware\RestrictByIpWhitelist::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);

        // ログアウト時は CSRF トークン検証が不要なため、419 例外をリダイレクトで握りつぶす
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->is('logout')) {
                $referer = $request->header('Referer', '');
                if (str_contains($referer, '/tenant')) {
                    return redirect()->route('for-business.login');
                }

                return redirect('/');
            }
        });

        $exceptions->reportable(function (AuthorizationException $e): void {
            Log::warning('Authorization failed', [
                'user_id' => request()->user()?->getAuthIdentifier(),
                'uri' => request()->getRequestUri(),
                'ip' => request()->ip(),
                'message' => $e->getMessage(),
            ]);
        });

        // API レスポンスは JSON を返すため、Inertia のレンダリングは Web 向けリクエストのみに絞る
        $exceptions->respond(function (Response $response, \Throwable $exception, Request $request) {
            $statusCode = $response->getStatusCode();

            // ローカル環境の500/503はデバッグ情報を優先
            if (app()->environment('local') && in_array($statusCode, [500, 503])) {
                return $response;
            }

            $errorPages = [403, 404, 419, 429, 500, 503];

            if (! $request->is('api/*') && in_array($statusCode, $errorPages)) {
                return Inertia::render("Errors/Error{$statusCode}", [
                    'status' => $statusCode,
                ])
                    ->toResponse($request)
                    ->setStatusCode($statusCode);
            }

            return $response;
        });
    })->create();
