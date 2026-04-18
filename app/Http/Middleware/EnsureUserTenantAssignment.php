<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserTenantAssignment
{
    // ユーザーにテナントが割り当てられていることを要求する
    // @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->user()?->getTenant();

        if (! $tenant) {
            abort(403, 'テナントが見つかりません');
        }

        return $next($request);
    }
}
