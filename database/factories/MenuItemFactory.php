<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MenuItem;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MenuItem> */
class MenuItemFactory extends Factory
{
    // モデルのデフォルト状態を定義する。
    //
    // @return array<string, mixed>
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->randomElement([
                'コーヒー',
                'カフェラテ',
                'エスプレッソ',
                'アメリカーノ',
                'ハンバーガー',
                'チーズバーガー',
                'フライドポテト',
                'サラダ',
                'チョコケーキ',
                'チーズケーキ',
                'アイスクリーム',
                'パンケーキ',
            ]),
            'description' => fake()->optional(0.7)->sentence(),
            'price' => fake()->randomElement([300, 400, 500, 600, 800, 1000, 1200, 1500]),
            'is_active' => true,
            'is_sold_out' => false,
            'available_from' => null,
            'available_until' => null,
            'available_days' => MenuItem::ALL_DAYS,
            'sort_order' => fake()->numberBetween(0, 100),
            'allergens' => 0,
            'allergen_advisories' => 0,
            'allergen_note' => null,
            'nutrition_info' => null,
        ];
    }

    // 商品を無効状態として設定する。
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    // 商品を売り切れ状態として設定する。
    public function soldOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_sold_out' => true,
        ]);
    }

    // 平日のみ利用可能（Monday to Friday）。
    public function weekdaysOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'available_days' => MenuItem::WEEKDAYS,
        ]);
    }

    // 週末のみ利用可能（Saturday and Sunday）。
    public function weekendsOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'available_days' => MenuItem::WEEKENDS,
        ]);
    }

    // 午前中のみ利用可能（09:00 - 11:00）。
    public function morningOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'available_from' => '09:00:00',
            'available_until' => '11:00:00',
        ]);
    }

    // ランチ時間帯のみ利用可能（11:00 - 14:00）。
    public function lunchOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'available_from' => '11:00:00',
            'available_until' => '14:00:00',
        ]);
    }

    // 特定の並び順を設定する。
    public function sortOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'sort_order' => $order,
        ]);
    }

    // アレルゲン情報付き
    public function withAllergens(int $allergens = 0, int $advisories = 0, ?string $note = null): static
    {
        return $this->state(fn (array $attributes) => [
            'allergens' => $allergens ?: (1 | 8 | 32), // えび、小麦、卵
            'allergen_advisories' => $advisories ?: (2048 | 16384), // 大豆、豚肉
            'allergen_note' => $note ?? '同一工場で卵・乳を含む製品を製造しています。',
        ]);
    }

    // 栄養成分情報付き
    public function withNutrition(?array $info = null): static
    {
        return $this->state(fn (array $attributes) => [
            'nutrition_info' => json_encode($info ?? [
                'energy' => 350.0,
                'protein' => 12.5,
                'fat' => 15.0,
                'carbohydrate' => 42.0,
                'salt' => 1.8,
            ]),
        ]);
    }

    // 特定の価格を設定する。
    public function price(int $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $price,
        ]);
    }
}
