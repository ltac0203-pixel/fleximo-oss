<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Notifications\PasswordChangedNotification;
use App\Services\SessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    public function __construct(
        private readonly SessionService $sessionService,
    ) {}

    // ユーザーのパスワードを更新する
    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => $request->validated('password'),  // Userモデルのcastsで自動ハッシュ化される
            'remember_token' => Str::random(60),
        ])->save();

        // 他デバイスのセッションを即時削除
        $this->sessionService->deleteOtherSessions(
            $request->user()->id,
            $request->session()->getId(),
        );

        $request->user()->notify(new PasswordChangedNotification);

        return back();
    }
}
