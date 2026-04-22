<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    // 初回オンボーディングツアーの完了またはスキップを記録する
    public function complete(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->onboarding_completed_at === null) {
            $user->forceFill(['onboarding_completed_at' => now()])->save();
        }

        return back();
    }

    // 「もう一度見る」用。完了状態をクリアして再度ツアーを自動起動させる。
    public function reset(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill(['onboarding_completed_at' => null])->save();

        return back();
    }
}
