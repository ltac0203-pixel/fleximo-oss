<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\Checkout\CheckoutOrchestrator;
use App\Services\Fincode\FincodeClient;
use App\Services\ThreeDsAuthResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

class CheckoutPageTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $customer;

    private User $tenantAdmin;

    private MenuItem $menuItem;

    protected function setUp(): void
    {
        parent::setUp();

        $mockClient = Mockery::mock(FincodeClient::class);
        $this->app->instance(FincodeClient::class, $mockClient);

        $this->tenant = Tenant::factory()->create([
            'status' => 'active',
            'is_active' => true,
        ]);
        $this->setTenantAlwaysOpen($this->tenant);

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

        $this->menuItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => false,
        ]);
    }

    public function test_authenticated_customer_can_access_checkout_page_with_cart(): void
    {

        $cart = Cart::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $this->menuItem->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->customer)
            ->get('/order/checkout');

        $response->assertStatus(200);
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Customer/Checkout/Index')
                ->has('cart')
                ->has('fincodePublicKey')
                ->has('isProduction')
        );
    }

    public function test_checkout_page_redirects_to_cart_when_cart_is_empty(): void
    {
        $response = $this->actingAs($this->customer)
            ->get('/order/checkout');

        $response->assertRedirect(route('order.cart.show'));
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/order/checkout');

        $response->assertRedirect('/login');
    }

    public function test_tenant_admin_cannot_access_checkout_page(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->get('/order/checkout');

        $response->assertStatus(403);
    }

    public function test_authenticated_customer_can_access_complete_page_for_own_order(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::Paid,
        ]);

        $response = $this->actingAs($this->customer)
            ->get("/order/checkout/complete/{$order->id}");

        $response->assertStatus(200);
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Customer/Checkout/Complete')
                ->has('order')
        );
    }

    public function test_customer_cannot_access_other_users_complete_page(): void
    {
        $otherCustomer = User::factory()->customer()->create();
        $order = Order::factory()->create([
            'user_id' => $otherCustomer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::Paid,
        ]);

        $response = $this->actingAs($this->customer)
            ->get("/order/checkout/complete/{$order->id}");

        $response->assertStatus(403);
    }

    public function test_authenticated_customer_can_access_failed_page(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::PaymentFailed,
        ]);

        $response = $this->actingAs($this->customer)
            ->get("/order/checkout/failed/{$order->id}");

        $response->assertStatus(200);
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Customer/Checkout/Failed')
                ->has('order')
        );
    }

    public function test_failed_page_can_be_accessed_without_order(): void
    {
        $response = $this->actingAs($this->customer)
            ->get('/order/checkout/failed');

        $response->assertStatus(200);
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Customer/Checkout/Failed')
                ->where('order', null)
        );
    }

    public function test_authenticated_customer_can_access_paypay_callback_page(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'method' => PaymentMethod::PayPay,
            'status' => PaymentStatus::Processing,
        ]);

        $url = URL::temporarySignedRoute(
            'order.checkout.callback.paypay',
            now()->addMinutes(30),
            ['payment' => $payment->id]
        );

        $response = $this->actingAs($this->customer)
            ->get($url);

        $response->assertStatus(200);
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Customer/Checkout/PayPayCallback')
                ->has('payment')
                ->has('success')
        );
    }

    public function test_customer_cannot_access_other_users_paypay_callback(): void
    {
        $otherCustomer = User::factory()->customer()->create();
        $order = Order::factory()->create([
            'user_id' => $otherCustomer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'method' => PaymentMethod::PayPay,
            'status' => PaymentStatus::Processing,
        ]);

        $url = URL::temporarySignedRoute(
            'order.checkout.callback.paypay',
            now()->addMinutes(30),
            ['payment' => $payment->id]
        );

        $response = $this->actingAs($this->customer)
            ->get($url);

        $response->assertStatus(403);
    }

    public function test_authenticated_customer_can_access_3ds_callback_with_param_query(): void
    {
        $payment = $this->createProcessingCardPaymentForCustomer($this->customer);
        $url = URL::temporarySignedRoute(
            'order.checkout.callback.3ds',
            now()->addMinutes(30),
            [
                'payment' => $payment->id,
                'param' => 'callback_param',
                'event' => 'ThreeDSecureMethodFinished',
            ]
        );

        $mockOrchestrator = Mockery::mock(CheckoutOrchestrator::class);
        $mockOrchestrator->shouldReceive('process3dsCallback')
            ->once()
            ->withArgs(function ($targetPayment, $param, $event) use ($payment) {
                return $targetPayment->id === $payment->id
                    && $param === 'callback_param'
                    && $event === 'ThreeDSecureMethodFinished';
            })
            ->andReturn(ThreeDsAuthResult::authenticated($payment));
        $this->app->instance(CheckoutOrchestrator::class, $mockOrchestrator);

        $response = $this->actingAs($this->customer)
            ->get($url);

        $response->assertRedirect(route('order.checkout.complete', ['order' => $payment->order_id]));
    }

    public function test_authenticated_customer_can_access_3ds_callback_with_pending_payment(): void
    {
        $payment = $this->createCardPaymentForCustomer($this->customer, PaymentStatus::Pending);
        $url = URL::temporarySignedRoute(
            'order.checkout.callback.3ds',
            now()->addMinutes(30),
            [
                'payment' => $payment->id,
                'MD' => 'a_pending_pw7SSsl9Tw2MCbhU63z88A',
            ]
        );

        $mockOrchestrator = Mockery::mock(CheckoutOrchestrator::class);
        $mockOrchestrator->shouldReceive('process3dsCallback')
            ->once()
            ->withArgs(function ($targetPayment, $param, $event) use ($payment) {
                return $targetPayment->id === $payment->id
                    && $param === 'a_pending_pw7SSsl9Tw2MCbhU63z88A'
                    && $event === 'AuthResultReady';
            })
            ->andReturn(ThreeDsAuthResult::authenticated($payment));
        $this->app->instance(CheckoutOrchestrator::class, $mockOrchestrator);

        $response = $this->actingAs($this->customer)
            ->get($url);

        $response->assertRedirect(route('order.checkout.complete', ['order' => $payment->order_id]));
    }

    public function test_authenticated_customer_can_access_3ds_callback_with_md_query(): void
    {
        $payment = $this->createProcessingCardPaymentForCustomer($this->customer);
        $url = URL::temporarySignedRoute(
            'order.checkout.callback.3ds',
            now()->addMinutes(30),
            [
                'payment' => $payment->id,
                'MD' => 'a_pw7SSsl9Tw2MCbhU63z88A',
            ]
        );

        $mockOrchestrator = Mockery::mock(CheckoutOrchestrator::class);
        $mockOrchestrator->shouldReceive('process3dsCallback')
            ->once()
            ->withArgs(function ($targetPayment, $param, $event) use ($payment) {
                return $targetPayment->id === $payment->id
                    && $param === 'a_pw7SSsl9Tw2MCbhU63z88A'
                    && $event === 'AuthResultReady';
            })
            ->andReturn(ThreeDsAuthResult::authenticated($payment));
        $this->app->instance(CheckoutOrchestrator::class, $mockOrchestrator);

        $response = $this->actingAs($this->customer)
            ->get($url);

        $response->assertRedirect(route('order.checkout.complete', ['order' => $payment->order_id]));
    }

    public function test_authenticated_customer_can_access_3ds_callback_with_appended_query_after_signature(): void
    {
        $payment = $this->createProcessingCardPaymentForCustomer($this->customer);
        $signedUrl = URL::temporarySignedRoute(
            'order.checkout.callback.3ds',
            now()->addMinutes(30),
            ['payment' => $payment->id]
        );
        $url = $signedUrl.'&param=callback_param&event=ThreeDSecureMethodFinished';

        $mockOrchestrator = Mockery::mock(CheckoutOrchestrator::class);
        $mockOrchestrator->shouldReceive('process3dsCallback')
            ->once()
            ->withArgs(function ($targetPayment, $param, $event) use ($payment) {
                return $targetPayment->id === $payment->id
                    && $param === 'callback_param'
                    && $event === 'ThreeDSecureMethodFinished';
            })
            ->andReturn(ThreeDsAuthResult::authenticated($payment));
        $this->app->instance(CheckoutOrchestrator::class, $mockOrchestrator);

        $response = $this->actingAs($this->customer)
            ->get($url);

        $response->assertRedirect(route('order.checkout.complete', ['order' => $payment->order_id]));
    }

    public function test_authenticated_customer_can_access_3ds_callback_with_malformed_signature_query(): void
    {
        $payment = $this->createProcessingCardPaymentForCustomer($this->customer);
        $signedUrl = URL::temporarySignedRoute(
            'order.checkout.callback.3ds',
            now()->addMinutes(30),
            ['payment' => $payment->id]
        );
        $url = $signedUrl.'?MD=a_pw7SSsl9Tw2MCbhU63z88A';

        $mockOrchestrator = Mockery::mock(CheckoutOrchestrator::class);
        $mockOrchestrator->shouldReceive('process3dsCallback')
            ->once()
            ->withArgs(function ($targetPayment, $param, $event) use ($payment) {
                return $targetPayment->id === $payment->id
                    && $param === 'a_pw7SSsl9Tw2MCbhU63z88A'
                    && $event === 'AuthResultReady';
            })
            ->andReturn(ThreeDsAuthResult::authenticated($payment));
        $this->app->instance(CheckoutOrchestrator::class, $mockOrchestrator);

        $response = $this->actingAs($this->customer)
            ->get($url);

        $response->assertRedirect(route('order.checkout.complete', ['order' => $payment->order_id]));
    }

    public function test_3ds_callback_allows_post_without_csrf_token_when_signed(): void
    {
        $payment = $this->createProcessingCardPaymentForCustomer($this->customer);
        $url = URL::temporarySignedRoute(
            'order.checkout.callback.3ds',
            now()->addMinutes(30),
            ['payment' => $payment->id]
        );

        $mockOrchestrator = Mockery::mock(CheckoutOrchestrator::class);
        $mockOrchestrator->shouldReceive('process3dsCallback')
            ->once()
            ->withArgs(function ($targetPayment, $param, $event) use ($payment) {
                return $targetPayment->id === $payment->id
                    && $param === 'a_posted_md'
                    && $event === 'AuthResultReady';
            })
            ->andReturn(ThreeDsAuthResult::authenticated($payment));
        $this->app->instance(CheckoutOrchestrator::class, $mockOrchestrator);

        $response = $this->post($url, [
            'MD' => 'a_posted_md',
        ]);

        $response->assertRedirect(route('order.checkout.complete', ['order' => $payment->order_id]));
    }

    public function test_3ds_callback_rejects_unsigned_url(): void
    {
        $payment = $this->createProcessingCardPaymentForCustomer($this->customer);

        $response = $this->get("/order/checkout/callback/3ds/{$payment->id}?MD=unsigned");

        $response->assertForbidden();
    }

    public function test_3ds_callback_order_route_with_non_numeric_payment_is_not_found(): void
    {
        $response = $this->post('/order/checkout/callback/3ds/malicious-path', [
            'MD' => 'attacker_payload',
        ]);

        $response->assertNotFound();
    }

    public function test_3ds_callback_legacy_route_with_non_numeric_payment_is_not_found(): void
    {
        $response = $this->post('/callback/3ds/malicious-path', [
            'MD' => 'attacker_payload',
        ]);

        $response->assertNotFound();
    }

    private function createProcessingCardPaymentForCustomer(User $customer): Payment
    {
        return $this->createCardPaymentForCustomer($customer, PaymentStatus::Processing);
    }

    private function createCardPaymentForCustomer(User $customer, PaymentStatus $paymentStatus): Payment
    {
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::PendingPayment,
        ]);

        return Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'method' => PaymentMethod::Card,
            'status' => $paymentStatus,
        ]);
    }
}
