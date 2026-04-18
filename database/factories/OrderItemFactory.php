<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderItem> */
class OrderItemFactory extends Factory
{
    // モデルのデフォルト状態を定義する。
    //
    // @return array<string, mixed>
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'tenant_id' => Tenant::factory(),
            'menu_item_id' => null,
            'name' => fake()->randomElement([
                'コーヒー',
                'カフェラテ',
                'エスプレッソ',
                'アメリカーノ',
                'ハンバーガー',
                'チーズバーガー',
                'フライドポテト',
                'サラダ',
            ]),
            'price' => fake()->randomElement([300, 400, 500, 600, 800, 1000]),
            'quantity' => fake()->numberBetween(1, 3),
        ];
    }

    // 特定の注文を設定する。
    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
        ]);
    }

    // 特定のテナントを設定する。
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    // メニュー商品から作成する（スナップショット）。
    public function fromMenuItem(MenuItem $menuItem): static
    {
        return $this->state(fn (array $attributes) => [
            'menu_item_id' => $menuItem->id,
            'tenant_id' => $menuItem->tenant_id,
            'name' => $menuItem->name,
            'price' => $menuItem->price,
        ]);
    }

    // 特定の数量を設定する。
    public function quantity(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
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
