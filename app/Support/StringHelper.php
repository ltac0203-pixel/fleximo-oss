<?php

declare(strict_types=1);

namespace App\Support;

class StringHelper
{
    // LIKE 句のメタ文字（% と _）を検索文字列扱いに変換する
    public static function escapeLike(string $value): string
    {
        return str_replace(['%', '_'], ['\%', '\_'], $value);
    }
}
