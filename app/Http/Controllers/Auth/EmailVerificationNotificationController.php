<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailVerificationNotificationController extends Controller
{
    // 新しいメール確認通知を送信する
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::error('Failed to send verification email', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'exception_class' => $e::class,
            ]);

            return back()->with('status', 'verification-link-failed');
        }

        return back()->with('status', 'verification-link-sent');
    }
}
