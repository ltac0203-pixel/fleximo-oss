<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartItemOption;
use App\Models\MenuItem;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_item_can_be_created(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create(['tenant_id' => $tenant->id]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 2,
        ]);
    }

    public function test_cart_item_is_not_scoped_by_tenant(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        $user = User::factory()->customer()->create();

        $menuItem1 = MenuItem::factory()->create(['tenant_id' => $tenant1->id]);
        $menuItem2 = MenuItem::factory()->create(['tenant_id' => $tenant2->id]);

        $cart1 = Cart::factory()->create(['user_id' => $user->id, 'tenant_id' => $tenant1->id]);
        $cart2 = Cart::factory()->create(['user_id' => $user->id, 'tenant_id' => $tenant2->id]);

        CartItem::factory()->create([
            'cart_id' => $cart1->id,
            'tenant_id' => $tenant1->id,
            'menu_item_id' => $menuItem1->id,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart2->id,
            'tenant_id' => $tenant2->id,
            'menu_item_id' => $menuItem2->id,
        ]);

        // TenantContext を設定しなくても両テナントのカートアイテムが取得できる
        $items = CartItem::all();

        $this->assertCount(2, $items);
    }

    public function test_belongs_to_cart(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create(['tenant_id' => $tenant->id]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
        ]);

        $this->assertInstanceOf(Cart::class, $cartItem->cart);
        $this->assertEquals($cart->id, $cartItem->cart->id);
    }

    public function test_belongs_to_menu_item(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create(['tenant_id' => $tenant->id]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
        ]);

        $this->assertInstanceOf(MenuItem::class, $cartItem->menuItem);
        $this->assertEquals($menuItem->id, $cartItem->menuItem->id);
    }

    public function test_has_many_options(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create(['tenant_id' => $tenant->id]);
        $optionGroup = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);
        $option1 = Option::factory()->create(['option_group_id' => $optionGroup->id]);
        $option2 = Option::factory()->create(['option_group_id' => $optionGroup->id]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
        ]);

        CartItemOption::factory()->create([
            'cart_item_id' => $cartItem->id,
            'tenant_id' => $tenant->id,
            'option_id' => $option1->id,
        ]);

        CartItemOption::factory()->create([
            'cart_item_id' => $cartItem->id,
            'tenant_id' => $tenant->id,
            'option_id' => $option2->id,
        ]);

        $cartItem->load('options');

        $this->assertCount(2, $cartItem->options);
    }

    public function test_subtotal_without_options(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create(['tenant_id' => $tenant->id, 'price' => 500]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 3,
        ]);

        $cartItem->load(['menuItem', 'options']);

        $this->assertEquals(1500, $cartItem->subtotal);
    }

    public function test_subtotal_with_options(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create(['tenant_id' => $tenant->id, 'price' => 500]);
        $optionGroup = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);
        $option1 = Option::factory()->create(['option_group_id' => $optionGroup->id, 'price' => 100]);
        $option2 = Option::factory()->create(['option_group_id' => $optionGroup->id, 'price' => 50]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 2,
        ]);

        CartItemOption::factory()->create([
            'cart_item_id' => $cartItem->id,
            'tenant_id' => $tenant->id,
            'option_id' => $option1->id,
        ]);

        CartItemOption::factory()->create([
            'cart_item_id' => $cartItem->id,
            'tenant_id' => $tenant->id,
            'option_id' => $option2->id,
        ]);

        $cartItem->load(['menuItem', 'options.option']);

        $this->assertEquals(1300, $cartItem->subtotal);
    }

    public function test_casts_quantity_to_integer(): void
    {
        $tenant = Tenant::factory()->create();
        $menuItem = MenuItem::factory()->create(['tenant_id' => $tenant->id]);
        $cart = Cart::factory()->create(['tenant_id' => $tenant->id]);

        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => '5',
        ]);

        $this->assertIsInt($cartItem->quantity);
        $this->assertEquals(5, $cartItem->quantity);
    }

    public function test_cart_item_is_deleted_when_cart_is_deleted(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create(['tenant_id' => $tenant->id]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
        ]);

        $cartItemId = $cartItem->id;
        $cart->delete();

        $this->assertDatabaseMissing('cart_items', ['id' => $cartItemId]);
    }
}
