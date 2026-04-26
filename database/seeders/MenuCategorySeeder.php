<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MenuCategory;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;

class MenuCategorySeeder extends Seeder
{
    // データベースシーダーを実行する。
    public function run(): void
    {
        $tenants = Tenant::all();

        // slugは MenuCategory モデルの booted() で Str::slug($name) ?: 'category' が走るが、
        // Str::slug は日本語名で空文字になりフォールバックの 'category' が全件で衝突する。
        // そのため種データ側で英字のslugを明示し、(tenant_id, slug) UNIQUE制約を満たす。
        $categories = [
            ['name' => 'ドリンク', 'slug' => 'drinks', 'sort_order' => 10],
            ['name' => 'フード', 'slug' => 'food', 'sort_order' => 20],
            ['name' => 'デザート', 'slug' => 'desserts', 'sort_order' => 30],
            ['name' => 'サイドメニュー', 'slug' => 'sides', 'sort_order' => 40],
            ['name' => 'アルコール', 'slug' => 'alcohol', 'sort_order' => 50],
        ];

        foreach ($tenants as $tenant) {
            app(TenantContext::class)->setTenant($tenant->id);

            foreach ($categories as $category) {
                MenuCategory::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name' => $category['name'],
                    ],
                    [
                        'slug' => $category['slug'],
                        'sort_order' => $category['sort_order'],
                        'is_active' => true,
                    ]
                );
            }

            app(TenantContext::class)->clear();
        }
    }
}
