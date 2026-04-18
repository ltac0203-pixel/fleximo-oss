<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Http\Requests\Auth\DeleteProfileRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\AuditLogger;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    // ユーザーのプロフィール編集フォームを表示する
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    // ユーザーのプロフィール情報を更新する
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    // ユーザーのアカウントを削除する
    public function destroy(DeleteProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        // ログアウト前に監査ログを記録（AuditLogger は auth()->id() でユーザーIDを取得するため）
        AuditLogger::log(
            action: AuditAction::UserDeleted,
            target: $user,
            changes: [
                'old' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                ],
            ],
        );

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
