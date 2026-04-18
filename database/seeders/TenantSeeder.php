<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    // データベースシーディングを実行する
    public function run(): void
    {
        $tenants = [
            [
                'name' => 'カフェ・ブルースカイ',
                'slug' => 'cafe-bluesky',
                'address' => '東京都渋谷区渋谷1-1-1',
                'email' => 'info@cafe-bluesky.example.com',
                'phone' => '03-1234-5678',
                'status' => 'active',
                'is_active' => true,
            ],
            [
                'name' => '居酒屋 月あかり',
                'slug' => 'izakaya-tsukiakari',
                'address' => '東京都新宿区歌舞伎町2-2-2',
                'email' => 'contact@tsukiakari.example.com',
                'phone' => '03-2345-6789',
                'status' => 'active',
                'is_active' => true,
            ],
            [
                'name' => 'ベーカリー サンライズ',
                'slug' => 'bakery-sunrise',
                'address' => '東京都世田谷区三軒茶屋3-3-3',
                'email' => 'hello@bakery-sunrise.example.com',
                'phone' => '03-3456-7890',
                'status' => 'active',
                'is_active' => true,
            ],
            [
                'name' => 'ファミリーレストラン ハーモニー',
                'slug' => 'family-restaurant-harmony',
                'address' => '東京都品川区大崎4-4-4',
                'email' => 'info@harmony-family.example.com',
                'phone' => '03-4567-8901',
                'status' => 'active',
                'is_active' => true,
            ],
            [
                'name' => 'レストラン クローズド',
                'slug' => 'restaurant-closed',
                'address' => '東京都港区六本木5-5-5',
                'email' => 'contact@closed.example.com',
                'phone' => '03-5678-9012',
                'status' => 'inactive',
                'is_active' => false,
            ],
        ];

        foreach ($tenants as $tenant) {
            Tenant::updateOrCreate(
                ['slug' => $tenant['slug']],
                $tenant
            );
        }
    }
}
