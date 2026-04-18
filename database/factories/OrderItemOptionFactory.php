<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Option;
use App\Models\OrderItem;
use App\Models\OrderItemOption;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderItemOption> */
class OrderItemOptionFactory extends Factory
{
    // モデルのデフォルト状態を定義する。
    //
    // @return array<string, mixed>
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'tenant_id' => Tenant::factory(),
            'option_id' => null,
            'name' => fake()->randomElement([
                'トッピング チーズ',
                'トッピング ベーコン',
                'サイズ L',
                'ホイップクリーム追加',
                'ショット追加',
                'シロップ追加',
            ]),
            'price' => fake()->randomElement([0, 50, 100, 150, 200]),
        ];
    }

    // 特定の注文商品を設定する。
    public function forOrderItem(OrderItem $orderItem): static
    {
        return $this->state(fn (array $attributes) => [
            'order_item_id' => $orderItem->id,
            'tenant_id' => $orderItem->tenant_id,
        ]);
    }

    // 特定のテナントを設定する。
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    // オプションから作成する（スナップショット）。
    public function fromOption(Option $option): static
    {
        return $this->state(fn (array $attributes) => [
            'option_id' => $option->id,
            'tenant_id' => $option->option_group_id ?
                $option->optionGroup->tenant_id : ($attributes['tenant_id'] ?? Tenant::factory()),
            'name' => $option->name,
            'price' => $option->price,
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
