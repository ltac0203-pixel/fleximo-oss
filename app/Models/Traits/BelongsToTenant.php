<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        // 全クエリにテナント条件を自動付与し、クロステナントデータ漏洩を構造的に防止する
        static::addGlobalScope(new TenantScope);

        // 開発者がtenant_idの設定を忘れてもデータ不整合が起きないよう、自動補完する
        static::creating(function ($model) {
            // Seederやテスト等で明示的に指定された値を尊重するため、上書きしない
            if ($model->tenant_id !== null) {
                return;
            }

            $context = app(TenantContext::class);
            $tenantId = $context->getTenantId();

            if ($tenantId !== null) {
                $model->tenant_id = $tenantId;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // 警告: このメソッドはテスト・開発環境でのみ使用可能です。
    // 本番環境での呼び出しは例外をスローします。
    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        if (app()->environment('production')) {
            Log::critical('SECURITY: withoutTenantScope() called in production', [
                'caller' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3),
            ]);
            throw new \RuntimeException(
                'withoutTenantScope() cannot be used in production environment. '.
                    'This is a security measure to prevent cross-tenant data leakage.'
            );
        }

        return $query->withoutGlobalScope(TenantScope::class);
    }
}
