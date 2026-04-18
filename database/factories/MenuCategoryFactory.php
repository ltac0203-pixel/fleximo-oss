<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MenuCategory;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<MenuCategory> */
class MenuCategoryFactory extends Factory
{
    // モデルのデフォルト状態を定義する。
    //
    // @return array<string, mixed>
    public function definition(): array
    {
        $name = fake()->randomElement(['ドリンク', 'フード', 'デザート', 'サイド', 'アルコール']);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'slug' => Str::slug($name) ?: 'category-'.fake()->unique()->numberBetween(1, 10000),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }

    // カテゴリを無効状態として設定する。
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    // 特定の並び順を設定する。
    public function sortOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'sort_order' => $order,
        ]);
    }
}
