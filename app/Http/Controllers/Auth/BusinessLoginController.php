<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\SessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class BusinessLoginController extends Controller
{
    public function __construct(
        private readonly SessionService $sessionService,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Auth/BusinessLogin', [
            'status' => session('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        // 事業者用の導線に顧客が入ると権限エラーではなくログイン失敗として見せた方が情報漏えいを防げる。
        if ($user->role === UserRole::Customer) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => trans('auth.failed'),
            ]);
        }

        // 所属テナントがない事業者を通すと後続画面が成立しないため、ここで早めに止める。
        if ($user->role->isTenantRole() && ! $user->getTenant()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'テナントが割り当てられていません。管理者にお問い合わせください。',
            ]);
        }

        $request->session()->regenerate();

        // 共用端末運用を想定し、事業者側は多重ログインより取り違え防止を優先する。
        $this->sessionService->deleteOtherSessions($user->id, $request->session()->getId());

        // 事業者画面はテナント文脈前提のため、遷移前に確定させておく。
        $user->setTenantContext();

        // 権限ごとに最初の作業画面を分け、不要な回遊を避ける。
        $redirectRoute = match ($user->role) {
            UserRole::Admin => route('admin.dashboard', absolute: false),
            UserRole::TenantAdmin => route('tenant.dashboard', absolute: false),
            UserRole::TenantStaff => route('tenant.kds', absolute: false),
            default => route('for-business.login', absolute: false),
        };

        return redirect()->intended($redirectRoute);
    }
}
