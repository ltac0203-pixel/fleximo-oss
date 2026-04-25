<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    // ログイン画面を表示する
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    // 認証リクエストを処理する
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        // テナントユーザー・管理者ロールの場合は拒否
        if ($user->role->isTenantRole() || $user->role === UserRole::Admin) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => trans('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        // ログイン後にテナントコンテキスト設定
        $user->setTenantContext();

        // ロール別リダイレクト（顧客のみ）
        $redirectRoute = match ($user->role) {
            UserRole::Customer => route('customer.home', absolute: false),
            default => route('home', absolute: false),
        };

        return redirect()->intended($redirectRoute);
    }

    // 認証済みセッションを破棄する
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isTenantUser = $user && $user->role?->isTenantRole();

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if ($isTenantUser) {
            return redirect()->route('for-business.login');
        }

        return redirect()->route('home');
    }
}
