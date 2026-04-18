<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

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
use App\Services\CartService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    private CartService $cartService;

    private User $customer;

    private Tenant $tenant;

    private MenuItem $menuItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cartService = app(CartService::class);
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

    public function test_get_user_carts_ユーザーの全カートを取得できる(): void
    {
        $tenant2 = Tenant::factory()->create();

        Cart::factory()->forUser($this->customer)->forTenant($this->tenant)->create();
        Cart::factory()->forUser($this->customer)->forTenant($tenant2)->create();

        $carts = $this->cartService->getUserCarts($this->customer);

        $this->assertCount(2, $carts);
    }

    public function test_get_user_carts_他ユーザーのカートは含まれない(): void
    {
        $otherCustomer = User::factory()->customer()->create();

        Cart::factory()->forUser($this->customer)->forTenant($this->tenant)->create();
        Cart::factory()->forUser($otherCustomer)->forTenant($this->tenant)->create();

        $carts = $this->cartService->getUserCarts($this->customer);

        $this->assertCount(1, $carts);
    }

    public function test_get_user_carts_前日以前のカートは削除される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 20, 12, 0, 0));

        try {
            $expiredCart = Cart::factory()->forUser($this->customer)->forTenant($this->tenant)->create([
                'updated_at' => Carbon::yesterday(),
            ]);
            $activeTenant = Tenant::factory()->create();
            $activeCart = Cart::factory()->forUser($this->customer)->forTenant($activeTenant)->create([
                'updated_at' => Carbon::today(),
            ]);

            $carts = $this->cartService->getUserCarts($this->customer);

            $this->assertCount(1, $carts);
            $this->assertTrue($carts->contains('id', $activeCart->id));
            $this->assertDatabaseMissing('carts', ['id' => $expiredCart->id]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_get_user_carts_当日更新のカートは削除されない(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 20, 12, 0, 0));

        try {
            $todayCart = Cart::factory()->forUser($this->customer)->forTenant($this->tenant)->create([
                'updated_at' => Carbon::today(),
            ]);

            $carts = $this->cartService->getUserCarts($this->customer);

            $this->assertCount(1, $carts);
            $this->assertTrue($carts->contains('id', $todayCart->id));
            $this->assertDatabaseHas('carts', ['id' => $todayCart->id]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_get_or_create_cart_既存のカートを返す(): void
    {
        $existingCart = Cart::factory()->forUser($this->customer)->forTenant($this->tenant)->create();

        $cart = $this->cartService->getOrCreateCart($this->customer, $this->tenant->id);

        $this->assertEquals($existingCart->id, $cart->id);
    }

    public function test_get_or_create_cart_カートがなければ作成する(): void
    {
        $cart = $this->cartService->getOrCreateCart($this->customer, $this->tenant->id);

        $this->assertDatabaseHas('carts', [
            'id' => $cart->id,
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_add_item_商品をカートに追加できる(): void
    {
        $cartItem = $this->cartService->addItem(
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

        $cartItem = $this->cartService->addItem(
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

        $this->cartService->addItem(
            user: $this->customer,
            tenantId: $this->tenant->id,
            menuItemId: $soldOutItem->id,
            quantity: 1
        );
    }

    public function test_add_item_トランザクション開始時に売り切れ化された商品は例外をスロー(): void
    {
        DB::partialMock()
            ->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function (callable $callback) {
                MenuItem::whereKey($this->menuItem->id)->update(['is_sold_out' => true]);

                return $callback();
            });

        try {
            $this->cartService->addItem(
                user: $this->customer,
                tenantId: $this->tenant->id,
                menuItemId: $this->menuItem->id,
                quantity: 1
            );

            $this->fail('ItemNotAvailableException がスローされませんでした。');
        } catch (ItemNotAvailableException) {
            $this->assertFalse(
                CartItem::query()
                    ->where('tenant_id', $this->tenant->id)
                    ->where('menu_item_id', $this->menuItem->id)
                    ->exists()
            );
        }
    }

    public function test_add_item_非アクティブ商品は例外をスロー(): void
    {
        $inactiveItem = MenuItem::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->expectException(ItemNotAvailableException::class);

        $this->cartService->addItem(
            user: $this->customer,
            tenantId: $this->tenant->id,
            menuItemId: $inactiveItem->id,
            quantity: 1
        );
    }

    public function test_add_item_必須オプション未選択で例外をスロー(): void
    {
        $optionGroup = OptionGroup::factory()->required()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        Option::factory()->create([
            'option_group_id' => $optionGroup->id,
        ]);
        $this->menuItem->optionGroups()->attach($optionGroup->id);

        $this->expectException(InvalidOptionSelectionException::class);

        $this->cartService->addItem(
            user: $this->customer,
            tenantId: $this->tenant->id,
            menuItemId: $this->menuItem->id,
            quantity: 1,
            optionIds: []
        );
    }

    public function test_add_item_オプション選択数超過で例外をスロー(): void
    {
        $optionGroup = OptionGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'max_select' => 1,
            'min_select' => 0,
        ]);
        $option1 = Option::factory()->create(['option_group_id' => $optionGroup->id]);
        $option2 = Option::factory()->create(['option_group_id' => $optionGroup->id]);
        $this->menuItem->optionGroups()->attach($optionGroup->id);

        $this->expectException(InvalidOptionSelectionException::class);

        $this->cartService->addItem(
            user: $this->customer,
            tenantId: $this->tenant->id,
            menuItemId: $this->menuItem->id,
            quantity: 1,
            optionIds: [$option1->id, $option2->id]
        );
    }

    public function test_update_item_数量を更新できる(): void
    {
        $cart = Cart::factory()->forUser($this->customer)->forTenant($this->tenant)->create();
        $cartItem = CartItem::factory()->forCart($cart)->forMenuItem($this->menuItem)->quantity(1)->create();

        $updatedItem = $this->cartService->updateItem(
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

        $updatedItem = $this->cartService->updateItem(
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

        $this->cartService->removeItem($cartItem);

        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
        $this->assertDatabaseMissing('cart_item_options', ['cart_item_id' => $cartItem->id]);
    }

    public function test_clear_cart_カートを全削除できる(): void
    {
        $cart = Cart::factory()->forUser($this->customer)->forTenant($this->tenant)->create();
        $cartItem1 = CartItem::factory()->forCart($cart)->forMenuItem($this->menuItem)->create();
        $cartItem2 = CartItem::factory()->forCart($cart)->forMenuItem($this->menuItem)->create();

        $optionGroup = OptionGroup::factory()->optional()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $option = Option::factory()->create(['option_group_id' => $optionGroup->id]);
        CartItemOption::create([
            'cart_item_id' => $cartItem1->id,
            'tenant_id' => $this->tenant->id,
            'option_id' => $option->id,
        ]);

        $this->cartService->clearCart($cart);

        $this->assertDatabaseMissing('carts', ['id' => $cart->id]);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
        $this->assertDatabaseMissing('cart_item_options', ['cart_item_id' => $cartItem1->id]);
    }

    public function test_validate_options_for_menu_item_商品に紐付いていないオプションは例外をスロー(): void
    {

        $otherOptionGroup = OptionGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $otherOption = Option::factory()->create(['option_group_id' => $otherOptionGroup->id]);

        $this->menuItem->load('optionGroups.options');

        $this->expectException(InvalidOptionSelectionException::class);

        $this->cartService->validateOptionsForMenuItem(
            $this->menuItem,
            [$otherOption->id]
        );
    }

    public function test_validate_options_for_menu_item_有効なオプションは検証を通過(): void
    {
        $optionGroup = OptionGroup::factory()->optional()->create([
            'tenant_id' => $this->tenant->id,
            'max_select' => 2,
        ]);
        $option = Option::factory()->create(['option_group_id' => $optionGroup->id]);
        $this->menuItem->optionGroups()->attach($optionGroup->id);
        $this->menuItem->load('optionGroups.options');

        $this->cartService->validateOptionsForMenuItem(
            $this->menuItem,
            [$option->id]
        );

        $this->assertTrue(true);
    }
}
