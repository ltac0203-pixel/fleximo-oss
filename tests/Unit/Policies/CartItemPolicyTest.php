<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use App\Policies\CartItemPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartItemPolicyTest extends TestCase
{
    use RefreshDatabase;

    private CartItemPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CartItemPolicy;
    }

    public function test_customer_can_manage_only_own_cart_item(): void
    {
        $owner = User::factory()->customer()->create();
        $otherCustomer = User::factory()->customer()->create();
        $staff = User::factory()->tenantStaff()->create();

        $cart = Cart::factory()->forUser($owner)->create();
        $cartItem = CartItem::factory()->forCart($cart)->create();

        foreach (['view', 'update', 'delete'] as $ability) {
            $this->assertTrue($this->policy->{$ability}($owner, $cartItem));
            $this->assertFalse($this->policy->{$ability}($otherCustomer, $cartItem));
            $this->assertFalse($this->policy->{$ability}($staff, $cartItem));
        }
    }
}
