<?php

declare(strict_types=1);

namespace Tests\Feature\Performance;

use App\Enums\UserRole;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\MenuItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\QueryCountAssertions;
use Tests\TestCase;

class CartQueryTest extends TestCase
{
    use QueryCountAssertions;
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->customer = User::factory()->create([
            'role' => UserRole::Customer,
        ]);

        // 3テナント x 5アイテム のマルチテナントカートを作成
        for ($t = 0; $t < 3; $t++) {
            $tenant = Tenant::factory()->create();
            $this->setTenantAlwaysOpen($tenant);

            $cart = Cart::factory()->create([
                'user_id' => $this->customer->id,
                'tenant_id' => $tenant->id,
            ]);

            $menuItems = MenuItem::factory()
                ->count(5)
                ->create(['tenant_id' => $tenant->id]);

            foreach ($menuItems as $menuItem) {
                CartItem::factory()
                    ->forCart($cart)
                    ->forMenuItem($menuItem)
                    ->create();
            }
        }
    }

    public function test_cart_api_query_count_constant_with_multiple_carts(): void
    {
        $this->assertQueryCountLessThan(20, function () {
            $this->actingAs($this->customer)
                ->getJson('/api/customer/cart')
                ->assertOk();
        });
    }
}
