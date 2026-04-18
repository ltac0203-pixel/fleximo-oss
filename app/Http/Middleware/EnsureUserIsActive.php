<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    // リクエストを処理する
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            // 全トークン一括削除はユーザー無効化時（Service層）で実施済み
            // ミドルウェアでは現在のAPIトークンのみ削除し、リクエスト毎のDB負荷を抑える
            $currentToken = $user->currentAccessToken();
            if ($currentToken instanceof PersonalAccessToken) {
                $currentToken->delete();
            }

            $message = $this->resolveMessage($user);

            if ($request->expectsJson()) {
                Auth::guard('web')->logout();

                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }

                return response()->json([
                    'message' => $message,
                ], 401);
            }

            Auth::logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            $loginRoute = $request->is('tenant/*') || $request->is('for-business/*')
                ? 'for-business.login'
                : 'login';

            return redirect()->route($loginRoute)
                ->with('error', $message);
        }

        return $next($request);
    }

    // アカウントステータスに応じたメッセージを返す
    private function resolveMessage($user): string
    {
        return match ($user->account_status) {
            AccountStatus::Banned => 'アカウントがBANされています。サポートにお問い合わせください。',
            AccountStatus::Suspended => 'アカウントが一時停止されています。サポートにお問い合わせください。',
            default => 'アカウントが無効化されています。管理者にお問い合わせください。',
        };
    }
}
