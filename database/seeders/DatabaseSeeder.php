<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    // アプリケーションのデータベースをシードする。
    public function run(): void
    {
        if (app()->environment(['local', 'testing'])) {
            // ユーザーファクトリ作成例

            User::firstOrCreate(
                ['email' => 'test@example.com'],
                ['name' => 'Test User', 'password' => 'password']
            );

            $this->call([
                TenantSeeder::class,
                MenuCategorySeeder::class,
                MenuItemSeeder::class,
                OptionGroupSeeder::class,
            ]);
        }
    }
}
