<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\MenuItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_can_be_created(): void
    {
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();

        $cart = Cart::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_user_tenant_combination_must_be_unique(): void
    {
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();

        Cart::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->expectException(QueryException::class);

        Cart::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_user_can_have_carts_for_different_tenants(): void
    {
        $user = User::factory()->customer()->create();
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $cart1 = Cart::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant1->id,
        ]);

        $cart2 = Cart::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant2->id,
        ]);

        $this->assertDatabaseCount('carts', 2);
        $this->assertNotEquals($cart1->id, $cart2->id);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertInstanceOf(User::class, $cart->user);
        $this->assertEquals($user->id, $cart->user->id);
    }

    public function test_belongs_to_tenant(): void
    {
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $cart->tenant);
        $this->assertEquals($tenant->id, $cart->tenant->id);
    }

    public function test_has_many_items(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $menuItem1 = MenuItem::factory()->create(['tenant_id' => $tenant->id, 'price' => 500]);
        $menuItem2 = MenuItem::factory()->create(['tenant_id' => $tenant->id, 'price' => 300]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem1->id,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem2->id,
        ]);

        $this->assertCount(2, $cart->items);
    }

    public function test_total_attribute_calculates_sum_of_item_subtotals(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $menuItem1 = MenuItem::factory()->create(['tenant_id' => $tenant->id, 'price' => 500]);
        $menuItem2 = MenuItem::factory()->create(['tenant_id' => $tenant->id, 'price' => 300]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem1->id,
            'quantity' => 2,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem2->id,
            'quantity' => 3,
        ]);

        $cart = Cart::withFullRelations()->find($cart->id);

        $this->assertEquals(1900, $cart->total);
    }

    public function test_item_count_attribute_calculates_sum_of_quantities(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $menuItem1 = MenuItem::factory()->create(['tenant_id' => $tenant->id]);
        $menuItem2 = MenuItem::factory()->create(['tenant_id' => $tenant->id]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem1->id,
            'quantity' => 2,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem2->id,
            'quantity' => 3,
        ]);

        $cart->load('items');

        $this->assertEquals(5, $cart->item_count);
    }

    public function test_is_empty_returns_true_for_empty_cart(): void
    {
        $cart = Cart::factory()->create();
        $cart->load('items');

        $this->assertTrue($cart->isEmpty());
    }

    public function test_is_empty_returns_false_for_non_empty_cart(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create(['tenant_id' => $tenant->id]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
        ]);

        $cart->load('items');

        $this->assertFalse($cart->isEmpty());
    }

    public function test_total_is_zero_for_empty_cart(): void
    {
        $cart = Cart::factory()->create();
        $cart->load('items');

        $this->assertEquals(0, $cart->total);
    }

    public function test_item_count_is_zero_for_empty_cart(): void
    {
        $cart = Cart::factory()->create();
        $cart->load('items');

        $this->assertEquals(0, $cart->item_count);
    }

    public function test_cart_is_deleted_when_user_is_deleted(): void
    {
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $cartId = $cart->id;
        $user->delete();

        $this->assertDatabaseMissing('carts', ['id' => $cartId]);
    }

    public function test_cart_is_deleted_when_tenant_is_deleted(): void
    {
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $cartId = $cart->id;
        $tenant->delete();

        $this->assertDatabaseMissing('carts', ['id' => $cartId]);
    }
}
