<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OptionGroup;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OptionGroup> */
class OptionGroupFactory extends Factory
{
    // モデルのデフォルト状態を定義する。
    //
    // @return array<string, mixed>
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->randomElement(['サイズ', 'トッピング', 'ミルクの種類', '温度', '甘さ', 'ソース']),
            'required' => fake()->boolean(30),
            'min_select' => 0,
            'max_select' => 1,
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }

    // オプショングループを無効状態として設定する。
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    // オプショングループを必須として設定する。
    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'required' => true,
            'min_select' => 1,
        ]);
    }

    // オプショングループを任意として設定する。
    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'required' => false,
            'min_select' => 0,
        ]);
    }

    // 複数選択の設定を行う。
    public function multipleSelect(int $min = 0, int $max = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'min_select' => $min,
            'max_select' => $max,
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
