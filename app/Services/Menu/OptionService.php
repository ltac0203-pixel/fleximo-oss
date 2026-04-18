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
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($optionGroup, $data) {
            $sortOrder = $this->resolveSortOrder(
                $data->sort_order,
                fn () => $optionGroup->options()->max('sort_order')
            );

            $option = Option::create([
                'option_group_id' => $optionGroup->id,
                'tenant_id' => $optionGroup->tenant_id,
                'name' => $data->name,
                'price' => $data->price ?? 0,
                'sort_order' => $sortOrder,
                'is_active' => $data->is_active ?? true,
            ]);

            $this->safeAuditLog(AuditAction::OptionCreated, $option, null, $optionGroup->tenant_id);
            $this->invalidateMenuCache($optionGroup->tenant_id);

            return $option;
        });
    }

    // オプション更新
    public function update(Option $option, UpdateOptionData $data): Option
    {
        return DB::transaction(function () use ($option, $data) {
            $oldAttributes = $option->getAttributes();
            $tenantId = $option->optionGroup->tenant_id;

            $updateData = array_filter($data->toArray(), fn ($v) => $v !== null);
            $option->update($updateData);

            $this->safeAuditLog(AuditAction::OptionUpdated, $option, [
                'old' => $oldAttributes,
                'new' => $option->getAttributes(),
            ], $tenantId);
            $this->invalidateMenuCache($tenantId);

            return $option;
        });
    }

    // オプション削除
    public function delete(Option $option): void
    {
        DB::transaction(function () use ($option) {
            $tenantId = $option->optionGroup->tenant_id;

            $this->safeAuditLog(AuditAction::OptionDeleted, $option, [
                'old' => $option->toArray(),
            ], $tenantId);

            $option->delete();
            $this->invalidateMenuCache($tenantId);
        });
    }
}
