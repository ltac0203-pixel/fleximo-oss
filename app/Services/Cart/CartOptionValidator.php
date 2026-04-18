<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Exceptions\InvalidOptionSelectionException;
use App\Models\MenuItem;
use InvalidArgumentException;

class CartOptionValidator
{
    public function validateOptionsForMenuItem(MenuItem $menuItem, array $optionIds): void
    {
        // 各オプショングループの必須/最小/最大選択数ルールに従っているか検証する
        foreach ($menuItem->optionGroups as $optionGroup) {
            // 全選択肢の中から、このグループに該当するものだけを抽出して検証に渡す
            $groupOptionIds = $optionGroup->options->pluck('id')->toArray();
            $selectedInGroup = array_values(array_intersect($optionIds, $groupOptionIds));

            try {
                $optionGroup->validateOptionSelection($selectedInGroup);
            } catch (InvalidArgumentException $e) {
                throw new InvalidOptionSelectionException(
                    $optionGroup->name,
                    $e->getMessage()
                );
            }
        }

        // 他商品のオプションIDが混入した不正リクエストを弾くため、所属チェックを行う
        $validOptionIds = $menuItem->optionGroups
            ->flatMap(fn ($group) => $group->options->pluck('id'))
            ->toArray();

        $invalidIds = array_diff($optionIds, $validOptionIds);
        if (! empty($invalidIds)) {
            throw new InvalidOptionSelectionException(
                '',
                'この商品に紐付いていないオプションが選択されています。'
            );
        }
    }
}
