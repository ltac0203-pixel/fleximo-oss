<?php

declare(strict_types=1);

namespace App\Services\Menu;

use App\DTOs\Menu\CreateMenuItemData;
use App\DTOs\Menu\UpdateMenuItemData;
use App\Enums\AuditAction;
use App\Events\TenantMenuUpdated;
use App\Models\MenuItem;
use App\Services\Menu\Concerns\MenuServiceHelpers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MenuItemService
{
    use MenuServiceHelpers;

    // 商品一覧取得
    public function getList(int $tenantId): Collection
    {
        return MenuItem::where('tenant_id', $tenantId)
            ->with(['categories', 'optionGroups.options'])
            ->ordered()
            ->get();
    }

    // 商品作成
    public function create(int $tenantId, CreateMenuItemData $data): MenuItem
    {
        $item = $this->withMenuMutation(
            AuditAction::MenuItemCreated,
            $tenantId,
            function () use ($tenantId, $data): MenuItem {
                $sortOrder = $this->resolveSortOrder(
                    $data->sort_order,
                    fn () => MenuItem::where('tenant_id', $tenantId)->max('sort_order')
                );

                // price はMass Assignment攻撃防止のため直接属性代入で設定する
                $item = new MenuItem([
                    'tenant_id' => $tenantId,
                    'name' => $data->name,
                    'description' => $data->description,
                    'is_active' => $data->is_active,
                    'is_sold_out' => $data->is_sold_out,
                    'available_from' => $data->available_from,
                    'available_until' => $data->available_until,
                    'available_days' => $data->available_days,
                    'sort_order' => $sortOrder,
                    'allergens' => $data->allergens,
                    'allergen_advisories' => $data->allergen_advisories,
                    'allergen_note' => $data->allergen_note,
                    'nutrition_info' => $data->nutrition_info,
                ]);
                $item->price = $data->price;
                $item->save();

                if (! empty($data->category_ids)) {
                    $item->categories()->attach($data->category_ids);
                }

                if (! empty($data->option_group_ids)) {
                    $item->optionGroups()->attach($data->option_group_ids);
                }

                return $item->load(['categories', 'optionGroups.options']);
            },
            fn () => [
                'metadata' => [
                    'category_ids' => $data->category_ids,
                    'option_group_ids' => $data->option_group_ids,
                ],
            ],
        );

        event(new TenantMenuUpdated($tenantId, 'created'));

        return $item;
    }

    // 商品更新
    public function update(MenuItem $item, UpdateMenuItemData $data): MenuItem
    {
        $oldAttributes = $item->getAttributes();
        $oldCategoryIds = $item->categories->pluck('id')->toArray();
        $oldOptionGroupIds = $item->optionGroups->pluck('id')->toArray();

        $result = $this->withMenuMutation(
            AuditAction::MenuItemUpdated,
            $item->tenant_id,
            function () use ($item, $data): MenuItem {
                $updateData = $data->toArray();
                unset($updateData['category_ids']);
                unset($updateData['option_group_ids']);
                unset($updateData['presentFields']);

                // nullable フィールド(description, available_from, available_until)は
                // null への変更を許可する必要があるため、presentFields に含まれるフィールドのみフィルタする
                $nullableFields = ['description', 'available_from', 'available_until', 'allergen_note', 'nutrition_info'];
                $filteredData = array_filter($updateData, function ($v, $k) use ($nullableFields) {
                    if (in_array($k, $nullableFields)) {
                        return true; // nullable フィールドは null 値も保持
                    }

                    return $v !== null;
                }, ARRAY_FILTER_USE_BOTH);

                // price はMass Assignment攻撃防止のため直接属性代入で設定する
                if (array_key_exists('price', $filteredData)) {
                    $item->price = $filteredData['price'];
                    unset($filteredData['price']);
                }

                $item->update($filteredData);

                if (in_array('category_ids', $data->presentFields)) {
                    $item->categories()->sync($data->category_ids ?? []);
                }

                if (in_array('option_group_ids', $data->presentFields)) {
                    $item->optionGroups()->sync($data->option_group_ids ?? []);
                }

                return $item->load(['categories', 'optionGroups.options']);
            },
            fn (MenuItem $updated) => [
                'old' => array_merge($oldAttributes, [
                    'category_ids' => $oldCategoryIds,
                    'option_group_ids' => $oldOptionGroupIds,
                ]),
                'new' => array_merge($updated->getAttributes(), [
                    'category_ids' => $data->category_ids ?? $oldCategoryIds,
                    'option_group_ids' => $data->option_group_ids ?? $oldOptionGroupIds,
                ]),
            ],
        );

        event(new TenantMenuUpdated($item->tenant_id, 'updated'));

        return $result;
    }

    // 商品削除
    public function delete(MenuItem $item): void
    {
        $tenantId = $item->tenant_id;
        $oldSnapshot = $item->toArray();

        $this->withMenuMutation(
            AuditAction::MenuItemDeleted,
            $tenantId,
            function () use ($item): MenuItem {
                $item->categories()->detach();
                $item->optionGroups()->detach();
                $item->delete();

                return $item;
            },
            fn () => ['old' => $oldSnapshot],
        );

        event(new TenantMenuUpdated($tenantId, 'deleted'));
    }

    // 売り切れ切替
    public function toggleSoldOut(MenuItem $item): MenuItem
    {
        $oldValue = $item->is_sold_out;

        $result = $this->withMenuMutation(
            AuditAction::MenuItemSoldOutToggled,
            $item->tenant_id,
            function () use ($item, $oldValue): MenuItem {
                $item->update(['is_sold_out' => ! $oldValue]);

                return $item->load(['categories', 'optionGroups.options']);
            },
            fn (MenuItem $updated) => [
                'old' => ['is_sold_out' => $oldValue],
                'new' => ['is_sold_out' => $updated->is_sold_out],
            ],
        );

        event(new TenantMenuUpdated($item->tenant_id, 'sold_out_toggled'));

        return $result;
    }

    // オプショングループを商品に紐付け
    // syncWithoutDetaching の結果が空の場合は audit/cache 破棄を skip する早期 return が
    // あるため、withMenuMutation テンプレには載せず DB::transaction 直書きで維持する
    public function attachOptionGroup(MenuItem $item, int $optionGroupId): void
    {
        DB::transaction(function () use ($item, $optionGroupId) {
            // 競合時の二重挿入を避けるため syncWithoutDetaching を使い、
            // 実際に追加された場合のみ監査ログとキャッシュ破棄を行う
            $result = $item->optionGroups()->syncWithoutDetaching([$optionGroupId]);

            if (empty($result['attached'])) {
                return;
            }

            $this->safeAuditLog(AuditAction::MenuItemUpdated, $item, [
                'metadata' => [
                    'action' => 'option_group_attached',
                    'option_group_id' => $optionGroupId,
                ],
            ]);
            $this->invalidateMenuCache($item->tenant_id);
        });
    }

    // オプショングループを商品から解除
    public function detachOptionGroup(MenuItem $item, int $optionGroupId): void
    {
        $this->withMenuMutation(
            AuditAction::MenuItemUpdated,
            $item->tenant_id,
            function () use ($item, $optionGroupId): MenuItem {
                $item->optionGroups()->detach($optionGroupId);

                return $item;
            },
            fn () => [
                'metadata' => [
                    'action' => 'option_group_detached',
                    'option_group_id' => $optionGroupId,
                ],
            ],
        );
    }
}
