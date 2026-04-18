<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cart;

use App\Exceptions\InvalidOptionSelectionException;
use App\Exceptions\ItemNotAvailableException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartItemOption;
use App\Models\MenuItem;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Cart\CartItemWriter;
use App\Services\Cart\CartOptionValidator;
use App\Services\Cart\CartQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartItemWriterTest extends TestCase
{
    use RefreshDatabase;

    private CartItemWriter $writer;

    private User $customer;

    private Tenant $tenant;

    private MenuItem $menuItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->writer = new CartItemWriter(new CartQueryService, new CartOptionValidator);
        $this->tenant = Tenant::factory()->create(['is_active' => true]);
        $this->customer = User::factory()->customer()->create();
        $this->menuItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'テスト商品',
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => false,
        ]);
    }

    public function test_add_item_商品をカートに追加できる(): void
    {
        $cartItem = $this->writer->addItem(
            user: $this->customer,
            tenantId: $this->tenant->id,
            menuItemId: $this->menuItem->id,
            quantity: 2
        );

        $this->assertEquals($this->menuItem->id, $cartItem->menu_item_id);
        $this->assertEquals(2, $cartItem->quantity);
    }

    public function test_add_item_オプション付きで追加できる(): void
    {
        $optionGroup = OptionGroup::factory()->optional()->create([
            'tenant_id' => $this->tenant->id,
            'max_select' => 2,
        ]);
        $option = Option::factory()->create([
            'option_group_id' => $optionGroup->id,
            'price' => 100,
        ]);
        $this->menuItem->optionGroups()->attach($optionGroup->id);

        $cartItem = $this->writer->addItem(
            user: $this->customer,
            tenantId: $this->tenant->id,
            menuItemId: $this->menuItem->id,
            quantity: 1,
            optionIds: [$option->id]
        );

        $this->assertCount(1, $cartItem->options);
        $this->assertEquals($option->id, $cartItem->options->first()->option_id);
    }

    public function test_add_item_売り切れ商品は例外をスロー(): void
    {
        $soldOutItem = MenuItem::factory()->soldOut()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->expectException(ItemNotAvailableException::class);

        $this->writer->addItem(
            user: $this->customer,
            tenantId: $this->tenant->id,
            menuItemId: $soldOutItem->id,
            quantity: 1
        );
    }

    public function test_add_item_必須オプション未選択で例外をスロー(): void
    {
        $optionGroup = OptionGroup::factory()->required()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        Option::factory()->create(['option_group_id' => $optionGroup->id]);
        $this->menuItem->optionGroups()->attach($optionGroup->id);

        $this->expectException(InvalidOptionSelectionException::class);

        $this->writer->addItem(
            user: $this->customer,
            tenantId: $this->tenant->id,
            menuItemId: $this->menuItem->id,
            quantity: 1,
            optionIds: []
        );
    }

    public function test_update_item_数量を更新できる(): void
    {
        $cart = Cart::factory()->forUser($this->customer)->forTenant($this->tenant)->create();
        $cartItem = CartItem::factory()->forCart($cart)->forMenuItem($this->menuItem)->quantity(1)->create();

        $updatedItem = $this->writer->updateItem(
            cartItem: $cartItem,
            quantity: 5
        );

        $this->assertEquals(5, $updatedItem->quantity);
    }

    public function test_update_item_オプションを更新できる(): void
    {
        $optionGroup = OptionGroup::factory()->optional()->create([
            'tenant_id' => $this->tenant->id,
            'max_select' => 2,
        ]);
        $option1 = Option::factory()->create(['option_group_id' => $optionGroup->id]);
        $option2 = Option::factory()->create(['option_group_id' => $optionGroup->id]);
        $this->menuItem->optionGroups()->attach($optionGroup->id);

        $cart = Cart::factory()->forUser($this->customer)->forTenant($this->tenant)->create();
        $cartItem = CartItem::factory()->forCart($cart)->forMenuItem($this->menuItem)->create();
        CartItemOption::create([
            'cart_item_id' => $cartItem->id,
            'tenant_id' => $this->tenant->id,
            'option_id' => $option1->id,
        ]);

        $updatedItem = $this->writer->updateItem(
            cartItem: $cartItem,
            optionIds: [$option2->id]
        );

        $this->assertCount(1, $updatedItem->options);
        $this->assertEquals($option2->id, $updatedItem->options->first()->option_id);
    }

    public function test_remove_item_カート商品を削除できる(): void
    {
        $cart = Cart::factory()->forUser($this->customer)->forTenant($this->tenant)->create();
        $cartItem = CartItem::factory()->forCart($cart)->forMenuItem($this->menuItem)->create();

        $optionGroup = OptionGroup::factory()->optional()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $option = Option::factory()->create(['option_group_id' => $optionGroup->id]);
        CartItemOption::create([
            'cart_item_id' => $cartItem->id,
            'tenant_id' => $this->tenant->id,
            'option_id' => $option->id,
        ]);

        $this->writer->removeItem($cartItem);

        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
        $this->assertDatabaseMissing('cart_item_options', ['cart_item_id' => $cartItem->id]);
    }

    public function test_clear_cart_カートを全削除できる(): void
    {
        $cart = Cart::factory()->forUser($this->customer)->forTenant($this->tenant)->create();
        CartItem::factory()->forCart($cart)->forMenuItem($this->menuItem)->create();
        CartItem::factory()->forCart($cart)->forMenuItem($this->menuItem)->create();

        $this->writer->clearCart($cart);

        $this->assertDatabaseMissing('carts', ['id' => $cart->id]);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
    }

    public function test_clear_items_アイテムのみクリアしカートは残る(): void
    {
        $cart = Cart::factory()->forUser($this->customer)->forTenant($this->tenant)->create();
        $cartItem = CartItem::factory()->forCart($cart)->forMenuItem($this->menuItem)->create();

        $optionGroup = OptionGroup::factory()->optional()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $option = Option::factory()->create(['option_group_id' => $optionGroup->id]);
        CartItemOption::create([
            'cart_item_id' => $cartItem->id,
            'tenant_id' => $this->tenant->id,
            'option_id' => $option->id,
        ]);

        $this->writer->clearItems($cart);

        $this->assertDatabaseHas('carts', ['id' => $cart->id]);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
        $this->assertDatabaseMissing('cart_item_options', ['cart_item_id' => $cartItem->id]);
    }
}
