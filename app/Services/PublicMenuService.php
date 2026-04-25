<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Tenant;
use App\Support\MenuCacheKeys;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PublicMenuService
{
    // キャッシュの有効期間（秒）
    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly TenantContext $tenantContext
    ) {}

    public function getMenu(Tenant $tenant): array
    {
        $cachedData = Cache::remember(MenuCacheKeys::menu($tenant->id), self::CACHE_TTL, function () use ($tenant) {
            return $this->buildMenuData($tenant);
        });

        // 時間帯・曜日制限のあるアイテムが存在する場合のみ、リクエスト時点で再判定する
        if ($cachedData['has_restricted_items']) {
            $cachedData = $this->recalculateAvailability($cachedData);
        }

        return $this->stripInternalFlags($cachedData);
    }

    public function invalidateCache(int $tenantId): void
    {
        MenuCacheKeys::invalidate($tenantId);
    }

    // メニューデータを構築する
    private function buildMenuData(Tenant $tenant): array
    {
        // TenantScopeがグローバルに適用されるため、対象テナントのコンテキストに一時的に切り替える
        $previousTenantId = $this->tenantContext->getTenantId();

        try {
            $this->tenantContext->setTenantInstance($tenant);

            $categories = MenuCategory::where('tenant_id', $tenant->id)
                ->active()
                ->ordered()
                ->with([
                    'menuItems' => function ($query) {
                        $query->active()
                            ->ordered()
                            ->with([
                                'optionGroups' => function ($q) {
                                    $q->active()
                                        ->ordered()
                                        ->with([
                                            'options' => function ($optionQuery) {
                                                $optionQuery->active()->ordered();
                                            },
                                        ]);
                                },
                            ]);
                    },
                ])
                ->get();

            $categories = $categories->map(fn (MenuCategory $category) => $this->formatCategory($category))->all();

            $hasRestrictedItems = false;
            foreach ($categories as $category) {
                foreach ($category['items'] as $item) {
                    if ($item['needs_recalc']) {
                        $hasRestrictedItems = true;
                        break 2;
                    }
                }
            }

            return [
                'categories' => $categories,
                'has_restricted_items' => $hasRestrictedItems,
            ];
        } finally {
            // 他のリクエスト処理に影響しないよう、変更前のテナントコンテキストに必ず復元する
            if ($previousTenantId !== null) {
                $this->tenantContext->setTenant($previousTenantId);
            } else {
                $this->tenantContext->clear();
            }
        }
    }

    private function formatCategory(MenuCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'sort_order' => $category->sort_order,
            'items' => $category->menuItems->map(fn (MenuItem $item) => $this->formatMenuItem($item))->all(),
        ];
    }

    private function formatMenuItem(MenuItem $item): array
    {
        $needsRecalc = $item->available_days !== MenuItem::ALL_DAYS
            || $item->available_from !== null
            || $item->available_until !== null;

        return [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'price' => $item->price,
            'is_sold_out' => $item->is_sold_out,
            'is_available' => ! $item->is_sold_out,
            'needs_recalc' => $needsRecalc,
            'available_from' => $item->available_from,
            'available_until' => $item->available_until,
            'available_days' => $item->available_days,
            'allergens' => $item->allergens,
            'allergen_advisories' => $item->allergen_advisories,
            'allergen_labels' => $item->getAllergenLabels(),
            'advisory_labels' => $item->getAdvisoryLabels(),
            'allergen_note' => $item->allergen_note,
            'nutrition_info' => $item->nutrition_info,
            'option_groups' => $item->optionGroups->map(fn ($optionGroup) => $this->formatOptionGroup($optionGroup))->all(),
        ];
    }

    // 出力構造は OpenAPI components.schemas.PublicOptionGroup / PublicOption と
    // 厳密に一致させること（公開メニュー API の互換性維持のため）
    private function formatOptionGroup($optionGroup): array
    {
        return [
            'id' => $optionGroup->id,
            'name' => $optionGroup->name,
            'required' => $optionGroup->required,
            'min_select' => $optionGroup->min_select,
            'max_select' => $optionGroup->max_select,
            'options' => $optionGroup->options->map(fn ($option) => [
                'id' => $option->id,
                'name' => $option->name,
                'price' => $option->price,
            ])->all(),
        ];
    }

    // is_available をリアルタイムで再計算する
    private function recalculateAvailability(array $menuData): array
    {
        $now = Carbon::now();
        $dayFlag = 1 << $now->dayOfWeek;
        $currentTime = $now->format('H:i:s');

        foreach ($menuData['categories'] as $categoryIndex => $category) {
            foreach ($category['items'] as $itemIndex => $itemData) {
                if (! $itemData['needs_recalc']) {
                    continue;
                }

                $isAvailable = ! $itemData['is_sold_out']
                    && ($itemData['available_days'] & $dayFlag) !== 0
                    && $this->isWithinTimeRange($itemData, $currentTime);

                $menuData['categories'][$categoryIndex]['items'][$itemIndex]['is_available'] = $isAvailable;
            }
        }

        return $menuData;
    }

    // レスポンスに含めない内部フラグを除去する
    private function stripInternalFlags(array $menuData): array
    {
        unset($menuData['has_restricted_items']);
        foreach ($menuData['categories'] as &$category) {
            foreach ($category['items'] as &$item) {
                unset($item['needs_recalc']);
            }
        }

        return $menuData;
    }

    private function isWithinTimeRange(array $item, string $currentTime): bool
    {
        if ($item['available_from'] && $item['available_until']) {
            return $currentTime >= $item['available_from']
                && $currentTime <= $item['available_until'];
        }

        return true;
    }
}
