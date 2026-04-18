<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\UpdateTenantProfileData;
use App\Enums\AuditAction;
use App\Events\TenantOrderPaused;
use App\Http\Requests\SearchTenantsRequest;
use App\Models\Tenant;
use App\Support\StringHelper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// テナント関連のビジネスロジックを担当するサービス
class TenantService
{
    private const ACTIVE_TENANTS_CACHE_KEY = 'tenants:active_list';

    private const ACTIVE_TENANTS_CACHE_TTL = 1800; // 30分

    private const ACTIVE_TENANTS_MAX_LIMIT = 100;

    // テナントを検索する
    public function search(SearchTenantsRequest $request): LengthAwarePaginator
    {
        $perPage = $request->integer('per_page', 20);
        $query = $request->string('query')->toString();

        return Tenant::active()
            ->with('businessHours')
            ->search($query)
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function getActiveTenants(): Collection
    {
        return Cache::remember(self::ACTIVE_TENANTS_CACHE_KEY, self::ACTIVE_TENANTS_CACHE_TTL, function () {
            return Tenant::active()
                ->with('businessHours')
                ->orderBy('name')
                ->limit(self::ACTIVE_TENANTS_MAX_LIMIT)
                ->get();
        });
    }

    // アクティブテナント一覧のキャッシュを無効化する
    public static function invalidateActiveTenantListCache(): void
    {
        Cache::forget(self::ACTIVE_TENANTS_CACHE_KEY);
    }

    // Shop ID管理画面用のテナント検索
    public function searchForShopIdManagement(?string $search): LengthAwarePaginator
    {
        return Tenant::query()
            ->when($search, function ($query, $search) {
                $escaped = StringHelper::escapeLike($search);
                $query->where(function ($q) use ($escaped) {
                    $q->where('name', 'like', "%{$escaped}%")
                        ->orWhere('email', 'like', "%{$escaped}%")
                        ->orWhere('fincode_shop_id', 'like', "%{$escaped}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();
    }

    // 注文受付の一時停止/再開をトグルする
    public function toggleOrderPause(Tenant $tenant): Tenant
    {
        if ($tenant->is_order_paused) {
            $tenant->resumeOrders();
            $action = AuditAction::OrderResumed;
        } else {
            $tenant->pauseOrders();
            $action = AuditAction::OrderPaused;
        }

        Cache::forget("tenant:{$tenant->id}:profile");
        self::invalidateActiveTenantListCache();

        AuditLogger::log(
            action: $action,
            target: $tenant,
            changes: [
                'new' => ['is_order_paused' => $tenant->is_order_paused],
            ],
            tenantId: $tenant->id
        );

        $freshTenant = $tenant->fresh();

        event(new TenantOrderPaused($freshTenant, $freshTenant->is_order_paused));

        return $freshTenant;
    }

    // テナントプロフィールを更新する
    public function updateProfile(Tenant $tenant, UpdateTenantProfileData $data): Tenant
    {
        return DB::transaction(function () use ($tenant, $data) {
            $hasBusinessHours = $data->hasBusinessHours();
            $allData = $data->toArray();
            $businessHours = $allData['business_hours'] ?? [];
            unset($allData['business_hours']);

            // フォームで未入力のフィールドがnullで渡されるため、既存値を上書きしないよう除外する
            $profileData = array_filter($allData, fn ($value) => $value !== null);

            $oldAttributes = $tenant->only(array_keys($profileData));

            if (! empty($profileData)) {
                $tenant->update($profileData);
            }

            $oldBusinessHours = null;
            if ($hasBusinessHours) {
                $oldBusinessHours = $tenant->businessHours()
                    ->orderBy('weekday')
                    ->orderBy('sort_order')
                    ->get(['weekday', 'open_time', 'close_time', 'sort_order'])
                    ->map(fn ($hour) => [
                        'weekday' => $hour->weekday,
                        'open_time' => $hour->open_time,
                        'close_time' => $hour->close_time,
                        'sort_order' => $hour->sort_order,
                    ])
                    ->all();

                $tenant->businessHours()->delete();

                $sortOrders = [];
                $rows = [];
                foreach ($businessHours as $hour) {
                    $weekday = (int) $hour['weekday'];
                    $sortOrders[$weekday] = ($sortOrders[$weekday] ?? 0) + 1;

                    $rows[] = [
                        'weekday' => $weekday,
                        'open_time' => $hour['open_time'],
                        'close_time' => $hour['close_time'],
                        'sort_order' => $sortOrders[$weekday] - 1,
                    ];
                }

                if (! empty($rows)) {
                    $tenant->businessHours()->createMany($rows);
                }
            }

            Cache::forget("tenant:{$tenant->id}:profile");
            self::invalidateActiveTenantListCache();

            $changes = [
                'old' => $oldAttributes,
                'new' => $tenant->only(array_keys($profileData)),
            ];

            if ($hasBusinessHours) {
                $newBusinessHours = $tenant->businessHours()
                    ->orderBy('weekday')
                    ->orderBy('sort_order')
                    ->get(['weekday', 'open_time', 'close_time', 'sort_order'])
                    ->map(fn ($hour) => [
                        'weekday' => $hour->weekday,
                        'open_time' => $hour->open_time,
                        'close_time' => $hour->close_time,
                        'sort_order' => $hour->sort_order,
                    ])
                    ->all();

                $changes['old']['business_hours'] = $oldBusinessHours;
                $changes['new']['business_hours'] = $newBusinessHours;
            }

            AuditLogger::log(
                action: AuditAction::TenantUpdated,
                target: $tenant,
                changes: $changes,
                tenantId: $tenant->id
            );

            return $tenant;
        });
    }
}
