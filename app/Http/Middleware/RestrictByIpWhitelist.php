<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictByIpWhitelist
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedIps = config('app.health_check_allowed_ips');

        // IP未設定時はアクセス許可（既存環境・ローカル開発を壊さない）
        // 本番環境では HEALTH_CHECK_ALLOWED_IPS にモニタリングサーバーのIPを設定すること
        if (empty($allowedIps)) {
            return $next($request);
        }

        $ipList = array_map('trim', explode(',', $allowedIps));

        if (in_array($request->ip(), $ipList, true)) {
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
    }
}
