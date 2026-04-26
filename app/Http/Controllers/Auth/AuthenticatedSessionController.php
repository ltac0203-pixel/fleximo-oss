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
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        // 顧客ログイン口から事業者権限へ入れると導線と権限境界が崩れるため、ここで閉じる。
        if ($user->role->isTenantRole() || $user->role === UserRole::Admin) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => trans('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        // 顧客でもテナント横断の共通処理があるため、直後に文脈を揃えておく。
        $user->setTenantContext();

        // 将来 role が増えても意図しない画面へ送らないよう、遷移先を明示しておく。
        $redirectRoute = match ($user->role) {
            UserRole::Customer => route('customer.home', absolute: false),
            default => route('home', absolute: false),
        };

        return redirect()->intended($redirectRoute);
    }

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
