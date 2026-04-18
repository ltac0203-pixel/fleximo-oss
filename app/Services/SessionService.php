<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SessionService
{
    // 指定ユーザーの現在セッション以外を削除（ログイン時・パスワード変更時）
    public function deleteOtherSessions(int $userId, string $currentSessionId): int
    {
        return DB::table('sessions')
            ->where('user_id', $userId)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }

    // 指定ユーザーの全セッションを削除（パスワードリセット時）
    public function deleteAllSessions(int $userId): int
    {
        return DB::table('sessions')
            ->where('user_id', $userId)
            ->delete();
    }
}
