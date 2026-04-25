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

    // 事業者向け認証リクエストを処理
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        // 顧客ロールの場合は拒否
        if ($user->role === UserRole::Customer) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => trans('auth.failed'),
            ]);
        }

        // テナントロールでテナント未割り当ての場合は拒否
        if ($user->role->isTenantRole() && ! $user->getTenant()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'テナントが割り当てられていません。管理者にお問い合わせください。',
            ]);
        }

        $request->session()->regenerate();

        // 事業者はシングルセッション強制（他セッション即時削除）
        $this->sessionService->deleteOtherSessions($user->id, $request->session()->getId());

        // ログイン後にテナントコンテキスト設定
        $user->setTenantContext();

        // ロール別リダイレクト
        $redirectRoute = match ($user->role) {
            UserRole::Admin => route('admin.dashboard', absolute: false),
            UserRole::TenantAdmin => route('tenant.dashboard', absolute: false),
            UserRole::TenantStaff => route('tenant.kds', absolute: false),
            default => route('for-business.login', absolute: false),
        };

        return redirect()->intended($redirectRoute);
    }
}
