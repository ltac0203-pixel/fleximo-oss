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
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        // 登録経路を一本化し、顧客以外の権限はアプリ層から注入できないよう DB デフォルトに委ねる。
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,  // ハッシュ責務をモデルに寄せ、保存経路ごとの差異を防ぐ。
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

        // 初回体験を途切れさせないため、登録直後は必ず顧客ホームへ送る。
        return to_route('customer.home');
    }
}
