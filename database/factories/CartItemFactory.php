<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\MenuItem;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CartItem> */
class CartItemFactory extends Factory
{
    // モデルのデフォルト状態を定義する。
    //
    // @return array<string, mixed>
    public function definition(): array
    {
        $tenant = Tenant::factory()->create();

        return [
            'cart_id' => Cart::factory()->forTenant($tenant),
            'tenant_id' => $tenant->id,
            'menu_item_id' => MenuItem::factory()->state(['tenant_id' => $tenant->id]),
            'quantity' => fake()->numberBetween(1, 5),
        ];
    }

    // 特定のカートを設定する。
    public function forCart(Cart $cart): static
    {
        return $this->state(fn (array $attributes) => [
            'cart_id' => $cart->id,
            'tenant_id' => $cart->tenant_id,
        ]);
    }

    // 特定のメニュー商品を設定する。
    public function forMenuItem(MenuItem $menuItem): static
    {
        return $this->state(fn (array $attributes) => [
            'menu_item_id' => $menuItem->id,
        ]);
    }

    // 特定の数量を設定する。
    public function quantity(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }
}
