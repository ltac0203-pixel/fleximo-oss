<?php

declare(strict_types=1);

namespace App\Services\Menu;

use App\DTOs\Menu\CreateOptionGroupData;
use App\DTOs\Menu\UpdateOptionGroupData;
use App\Enums\AuditAction;
use App\Models\OptionGroup;
use App\Services\Menu\Concerns\MenuServiceHelpers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OptionGroupService
{
    use MenuServiceHelpers;

    // オプショングループ一覧取得
    public function getList(int $tenantId): Collection
    {
        return OptionGroup::where('tenant_id', $tenantId)
            ->with('options')
            ->ordered()
            ->get();
    }

    // オプショングループ作成
    public function create(int $tenantId, CreateOptionGroupData $data): OptionGroup
    {
        return DB::transaction(function () use ($tenantId, $data) {
            $sortOrder = $this->resolveSortOrder(
                $data->sort_order,
                fn () => OptionGroup::where('tenant_id', $tenantId)->max('sort_order')
            );

            $optionGroup = OptionGroup::create([
                'tenant_id' => $tenantId,
                'name' => $data->name,
                'required' => $data->required,
                'min_select' => $data->min_select,
                'max_select' => $data->max_select,
                'sort_order' => $sortOrder,
                'is_active' => $data->is_active,
            ]);

            $this->safeAuditLog(AuditAction::OptionGroupCreated, $optionGroup);
            $this->invalidateMenuCache($tenantId);

            return $optionGroup->load('options');
        });
    }

    // オプショングループ更新
    public function update(OptionGroup $optionGroup, UpdateOptionGroupData $data): OptionGroup
    {
        return DB::transaction(function () use ($optionGroup, $data) {
            $oldAttributes = $optionGroup->getAttributes();

            $optionGroup->update($data->toArray());

            $this->safeAuditLog(AuditAction::OptionGroupUpdated, $optionGroup, [
                'old' => $oldAttributes,
                'new' => $optionGroup->getAttributes(),
            ]);
            $this->invalidateMenuCache($optionGroup->tenant_id);

            return $optionGroup->fresh('options');
        });
    }

    // オプショングループ削除
    public function delete(OptionGroup $optionGroup): void
    {
        DB::transaction(function () use ($optionGroup) {
            $tenantId = $optionGroup->tenant_id;

            $this->safeAuditLog(AuditAction::OptionGroupDeleted, $optionGroup, [
                'old' => $optionGroup->toArray(),
            ]);

            $optionGroup->options()->delete();
            $optionGroup->menuItems()->detach();
            $optionGroup->delete();

            $this->invalidateMenuCache($tenantId);
        });
    }
}
