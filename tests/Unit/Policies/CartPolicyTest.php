<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Cart;
use App\Models\User;
use App\Policies\CartPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartPolicyTest extends TestCase
{
    use RefreshDatabase;

    private CartPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CartPolicy;
    }

    public function test_customer_can_manage_only_own_cart(): void
    {
        $owner = User::factory()->customer()->create();
        $otherCustomer = User::factory()->customer()->create();
        $staff = User::factory()->tenantStaff()->create();

        $cart = Cart::factory()->forUser($owner)->create();

        foreach (['view', 'update', 'delete', 'addItem', 'checkout'] as $ability) {
            $this->assertTrue($this->policy->{$ability}($owner, $cart));
            $this->assertFalse($this->policy->{$ability}($otherCustomer, $cart));
            $this->assertFalse($this->policy->{$ability}($staff, $cart));
        }
    }
}
