<?php

declare(strict_types=1);

namespace App\Services\Menu;

use App\DTOs\Menu\CreateOptionData;
use App\DTOs\Menu\UpdateOptionData;
use App\Enums\AuditAction;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Services\Menu\Concerns\MenuServiceHelpers;
use Illuminate\Database\Eloquent\Collection;

class OptionService
{
    use MenuServiceHelpers;

    // オプション一覧取得
    public function getList(OptionGroup $optionGroup): Collection
    {
        return $optionGroup->options()->ordered()->get();
    }

    // オプション作成
    public function create(OptionGroup $optionGroup, CreateOptionData $data): Option
    {
        return $this->withMenuMutation(
            AuditAction::OptionCreated,
            $optionGroup->tenant_id,
            function () use ($optionGroup, $data): Option {
                $sortOrder = $this->resolveSortOrder(
                    $data->sort_order,
                    fn () => $optionGroup->options()->max('sort_order')
                );

                return Option::create([
                    'option_group_id' => $optionGroup->id,
                    'tenant_id' => $optionGroup->tenant_id,
                    'name' => $data->name,
                    'price' => $data->price ?? 0,
                    'sort_order' => $sortOrder,
                    'is_active' => $data->is_active ?? true,
                ]);
            },
        );
    }

    // オプション更新
    public function update(Option $option, UpdateOptionData $data): Option
    {
        $oldAttributes = $option->getAttributes();
        $tenantId = $option->optionGroup->tenant_id;

        return $this->withMenuMutation(
            AuditAction::OptionUpdated,
            $tenantId,
            function () use ($option, $data): Option {
                $updateData = array_filter($data->toArray(), fn ($v) => $v !== null);
                $option->update($updateData);

                return $option;
            },
            fn (Option $updated) => [
                'old' => $oldAttributes,
                'new' => $updated->getAttributes(),
            ],
        );
    }

    // オプション削除
    public function delete(Option $option): void
    {
        $tenantId = $option->optionGroup->tenant_id;
        $oldSnapshot = $option->toArray();

        $this->withMenuMutation(
            AuditAction::OptionDeleted,
            $tenantId,
            function () use ($option): Option {
                $option->delete();

                return $option;
            },
            fn () => ['old' => $oldSnapshot],
        );
    }
}
