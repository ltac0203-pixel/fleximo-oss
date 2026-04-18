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

        $categories = [
            ['name' => 'ドリンク', 'sort_order' => 10],
            ['name' => 'フード', 'sort_order' => 20],
            ['name' => 'デザート', 'sort_order' => 30],
            ['name' => 'サイドメニュー', 'sort_order' => 40],
            ['name' => 'アルコール', 'sort_order' => 50],
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
                        'sort_order' => $category['sort_order'],
                        'is_active' => true,
                    ]
                );
            }

            app(TenantContext::class)->clear();
        }
    }
}
