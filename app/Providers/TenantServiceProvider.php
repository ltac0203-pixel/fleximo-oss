<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\TenantContext;
use Illuminate\Support\ServiceProvider;

class TenantServiceProvider extends ServiceProvider
{
    // サービスを登録する。
    public function register(): void
    {
        // リクエストライフサイクル全体で同一テナント情報を共有するためシングルトンにする
        $this->app->singleton(TenantContext::class, function () {
            return new TenantContext;
        });

        // 文字列キーでもDI解決できるよう、Facade的なアクセスを可能にする
        $this->app->alias(TenantContext::class, 'tenant.context');
    }

    // サービスの初期化処理を行う。
    public function boot(): void {}
}
