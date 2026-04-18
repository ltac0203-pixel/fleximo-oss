<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            $loginRoute = $request->is('tenant/*') || $request->is('for-business/*')
                ? 'for-business.login'
                : 'login';

            return redirect()->route($loginRoute);
        }

        $allowedRoles = array_filter(
            array_map(fn (string $role) => UserRole::tryFrom($role), $roles),
            fn ($role) => $role !== null
        );

        // 無効なロール設定の検出（開発時のミス検知）
        if (empty($allowedRoles)) {
            abort(500, 'Invalid role configuration');
        }

        if (in_array($user->role, $allowedRoles, true)) {
            return $next($request);
        }

        abort(403, 'このアクションを実行する権限がありません。');
    }
}
