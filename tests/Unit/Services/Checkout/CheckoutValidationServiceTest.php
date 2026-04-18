<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Checkout;

use App\Enums\PaymentMethod;
use App\Exceptions\EmptyCartException;
use App\Exceptions\ItemNotAvailableException;
use App\Exceptions\PaymentMethodNotAvailableException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\MenuItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Checkout\CheckoutValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private CheckoutValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CheckoutValidationService;
    }

    public function test_validate_cart_not_empty_passes_with_items(): void
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

        $this->service->validateCartNotEmpty($cart);
        $this->assertTrue(true);
    }

    public function test_validate_cart_not_empty_fails_with_empty_cart(): void
    {

        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->expectException(EmptyCartException::class);
        $this->service->validateCartNotEmpty($cart);
    }

    public function test_validate_items_availability_passes_with_available_items(): void
    {

        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_sold_out' => false,
        ]);
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
        ]);

        $this->service->validateItemsAvailability($cart);
        $this->assertTrue(true);
    }

    public function test_validate_items_availability_fails_with_inactive_item(): void
    {

        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => false,
        ]);
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
        ]);

        $this->expectException(ItemNotAvailableException::class);
        $this->service->validateItemsAvailability($cart);
    }

    public function test_validate_items_availability_fails_with_sold_out_item(): void
    {

        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_sold_out' => true,
        ]);
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
        ]);

        $this->expectException(ItemNotAvailableException::class);
        $this->service->validateItemsAvailability($cart);
    }

    public function test_validate_payment_method_passes_with_active_tenant(): void
    {

        $tenant = Tenant::factory()->create(['is_active' => true]);

        $this->service->validatePaymentMethod($tenant, PaymentMethod::Card);
        $this->assertTrue(true);
    }

    public function test_validate_payment_method_fails_with_inactive_tenant(): void
    {

        $tenant = Tenant::factory()->create(['is_active' => false]);

        $this->expectException(PaymentMethodNotAvailableException::class);
        $this->service->validatePaymentMethod($tenant, PaymentMethod::Card);
    }

    public function test_validate_for_checkout_passes_all_validations(): void
    {

        $tenant = Tenant::factory()->create(['is_active' => true]);
        $this->setTenantAlwaysOpen($tenant);
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_sold_out' => false,
        ]);
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
        ]);

        $this->service->validateForCheckout($cart, PaymentMethod::Card);
        $this->assertTrue(true);
    }

    public function test_validate_for_checkout_fails_on_first_validation_error(): void
    {

        $tenant = Tenant::factory()->create(['is_active' => true]);
        $user = User::factory()->customer()->create();
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->expectException(EmptyCartException::class);
        $this->service->validateForCheckout($cart, PaymentMethod::Card);
    }
}
