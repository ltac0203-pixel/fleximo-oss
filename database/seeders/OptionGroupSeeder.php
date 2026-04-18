<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\MenuItemOptionGroup;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;

class OptionGroupSeeder extends Seeder
{
    // データベースシーダーを実行する。
    public function run(): void
    {
        $tenants = Tenant::all();

        // オプショングループとオプションの定義
        $optionGroups = [
            [
                'name' => 'サイズ',
                'required' => true,
                'min_select' => 1,
                'max_select' => 1,
                'sort_order' => 10,
                'options' => [
                    ['name' => 'S', 'price' => 0, 'sort_order' => 10],
                    ['name' => 'M', 'price' => 50, 'sort_order' => 20],
                    ['name' => 'L', 'price' => 100, 'sort_order' => 30],
                ],
            ],
            [
                'name' => 'トッピング',
                'required' => false,
                'min_select' => 0,
                'max_select' => 3,
                'sort_order' => 20,
                'options' => [
                    ['name' => 'チーズ', 'price' => 100, 'sort_order' => 10],
                    ['name' => 'ベーコン', 'price' => 150, 'sort_order' => 20],
                    ['name' => 'アボカド', 'price' => 200, 'sort_order' => 30],
                    ['name' => 'エッグ', 'price' => 100, 'sort_order' => 40],
                ],
            ],
            [
                'name' => 'ミルクの種類',
                'required' => true,
                'min_select' => 1,
                'max_select' => 1,
                'sort_order' => 30,
                'options' => [
                    ['name' => '牛乳', 'price' => 0, 'sort_order' => 10],
                    ['name' => '豆乳', 'price' => 50, 'sort_order' => 20],
                    ['name' => 'オーツミルク', 'price' => 80, 'sort_order' => 30],
                    ['name' => 'アーモンドミルク', 'price' => 80, 'sort_order' => 40],
                ],
            ],
        ];

        // 各商品に紐付けるオプショングループ
        $menuItemOptionGroups = [
            // ドリンク系にはサイズとミルク
            'コーヒー' => ['サイズ'],
            'カフェラテ' => ['サイズ', 'ミルクの種類'],
            'エスプレッソ' => ['サイズ'],
            // バーガー系にはトッピング
            'ハンバーガー' => ['トッピング'],
            'チーズバーガー' => ['トッピング'],
        ];

        foreach ($tenants as $tenant) {
            app(TenantContext::class)->setTenant($tenant->id);

            $createdGroups = [];

            // オプショングループとオプションを作成
            foreach ($optionGroups as $groupData) {
                $options = $groupData['options'];
                unset($groupData['options']);

                $group = OptionGroup::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name' => $groupData['name'],
                    ],
                    [
                        ...$groupData,
                        'is_active' => true,
                    ]
                );

                $createdGroups[$group->name] = $group;

                // オプションを作成
                foreach ($options as $optionData) {
                    Option::updateOrCreate(
                        [
                            'option_group_id' => $group->id,
                            'name' => $optionData['name'],
                        ],
                        [
                            ...$optionData,
                            'is_active' => true,
                        ]
                    );
                }
            }

            // 商品にオプショングループを紐付け
            foreach ($menuItemOptionGroups as $menuItemName => $groupNames) {
                $menuItem = MenuItem::where('tenant_id', $tenant->id)
                    ->where('name', $menuItemName)
                    ->first();

                if ($menuItem) {
                    $attachData = [];
                    foreach ($groupNames as $sortOrder => $groupName) {
                        if (isset($createdGroups[$groupName])) {
                            $attachData[$createdGroups[$groupName]->id] = ($sortOrder + 1) * 10;
                        }
                    }
                    foreach ($attachData as $groupId => $sortOrder) {
                        MenuItemOptionGroup::updateOrCreate(
                            [
                                'menu_item_id' => $menuItem->id,
                                'option_group_id' => $groupId,
                            ],
                            ['sort_order' => $sortOrder]
                        );
                    }
                }
            }

            app(TenantContext::class)->clear();
        }
    }
}
