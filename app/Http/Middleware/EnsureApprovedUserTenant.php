<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApprovedUserTenant
{
    // 未承認テナントでもダッシュボード確認とログアウトは許可し、承認待機状態のままシステムを利用不能にしない
    protected array $allowedRoutes = [
        'tenant.dashboard',
        'tenant.dashboard.summary',
        'tenant.dashboard.hourly',
        'tenant.dashboard.sales',
        'tenant.dashboard.top-items',
        'tenant.dashboard.payment-methods',
        'tenant.dashboard.customer-insights',
        'tenant.dashboard.export.csv',
        'tenant.profile.api.show',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                abort(401, '認証が必要です。');
            }

            $loginRoute = $request->is('tenant/*') || $request->is('for-business/*')
                ? 'for-business.login'
                : 'login';

            return redirect()->route($loginRoute);
        }

        $tenant = $user->getTenant();

        if ($tenant->isApproved()) {
            return $next($request);
        }

        $currentRoute = $request->route()?->getName();

        if (in_array($currentRoute, $this->allowedRoutes, true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'テナントが承認されていません。');
        }

        return redirect()->route('tenant.dashboard');
    }
}
