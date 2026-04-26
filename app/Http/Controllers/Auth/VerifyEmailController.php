<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailVerificationRequest;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->userToVerify();

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        // 既存セッションがある場合は再ログインを挟まず、そのまま元の利用導線へ戻す。
        if ($request->user()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        // 未ログイン時は次に踏むべき入口を role ごとに分け、案内の迷子を防ぐ。
        if ($user->hasTenantRole() || $user->isAdmin()) {
            return redirect()->route('for-business.login')
                ->with('status', 'メールアドレスの認証が完了しました。ログインしてください。');
        }

        return redirect()->route('login')
            ->with('status', 'メールアドレスの認証が完了しました。ログインしてください。');
    }
}
