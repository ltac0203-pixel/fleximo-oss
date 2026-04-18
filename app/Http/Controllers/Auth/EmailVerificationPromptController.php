<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    // メール確認プロンプトを表示する
    public function __invoke(Request $request): RedirectResponse|Response
    {
        if ($request->user()->hasVerifiedEmail()) {
            $redirectRoute = match ($request->user()->role) {
                UserRole::Admin => route('admin.dashboard', absolute: false),
                UserRole::TenantAdmin,
                UserRole::TenantStaff => route('tenant.dashboard', absolute: false),
                default => route('dashboard', absolute: false),
            };

            return redirect()->intended($redirectRoute);
        }

        return Inertia::render('Auth/VerifyEmail', [
            'status' => session('status'),
        ]);
    }
}
