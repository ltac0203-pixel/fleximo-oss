<?php

declare(strict_types=1);

namespace App\Services\Menu;

use App\DTOs\Menu\CreateOptionGroupData;
use App\DTOs\Menu\UpdateOptionGroupData;
use App\Enums\AuditAction;
use App\Models\OptionGroup;
use App\Services\Menu\Concerns\MenuServiceHelpers;
use Illuminate\Database\Eloquent\Collection;

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
        return $this->withMenuMutation(
            AuditAction::OptionGroupCreated,
            $tenantId,
            function () use ($tenantId, $data): OptionGroup {
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

                return $optionGroup->load('options');
            },
        );
    }

    // オプショングループ更新
    public function update(OptionGroup $optionGroup, UpdateOptionGroupData $data): OptionGroup
    {
        $oldAttributes = $optionGroup->getAttributes();

        return $this->withMenuMutation(
            AuditAction::OptionGroupUpdated,
            $optionGroup->tenant_id,
            function () use ($optionGroup, $data): OptionGroup {
                $optionGroup->update($data->toArray());

                return $optionGroup->fresh('options');
            },
            fn (OptionGroup $updated) => [
                'old' => $oldAttributes,
                'new' => $updated->getAttributes(),
            ],
        );
    }

    // オプショングループ削除
    public function delete(OptionGroup $optionGroup): void
    {
        $tenantId = $optionGroup->tenant_id;
        $oldSnapshot = $optionGroup->toArray();

        $this->withMenuMutation(
            AuditAction::OptionGroupDeleted,
            $tenantId,
            function () use ($optionGroup): OptionGroup {
                $optionGroup->options()->delete();
                $optionGroup->menuItems()->detach();
                $optionGroup->delete();

                return $optionGroup;
            },
            fn () => ['old' => $oldSnapshot],
        );
    }
}
