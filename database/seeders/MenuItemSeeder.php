<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\MenuItemCategory;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    // データベースシーダーを実行する。
    public function run(): void
    {
        $tenants = Tenant::all();

        $menuItems = [
            // ドリンク
            ['name' => 'コーヒー', 'price' => 350, 'category' => 'ドリンク', 'sort_order' => 10],
            ['name' => 'カフェラテ', 'price' => 450, 'category' => 'ドリンク', 'sort_order' => 20],
            ['name' => 'エスプレッソ', 'price' => 300, 'category' => 'ドリンク', 'sort_order' => 30],
            ['name' => 'オレンジジュース', 'price' => 350, 'category' => 'ドリンク', 'sort_order' => 40],
            // フード
            ['name' => 'ハンバーガー', 'price' => 800, 'category' => 'フード', 'sort_order' => 50],
            ['name' => 'チーズバーガー', 'price' => 900, 'category' => 'フード', 'sort_order' => 60],
            ['name' => 'BLTサンドイッチ', 'price' => 700, 'category' => 'フード', 'sort_order' => 70],
            // サイド
            ['name' => 'フライドポテト', 'price' => 350, 'category' => 'サイドメニュー', 'sort_order' => 80],
            ['name' => 'サラダ', 'price' => 400, 'category' => 'サイドメニュー', 'sort_order' => 90],
            // デザート
            ['name' => 'チョコレートケーキ', 'price' => 500, 'category' => 'デザート', 'sort_order' => 100],
            ['name' => 'チーズケーキ', 'price' => 500, 'category' => 'デザート', 'sort_order' => 110],
            // アルコール
            ['name' => '生ビール', 'price' => 550, 'category' => 'アルコール', 'sort_order' => 120],
            ['name' => 'ハイボール', 'price' => 500, 'category' => 'アルコール', 'sort_order' => 130],
            // モーニング限定
            [
                'name' => 'モーニングセット',
                'price' => 600,
                'category' => 'フード',
                'sort_order' => 140,
                'available_from' => '09:00:00',
                'available_until' => '11:00:00',
                'description' => '9:00〜11:00限定のお得なセット',
            ],
            // 平日限定
            [
                'name' => 'ランチセット',
                'price' => 900,
                'category' => 'フード',
                'sort_order' => 150,
                'available_days' => MenuItem::WEEKDAYS,
                'description' => '平日限定のお得なランチセット',
            ],
        ];

        foreach ($tenants as $tenant) {
            app(TenantContext::class)->setTenant($tenant->id);

            // このテナントのカテゴリを取得
            $categories = MenuCategory::where('tenant_id', $tenant->id)->get()->keyBy('name');

            foreach ($menuItems as $itemData) {
                $categoryName = $itemData['category'];
                unset($itemData['category']);

                $item = MenuItem::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name' => $itemData['name'],
                    ],
                    [
                        'description' => $itemData['description'] ?? null,
                        'price' => $itemData['price'],
                        'sort_order' => $itemData['sort_order'],
                        'available_from' => $itemData['available_from'] ?? null,
                        'available_until' => $itemData['available_until'] ?? null,
                        'available_days' => $itemData['available_days'] ?? MenuItem::ALL_DAYS,
                        'is_active' => true,
                        'is_sold_out' => false,
                    ]
                );

                // カテゴリを紐付け
                if (isset($categories[$categoryName])) {
                    MenuItemCategory::updateOrCreate([
                        'menu_item_id' => $item->id,
                        'category_id' => $categories[$categoryName]->id,
                    ]);
                }
            }

            app(TenantContext::class)->clear();
        }
    }
}
