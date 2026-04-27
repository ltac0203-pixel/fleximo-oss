<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;

class OrderNumberGenerator
{
    // 使用するアルファベット（紛らわしい文字を除外: O, I, L を除く）
    private const ALPHA_CHARSET = 'ABCDEFGHJKMNPQRSTUVWXYZ';

    // 注文番号を生成する
    public function generate(int $tenantId, ?Carbon $businessDate = null): string
    {
        // 署名互換のため引数は維持する。重複解消は注文INSERT側のユニーク制約+リトライで扱う。
        return $this->generateRandomOrderCode();
    }

    // アプリのタイムゾーン基準で、深夜0〜5時は前日の営業日を返す。
    public function getBusinessDate(): Carbon
    {
        $now = Carbon::now(config('app.timezone'));

        // 飲食店は深夜営業が多いため、5時までは前日の営業日として扱う
        if ($now->hour < 5) {
            return $now->subDay()->startOfDay();
        }

        return $now->startOfDay();
    }

    // ランダムな注文番号を生成する
    // 形式: [A-Z]{1}[0-9]{3} (例: A001, B123)
    // 紛らわしいアルファベット（O, I, L）を除外
    private function generateRandomOrderCode(): string
    {
        $alpha = self::ALPHA_CHARSET[random_int(0, strlen(self::ALPHA_CHARSET) - 1)];
        $number = str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);

        return $alpha.$number;
    }
}
