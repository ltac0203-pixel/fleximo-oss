<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartItemOption;
use App\Models\MenuItem;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CartPageTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $customer;

    private User $tenantAdmin;

    private User $tenantStaff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $this->tenantAdmin = User::factory()->create([
            'role' => 'tenant_admin',
        ]);
        TenantUser::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->tenantAdmin->id,
        ]);

        $this->tenantStaff = User::factory()->create([
            'role' => 'tenant_staff',
        ]);
        TenantUser::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->tenantStaff->id,
        ]);
    }

    public function test_authenticated_customer_can_access_cart_page(): void
    {
        $response = $this->actingAs($this->customer)
            ->get('/order/cart');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Customer/Cart/Index'));
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/order/cart');

        $response->assertRedirect('/login');
    }

    public function test_tenant_admin_cannot_access_cart_page(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->get('/order/cart');

        $response->assertStatus(403);
    }

    public function test_tenant_staff_cannot_access_cart_page(): void
    {
        $response = $this->actingAs($this->tenantStaff)
            ->get('/order/cart');

        $response->assertStatus(403);
    }

    // N+1問題が発生していないことを検証
    public function test_cart_page_does_not_cause_n_plus_one_queries(): void
    {
        // 複数のカートアイテムとオプションを作成
        $cart = Cart::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $menuItems = MenuItem::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        foreach ($menuItems as $menuItem) {
            $cartItem = CartItem::factory()->create([
                'cart_id' => $cart->id,
                'tenant_id' => $this->tenant->id,
                'menu_item_id' => $menuItem->id,
            ]);

            // 各アイテムに複数のオプションを追加
            $optionGroup = OptionGroup::factory()->create([
                'tenant_id' => $this->tenant->id,
            ]);

            // MenuItemとOptionGroupを関連付け
            $menuItem->optionGroups()->attach($optionGroup->id);

            $options = Option::factory()->count(3)->create([
                'option_group_id' => $optionGroup->id,
            ]);

            foreach ($options as $option) {
                CartItemOption::factory()->create([
                    'cart_item_id' => $cartItem->id,
                    'tenant_id' => $this->tenant->id,
                    'option_id' => $option->id,
                ]);
            }
        }

        // クエリ数をカウント
        DB::enableQueryLog();

        $response = $this->actingAs($this->customer)->get('/order/cart');

        $queryCount = count(DB::getQueryLog());

        // N+1が発生していない場合、クエリ数は少なく抑えられる
        // 15クエリ以下を目標とする（認証、セッション、カート取得、テナント取得など）
        $this->assertLessThan(15, $queryCount, "N+1 query detected: {$queryCount} queries executed");

        $response->assertOk();
    }
}
