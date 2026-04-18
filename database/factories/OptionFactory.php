<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Option;
use App\Models\OptionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Option> */
class OptionFactory extends Factory
{
    // モデルのデフォルト状態を定義する。
    //
    // @return array<string, mixed>
    public function definition(): array
    {
        $optionGroup = OptionGroup::factory();

        return [
            'option_group_id' => $optionGroup,
            'tenant_id' => function (array $attributes) use ($optionGroup) {
                // option_group_id が指定されている場合は、そのグループの tenant_id を使用
                if (isset($attributes['option_group_id'])) {
                    return OptionGroup::find($attributes['option_group_id'])->tenant_id;
                }

                return $optionGroup;
            },
            'name' => fake()->randomElement(['S', 'M', 'L', 'チーズ', 'ベーコン', '牛乳', '豆乳', 'ホット', 'アイス']),
            'price' => fake()->randomElement([0, 50, 100, 150, 200]),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }

    // オプションを無効状態として設定する。
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    // 特定の価格を設定する。
    public function price(int $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $price,
        ]);
    }

    // 特定の並び順を設定する。
    public function sortOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'sort_order' => $order,
        ]);
    }

    // 価格を0に設定する（無料オプション）。
    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => 0,
        ]);
    }
}
