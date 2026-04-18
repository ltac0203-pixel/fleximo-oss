<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CartItem;
use App\Models\CartItemOption;
use App\Models\Option;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CartItemOption> */
class CartItemOptionFactory extends Factory
{
    // モデルのデフォルト状態を定義する。
    //
    // @return array<string, mixed>
    public function definition(): array
    {
        $tenant = Tenant::factory()->create();

        return [
            'cart_item_id' => CartItem::factory(),
            'tenant_id' => $tenant->id,
            'option_id' => Option::factory(),
        ];
    }

    // 特定のカート商品を設定する。
    public function forCartItem(CartItem $cartItem): static
    {
        return $this->state(fn (array $attributes) => [
            'cart_item_id' => $cartItem->id,
            'tenant_id' => $cartItem->tenant_id,
        ]);
    }

    // 特定のオプションを設定する。
    public function forOption(Option $option): static
    {
        return $this->state(fn (array $attributes) => [
            'option_id' => $option->id,
        ]);
    }
}
