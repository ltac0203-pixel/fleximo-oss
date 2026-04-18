<?php

declare(strict_types=1);

namespace App\Services\Concerns;

trait SanitizesCsvExport
{
    // CSVインジェクション防止のため、危険な先頭文字をエスケープする
    private function sanitizeCsvValue(mixed $value): string|int|float
    {
        if (! is_string($value)) {
            return $value;
        }

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    // 配列の全値をCSVサニタイズする
    private function sanitizeCsvRow(array $row): array
    {
        return array_map(fn ($v) => $this->sanitizeCsvValue($v), $row);
    }
}
