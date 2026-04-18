<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    // 登録画面を表示する
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    // ユーザー登録リクエストを処理する
    public function store(RegisterRequest $request): RedirectResponse
    {
        // roleはDBデフォルト値(customer)で設定される
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,  // Userモデルのcastsで自動ハッシュ化される
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            Log::error('Failed to send registration email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // 新規登録ユーザーはcustomerなので顧客ホームへリダイレクト
        return to_route('customer.home');
    }
}
