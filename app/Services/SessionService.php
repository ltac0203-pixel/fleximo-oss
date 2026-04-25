<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SessionService
{
    // 指定ユーザーの現在セッション以外を削除（事業者ログイン時のシングルセッション強制で使用）
    public function deleteOtherSessions(int $userId, string $currentSessionId): int
    {
        return DB::table('sessions')
            ->where('user_id', $userId)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }
}
