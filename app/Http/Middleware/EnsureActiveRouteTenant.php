<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// ルートパラメータのテナントがアクティブであることを要求するミドルウェア
// ルートパラメータに含まれるテナントが非アクティブの場合、404を返す
class EnsureActiveRouteTenant
{
    // 受信したリクエストを処理する。
    // @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->route('tenant');

        if ($tenant instanceof Tenant && ! $tenant->isActive()) {
            return response()->json([
                'message' => 'テナントが見つかりません',
            ], 404);
        }

        return $next($request);
    }
}
