<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailVerificationRequest;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    // メールアドレスを確認済みにする（未ログインでも署名付きURLで検証可能）
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->userToVerify();

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        // ログイン済みならダッシュボードへ
        if ($request->user()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        // 未ログインの場合、ロールに応じたログインページへリダイレクト
        if ($user->hasTenantRole() || $user->isAdmin()) {
            return redirect()->route('for-business.login')
                ->with('status', 'メールアドレスの認証が完了しました。ログインしてください。');
        }

        return redirect()->route('login')
            ->with('status', 'メールアドレスの認証が完了しました。ログインしてください。');
    }
}
