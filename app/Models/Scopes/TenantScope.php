<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * @template TModel of Model
 *
 * @implements Scope<TModel>
 */
class TenantScope implements Scope
{
    // Eloquentクエリビルダーにスコープを適用する
    /**
     * @param  Builder<TModel>  $builder
     * @param  TModel  $model
     */
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        $tenantId = $context->getTenantId();

        if ($tenantId !== null) {
            $builder->where($builder->qualifyColumn('tenant_id'), $tenantId);
        } elseif (! app()->runningInConsole()) {
            // Webリクエストでテナントが未設定の場合はデータ漏洩を防止するため空結果を返す
            // コンソール（Seeder、キューワーカー等）では全テナントへのアクセスを許可する
            // 明示的に全テナントデータが必要な場合は withoutGlobalScope(TenantScope::class) を使用する
            $builder->whereRaw('1 = 0');
        }
    }
}
