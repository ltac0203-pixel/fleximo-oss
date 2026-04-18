<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Customer;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutController3dsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $customer;

    private Cart $cart;

    private MenuItem $menuItem;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'fincode.is_production' => false,
            'fincode.test_api_url' => 'https://api.test.fincode.jp/v1',
            'fincode.api_key' => 'test_api_key',
            'fincode.shop_id' => 'test_shop_id',
        ]);

        $this->tenant = Tenant::factory()->create([
            'fincode_shop_id' => 'tenant_shop_123',
        ]);
        $this->setTenantAlwaysOpen($this->tenant);

        $this->customer = User::factory()->create([
            'role' => UserRole::Customer,
        ]);

        $this->menuItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 1000,
        ]);

        $this->cart = Cart::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);

        CartItem::factory()->create([
            'cart_id' => $this->cart->id,
            'menu_item_id' => $this->menuItem->id,
            'quantity' => 1,
        ]);
    }

    public function test_finalize_returns_acs_url_redirect(): void
    {
        // まずチェックアウトを実行（新規カードの場合、顧客登録も発生する）
        Http::fake([
            'api.test.fincode.jp/v1/customers' => Http::response([
                'id' => 'cus_3ds_test',
                'name' => $this->customer->name,
                'email' => $this->customer->email,
            ], 200),
            'api.test.fincode.jp/v1/payments' => Http::response([
                'id' => 'pay_3ds_123',
                'access_id' => 'acc_3ds_456',
                'status' => 'AWAITING_CUSTOMER_PAYMENT',
                'amount' => 1000,
                'pay_type' => 'Card',
            ], 200),
        ]);

        $checkoutResponse = $this->actingAs($this->customer)
            ->withIdempotencyKey()
            ->postJson('/api/customer/checkout', [
                'cart_id' => $this->cart->id,
                'payment_method' => 'card',
            ]);

        $checkoutResponse->assertStatus(201);
        $paymentId = $checkoutResponse->json('data.payment.id');

        // 決済実行でacs_urlが返却されるケース
        Http::fake([
            'api.test.fincode.jp/v1/payments/pay_3ds_123' => Http::response([
                'id' => 'pay_3ds_123',
                'access_id' => 'acc_3ds_456',
                'redirect_url' => 'https://acs.example.com/3ds-method',
                'status' => 'AWAITING_AUTHENTICATION',
            ], 200),
        ]);

        $response = $this->actingAs($this->customer)
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/finalize', [
                'payment_id' => $paymentId,
                'token' => 'test_token_123',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'requires_3ds_redirect' => true,
                    'redirect_url' => 'https://acs.example.com/3ds-method',
                    'payment_id' => $paymentId,
                ],
            ]);
    }

    public function test_finalize_returns_acs_url_for_saved_card(): void
    {
        // 注文とPaymentを作成（保存済みカード）
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::PendingPayment,
            'total_amount' => 1000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Pending,
            'amount' => 1000,
            'fincode_id' => 'pay_3ds_123',
            'fincode_access_id' => 'acc_3ds_456',
            'fincode_customer_id' => 'cust_123',
            'fincode_card_id' => 'card_456',
        ]);

        $order->update(['payment_id' => $payment->id]);

        // 決済実行でacs_urlが返却されるケース
        Http::fake([
            'api.test.fincode.jp/v1/payments/pay_3ds_123' => Http::response([
                'id' => 'pay_3ds_123',
                'access_id' => 'acc_3ds_456',
                'redirect_url' => 'https://acs.example.com/3ds-method',
                'status' => 'AWAITING_AUTHENTICATION',
            ], 200),
        ]);

        $response = $this->actingAs($this->customer)
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/finalize', [
                'payment_id' => $payment->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'requires_3ds_redirect' => true,
                    'redirect_url' => 'https://acs.example.com/3ds-method',
                    'payment_id' => $payment->id,
                ],
            ]);
    }

    public function test_3ds_callback_completes_payment_after_challenge(): void
    {
        // 注文とPaymentを作成（3DSチャレンジ待ち状態）
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::PendingPayment,
            'total_amount' => 1000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Processing,
            'amount' => 1000,
            'fincode_id' => 'pay_3ds_123',
            'fincode_access_id' => 'acc_3ds_456',
            'tds_trans_result' => 'C',
            'tds_challenge_url' => 'https://acs.example.com/challenge',
        ]);

        $order->update(['payment_id' => $payment->id]);

        // チャレンジ完了後: GET /secure2 で認証結果取得 + PUT /payments/{id}/secure で売上確定
        Http::fake([
            'api.test.fincode.jp/v1/secure2/acc_3ds_456*' => Http::response([
                'id' => 'pay_3ds_123',
                'access_id' => 'acc_3ds_456',
                'tds2_trans_result' => 'Y',
                'status' => 'AUTHENTICATED',
            ], 200),
            'api.test.fincode.jp/v1/payments/pay_3ds_123/secure' => Http::response([
                'id' => 'pay_3ds_123',
                'access_id' => 'acc_3ds_456',
                'status' => 'CAPTURED',
                'amount' => 1000,
            ], 200),
        ]);

        $response = $this->actingAs($this->customer)
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/3ds-callback', [
                'payment_id' => $payment->id,
                'param' => 'challenge_callback_param',
                'event' => 'AuthResultReady',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'order' => [
                        'id',
                        'order_code',
                    ],
                ],
            ]);

        // 決済が完了していることを確認
        $payment->refresh();
        $this->assertEquals(PaymentStatus::Completed, $payment->status);
        $this->assertEquals('Y', $payment->tds_trans_result);
    }

    public function test_3ds_callback_completes_payment_from_pending_status(): void
    {
        // 注文とPaymentを作成（Pending状態だが3DSチャレンジから復帰）
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::PendingPayment,
            'total_amount' => 1000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Pending,
            'amount' => 1000,
            'fincode_id' => 'pay_3ds_pending_123',
            'fincode_access_id' => 'acc_3ds_pending_456',
            'tds_trans_result' => 'C',
        ]);

        $order->update(['payment_id' => $payment->id]);

        Http::fake([
            'api.test.fincode.jp/v1/secure2/acc_3ds_pending_456*' => Http::response([
                'id' => 'pay_3ds_pending_123',
                'access_id' => 'acc_3ds_pending_456',
                'tds2_trans_result' => 'Y',
                'status' => 'AUTHENTICATED',
            ], 200),
            'api.test.fincode.jp/v1/payments/pay_3ds_pending_123/secure' => Http::response([
                'id' => 'pay_3ds_pending_123',
                'access_id' => 'acc_3ds_pending_456',
                'status' => 'CAPTURED',
                'amount' => 1000,
            ], 200),
        ]);

        $response = $this->actingAs($this->customer)
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/3ds-callback', [
                'payment_id' => $payment->id,
                'param' => 'challenge_callback_param',
                'event' => 'AuthResultReady',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'order' => [
                        'id',
                        'order_code',
                    ],
                ],
            ]);

        $payment->refresh();
        $order->refresh();
        $this->assertEquals(PaymentStatus::Completed, $payment->status);
        $this->assertEquals(OrderStatus::Paid, $order->status);
        $this->assertEquals('Y', $payment->tds_trans_result);
    }

    public function test_3ds_callback_fails_when_authentication_failed(): void
    {
        // 注文とPaymentを作成（3DSチャレンジ待ち状態）
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::PendingPayment,
            'total_amount' => 1000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Processing,
            'amount' => 1000,
            'fincode_id' => 'pay_3ds_123',
            'fincode_access_id' => 'acc_3ds_456',
            'tds_trans_result' => 'C',
        ]);

        $order->update(['payment_id' => $payment->id]);

        // チャレンジ完了後の3DS認証失敗
        Http::fake([
            'api.test.fincode.jp/v1/secure2/acc_3ds_456*' => Http::response([
                'id' => 'pay_3ds_123',
                'access_id' => 'acc_3ds_456',
                'tds2_trans_result' => 'N',
                'status' => 'AUTHENTICATION_FAILED',
            ], 200),
        ]);

        $response = $this->actingAs($this->customer)
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/3ds-callback', [
                'payment_id' => $payment->id,
                'param' => 'challenge_callback_param',
                'event' => 'AuthResultReady',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => [
                    'message' => '3DS認証に失敗しました。別のカードをお試しください。',
                ],
            ]);

        // 決済が失敗していることを確認
        $payment->refresh();
        $this->assertEquals(PaymentStatus::Failed, $payment->status);
    }

    public function test_3ds_callback_marks_payment_failed_when_access_id_is_missing(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::PendingPayment,
            'total_amount' => 1000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Pending,
            'amount' => 1000,
            'fincode_id' => 'pay_3ds_missing_access_123',
            'fincode_access_id' => null,
            'tds_trans_result' => 'C',
        ]);

        $order->update(['payment_id' => $payment->id]);

        $response = $this->actingAs($this->customer)
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/3ds-callback', [
                'payment_id' => $payment->id,
                'param' => 'challenge_callback_param',
                'event' => 'AuthResultReady',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'fincode access_idが設定されていません。');

        $payment->refresh();
        $order->refresh();
        $this->assertEquals(PaymentStatus::Failed, $payment->status);
        $this->assertEquals(OrderStatus::PaymentFailed, $order->status);
    }

    public function test_3ds_callback_marks_payment_failed_when_fincode_id_is_missing(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::PendingPayment,
            'total_amount' => 1000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Processing,
            'amount' => 1000,
            'fincode_id' => null,
            'fincode_access_id' => 'acc_3ds_missing_payment_id_456',
            'tds_trans_result' => 'C',
        ]);

        $order->update(['payment_id' => $payment->id]);

        Http::fake([
            'api.test.fincode.jp/v1/secure2/acc_3ds_missing_payment_id_456*' => Http::response([
                'id' => 'pay_3ds_missing_payment_id_123',
                'access_id' => 'acc_3ds_missing_payment_id_456',
                'tds2_trans_result' => 'Y',
                'status' => 'AUTHENTICATED',
            ], 200),
        ]);

        $response = $this->actingAs($this->customer)
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/3ds-callback', [
                'payment_id' => $payment->id,
                'param' => 'challenge_callback_param',
                'event' => 'AuthResultReady',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'fincode決済IDが設定されていません。');

        Http::assertSentCount(1);

        $payment->refresh();
        $order->refresh();
        $this->assertEquals(PaymentStatus::Failed, $payment->status);
        $this->assertEquals(OrderStatus::PaymentFailed, $order->status);
    }

    public function test_unauthorized_user_cannot_access_other_users_payment(): void
    {
        $otherCustomer = User::factory()->create([
            'role' => UserRole::Customer,
        ]);

        $order = Order::factory()->create([
            'user_id' => $otherCustomer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::PendingPayment,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/finalize', [
                'payment_id' => $payment->id,
                'token' => 'test_token',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.payment_id.0', '指定された決済は無効です。');
    }
}
