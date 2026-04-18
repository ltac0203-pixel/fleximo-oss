<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

trait QueryCountAssertions
{
    // コールバック実行中の総クエリ数をカウントする
    protected function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $callback();

            return count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    // 特定テーブルに対するクエリ数をカウントする
    protected function countQueriesAgainstTable(callable $callback, string $tableName): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $callback();

            return collect(DB::getQueryLog())
                ->pluck('query')
                ->filter(fn (string $query): bool => preg_match(
                    '/\bfrom\s+["`]?'.preg_quote($tableName, '/').'["`]?\b/i',
                    $query
                ) === 1)
                ->count();
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    // コールバック実行中のクエリログを取得する
    protected function captureQueryLog(callable $callback): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $callback();

            return DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    // 総クエリ数がしきい値未満であることをアサートする
    protected function assertQueryCountLessThan(int $threshold, callable $callback, string $message = ''): void
    {
        $count = $this->countQueries($callback);

        $this->assertLessThan(
            $threshold,
            $count,
            $message ?: "Expected fewer than {$threshold} queries, but {$count} were executed."
        );
    }

    // 特定テーブルに対するクエリ数がしきい値未満であることをアサートする
    protected function assertTableQueryCountLessThan(int $threshold, string $tableName, callable $callback, string $message = ''): void
    {
        $count = $this->countQueriesAgainstTable($callback, $tableName);

        $this->assertLessThan(
            $threshold,
            $count,
            $message ?: "Expected fewer than {$threshold} queries against '{$tableName}', but {$count} were executed."
        );
    }
}
