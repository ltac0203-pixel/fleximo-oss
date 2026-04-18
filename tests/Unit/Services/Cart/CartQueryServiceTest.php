<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\MenuItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Cart\CartQueryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    private CartQueryService $queryService;

    private User $customer;

    private Tenant $tenant;

    private MenuItem $menuItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryService = new CartQueryService;
        $this->tenant = Tenant::factory()->create(['is_active' => true]);
        $this->customer = User::factory()->customer()->create();
        $this->menuItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'is_sold_out' => false,
        ]);
    }

    public function test_find_user_cart_for_tenant_カートが存在する場合に返す(): void
    {
        $cart = Cart::factory()->forUser($this->customer)->forTenant($this->tenant)->create();

        $result = $this->queryService->findUserCartForTenant($this->customer, $this->tenant->id);

        $this->assertNotNull($result);
        $this->assertEquals($cart->id, $result->id);
    }

    public function test_find_user_cart_for_tenant_カートがない場合はnull(): void
    {
        $result = $this->queryService->findUserCartForTenant($this->customer, $this->tenant->id);

        $this->assertNull($result);
    }

    public function test_get_user_carts_ユーザーの全カートを取得できる(): void
    {
        $tenant2 = Tenant::factory()->create();

        Cart::factory()->forUser($this->customer)->forTenant($this->tenant)->create();
        Cart::factory()->forUser($this->customer)->forTenant($tenant2)->create();

        $carts = $this->queryService->getUserCarts($this->customer);

        $this->assertCount(2, $carts);
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

            $carts = $this->queryService->getUserCarts($this->customer);

            $this->assertCount(1, $carts);
            $this->assertTrue($carts->contains('id', $activeCart->id));
            $this->assertDatabaseMissing('carts', ['id' => $expiredCart->id]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_get_or_create_cart_既存のカートを返す(): void
    {
        $existingCart = Cart::factory()->forUser($this->customer)->forTenant($this->tenant)->create();

        $cart = $this->queryService->getOrCreateCart($this->customer, $this->tenant->id);

        $this->assertEquals($existingCart->id, $cart->id);
    }

    public function test_get_or_create_cart_カートがなければ作成する(): void
    {
        $cart = $this->queryService->getOrCreateCart($this->customer, $this->tenant->id);

        $this->assertDatabaseHas('carts', [
            'id' => $cart->id,
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_get_checkout_cart_アイテムがあるカートを返す(): void
    {
        $cart = Cart::factory()->forUser($this->customer)->forTenant($this->tenant)->create();
        CartItem::factory()->forCart($cart)->forMenuItem($this->menuItem)->create();

        $result = $this->queryService->getCheckoutCart($this->customer);

        $this->assertNotNull($result);
        $this->assertEquals($cart->id, $result->id);
    }

    public function test_get_checkout_cart_空カートしかない場合はnull(): void
    {
        Cart::factory()->forUser($this->customer)->forTenant($this->tenant)->create();

        $result = $this->queryService->getCheckoutCart($this->customer);

        $this->assertNull($result);
    }

    public function test_get_cart_with_relations_or_fail_リレーション付きで取得(): void
    {
        $cart = Cart::factory()->forUser($this->customer)->forTenant($this->tenant)->create();

        $result = $this->queryService->getCartWithRelationsOrFail($cart->id);

        $this->assertEquals($cart->id, $result->id);
    }
}
