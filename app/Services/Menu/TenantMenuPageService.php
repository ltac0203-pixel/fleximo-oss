<?php

declare(strict_types=1);

namespace App\Services\Menu;

use App\Http\Resources\OptionGroupResource;
use App\Http\Resources\OptionGroupSlimResource;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\OptionGroup;

class TenantMenuPageService
{
    // カテゴリ一覧ページ用のpropsを取得
    public function getCategoriesIndexProps(): array
    {
        $categories = MenuCategory::query()
            ->ordered()
            ->get()
            ->map(static fn (MenuCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'sort_order' => $category->sort_order,
                'is_active' => $category->is_active,
            ]);

        return [
            'categories' => $categories,
        ];
    }

    // 商品一覧ページ用のpropsを取得
    public function getItemsIndexProps(): array
    {
        $items = MenuItem::with('categories')
            ->ordered()
            ->get();

        // Eager Load済みのカテゴリからフィルタ用リストを導出（重複排除・アクティブのみ・ソート済み）
        $categories = $items->flatMap->categories
            ->where('is_active', true)
            ->unique('id')
            ->sortBy('sort_order')
            ->values()
            ->map(static fn (MenuCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ]);

        $items = $items->map(static fn (MenuItem $item) => [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'price' => $item->price,
            'is_active' => $item->is_active,
            'is_sold_out' => $item->is_sold_out,
            'available_from' => $item->available_from,
            'available_until' => $item->available_until,
            'available_days' => $item->available_days,
            'sort_order' => $item->sort_order,
            'categories' => $item->categories->map(static fn (MenuCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ]),
        ]);

        return [
            'items' => $items,
            'categories' => $categories,
        ];
    }

    // 商品作成ページ用のpropsを取得
    public function getItemsCreateProps(): array
    {
        $categories = MenuCategory::query()
            ->active()
            ->ordered()
            ->get(['id', 'name', 'sort_order', 'is_active']);

        $optionGroups = OptionGroup::with(['options' => static fn ($query) => $query->active()->ordered()])
            ->active()
            ->ordered()
            ->get();

        return [
            'categories' => $categories,
            'optionGroups' => OptionGroupSlimResource::collection($optionGroups)->resolve(),
        ];
    }

    // 商品編集ページ用のpropsを取得
    public function getItemsEditProps(MenuItem $item): array
    {
        // ドロップダウン用: 全アクティブカテゴリ（1クエリ）
        $categories = MenuCategory::query()
            ->active()
            ->ordered()
            ->get(['id', 'name', 'sort_order', 'is_active']);

        // ドロップダウン用: 全アクティブオプショングループ+options（2クエリ）
        $optionGroups = OptionGroup::with(['options' => static fn ($query) => $query->active()->ordered()])
            ->active()
            ->ordered()
            ->get();

        // アイテム紐付きIDのみ取得（pivotテーブルの軽量クエリ × 2）
        $itemCategoryIds = $item->categories()->pluck('menu_categories.id');
        $itemOptionGroupIds = $item->optionGroups()->pluck('option_groups.id');

        $itemData = [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'price' => $item->price,
            'is_active' => $item->is_active,
            'is_sold_out' => $item->is_sold_out,
            'available_from' => $item->available_from,
            'available_until' => $item->available_until,
            'available_days' => $item->available_days,
            'sort_order' => $item->sort_order,
            'categories' => $categories->whereIn('id', $itemCategoryIds)
                ->map(static fn (MenuCategory $c) => ['id' => $c->id, 'name' => $c->name])
                ->values(),
            'option_groups' => $optionGroups->whereIn('id', $itemOptionGroupIds)
                ->map(static fn (OptionGroup $og) => ['id' => $og->id, 'name' => $og->name])
                ->values(),
        ];

        return [
            'item' => $itemData,
            'categories' => $categories,
            'optionGroups' => OptionGroupSlimResource::collection($optionGroups)->resolve(),
        ];
    }

    // オプショングループ一覧ページ用のpropsを取得
    public function getOptionGroupsIndexProps(): array
    {
        $optionGroups = OptionGroup::with(['options' => static fn ($query) => $query->ordered()])
            ->ordered()
            ->get();

        return [
            'optionGroups' => OptionGroupResource::collection($optionGroups)->resolve(),
        ];
    }

    // オプショングループ編集ページ用のpropsを取得
    public function getOptionGroupsEditProps(OptionGroup $optionGroup): array
    {
        $optionGroup->load(['options' => static fn ($query) => $query->ordered()]);

        return [
            'optionGroup' => new OptionGroupResource($optionGroup),
        ];
    }
}
