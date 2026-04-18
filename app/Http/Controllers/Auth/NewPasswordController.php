<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\SessionService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    public function __construct(
        private readonly SessionService $sessionService,
    ) {}

    // パスワードリセット画面を表示する
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    // 新しいパスワードリクエストを処理する
    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        // ユーザーのパスワードリセットを試行する
        // 成功した場合は実際のユーザーモデルのパスワードを更新しデータベースに保存する
        // 失敗した場合はエラーを解析してレスポンスを返す
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => $request->password,  // Userモデルのcastsで自動ハッシュ化される
                    'remember_token' => Str::random(60),
                ])->save();

                // パスワードリセット時は全セッションを即時削除（未認証なので現セッションも含む）
                $this->sessionService->deleteAllSessions($user->id);

                event(new PasswordReset($user));
            }
        );

        // パスワードが正常にリセットされた場合、ユーザーをアプリケーションの
        // 認証済みホームビューにリダイレクトする
        // エラーの場合は元のページにエラーメッセージとともにリダイレクトする
        if ($status == Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
