<?php

declare(strict_types=1);

use App\Jobs\AggregateDailyAnalyticsJob;
use App\Jobs\AggregateMonthlyAnalyticsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Analytics Aggregation Jobs
|--------------------------------------------------------------------------
|
| 分析データの定期集計ジョブをスケジュール設定します。
| - 日次集計: 毎日深夜2時に前日分を集計
| - 月次集計: 毎月1日深夜3時に前月分を集計
|
*/

// 日次分析集計（毎日 02:00 に前日分を集計）
Schedule::job(new AggregateDailyAnalyticsJob(now()->subDay()))
    ->dailyAt('02:00')
    ->onOneServer()
    ->withoutOverlapping()
    ->name('analytics:aggregate-daily');

// 月次分析集計（毎月1日 03:00 に前月分を集計）
Schedule::job(new AggregateMonthlyAnalyticsJob(
    now()->subMonth()->year,
    now()->subMonth()->month
))
    ->monthlyOn(1, '03:00')
    ->onOneServer()
    ->withoutOverlapping()
    ->name('analytics:aggregate-monthly');

// 期限切れカート日次クリア（毎日 00:05 に前日以前のカートを削除）
Schedule::command('carts:clear-expired')
    ->dailyAt('00:05')
    ->onOneServer()
    ->withoutOverlapping()
    ->name('carts:clear-expired');

// Sitemap生成（毎日 01:00 に最新のサイトマップを生成）
Schedule::command('sitemap:generate')
    ->dailyAt('01:00')
    ->onOneServer()
    ->withoutOverlapping()
    ->name('sitemap:generate');

// 準備完了注文の自動完了（5分ごとに実行）
Schedule::command('orders:auto-complete-ready')
    ->everyFiveMinutes()
    ->onOneServer()
    ->withoutOverlapping()
    ->name('orders:auto-complete-ready');

// 放棄カート検出（毎時実行）
Schedule::command('carts:detect-abandoned')
    ->hourly()
    ->onOneServer()
    ->withoutOverlapping()
    ->name('carts:detect-abandoned');
