<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SetTenantContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->clear();
        Cache::flush();

        // テスト用エンドポイント：リクエスト中のテナントコンテキストを返す
        Route::middleware(['auth:sanctum', SubstituteBindings::class, 'tenant.context'])
            ->get('/test-api/endpoint', function () {
                return response()->json([
                    'success' => true,
                    'tenant_id' => app(TenantContext::class)->getTenantId(),
                ]);
            });

        Route::middleware(['auth:sanctum', SubstituteBindings::class, 'tenant.context'])
            ->get('/test-api/tenants/{tenant}/endpoint', function (Tenant $tenant) {
                return response()->json([
                    'success' => true,
                    'tenant_id' => app(TenantContext::class)->getTenantId(),
                    'route_tenant_id' => $tenant->id,
                ]);
            });

        Route::middleware(['auth:sanctum', SubstituteBindings::class, 'tenant.context'])
            ->post('/test-api/customer/action', function () {
                return response()->json([
                    'success' => true,
                    'tenant_id' => app(TenantContext::class)->getTenantId(),
                ]);
            });
    }

    public function test_tenant_admin_sets_tenant_context(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin()->create();
        TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/test-api/endpoint');

        // リクエスト中にテナントコンテキストが設定されることを確認
        $response->assertJson(['tenant_id' => $tenant->id]);
    }

    public function test_tenant_staff_sets_tenant_context(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantStaff()->create();
        TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/test-api/endpoint');

        // リクエスト中にテナントコンテキストが設定されることを確認
        $response->assertJson(['tenant_id' => $tenant->id]);
    }

    public function test_customer_does_not_set_tenant_context(): void
    {
        $user = User::factory()->customer()->create();

        $response = $this->actingAs($user)
            ->getJson('/test-api/endpoint');

        // 顧客はテナントコンテキストなし
        $response->assertJson(['tenant_id' => null]);
    }

    public function test_customer_sets_tenant_context_from_route_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();

        $response = $this->actingAs($user)
            ->getJson("/test-api/tenants/{$tenant->id}/endpoint");

        $response->assertOk()
            ->assertJson([
                'tenant_id' => $tenant->id,
                'route_tenant_id' => $tenant->id,
            ]);
    }

    public function test_customer_sets_tenant_context_from_request_body(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();

        $response = $this->actingAs($user)
            ->postJson('/test-api/customer/action', [
                'tenant_id' => $tenant->id,
            ]);

        $response->assertOk()
            ->assertJson(['tenant_id' => $tenant->id]);
    }

    public function test_customer_does_not_set_context_with_invalid_tenant_id(): void
    {
        $user = User::factory()->customer()->create();

        $response = $this->actingAs($user)
            ->postJson('/test-api/customer/action', [
                'tenant_id' => 'not-a-number',
            ]);

        $response->assertOk()
            ->assertJson(['tenant_id' => null]);
    }

    public function test_customer_sets_tenant_context_from_cart_id(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/test-api/customer/action', [
                'cart_id' => $cart->id,
            ]);

        $response->assertOk()
            ->assertJson(['tenant_id' => $tenant->id]);
    }

    public function test_customer_sets_tenant_context_from_payment_id(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);
        $payment = Payment::factory()->forOrder($order)->create();

        $response = $this->actingAs($user)
            ->postJson('/test-api/customer/action', [
                'payment_id' => $payment->id,
            ]);

        $response->assertOk()
            ->assertJson(['tenant_id' => $tenant->id]);
    }

    public function test_customer_payment_lookup_is_cached_after_first_request(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);
        $payment = Payment::factory()->forOrder($order)->create();

        $firstLookupQueryCount = $this->countQueriesAgainstTable(
            fn () => $this->actingAs($user)
                ->postJson('/test-api/customer/action', [
                    'payment_id' => $payment->id,
                ])
                ->assertOk()
                ->assertJson(['tenant_id' => $tenant->id]),
            'payments'
        );

        $this->assertSame(1, $firstLookupQueryCount);

        $secondLookupQueryCount = $this->countQueriesAgainstTable(
            fn () => $this->actingAs($user)
                ->postJson('/test-api/customer/action', [
                    'payment_id' => $payment->id,
                ])
                ->assertOk()
                ->assertJson(['tenant_id' => $tenant->id]),
            'payments'
        );

        $this->assertSame(0, $secondLookupQueryCount);
    }

    public function test_customer_cart_lookup_is_cached_after_first_request(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $firstLookupQueryCount = $this->countQueriesAgainstTable(
            fn () => $this->actingAs($user)
                ->postJson('/test-api/customer/action', [
                    'cart_id' => $cart->id,
                ])
                ->assertOk()
                ->assertJson(['tenant_id' => $tenant->id]),
            'carts'
        );

        $this->assertSame(1, $firstLookupQueryCount);

        $secondLookupQueryCount = $this->countQueriesAgainstTable(
            fn () => $this->actingAs($user)
                ->postJson('/test-api/customer/action', [
                    'cart_id' => $cart->id,
                ])
                ->assertOk()
                ->assertJson(['tenant_id' => $tenant->id]),
            'carts'
        );

        $this->assertSame(0, $secondLookupQueryCount);
    }

    public function test_non_numeric_cart_and_payment_ids_do_not_trigger_lookup_queries(): void
    {
        $user = User::factory()->customer()->create();

        $cartLookupQueryCount = $this->countQueriesAgainstTable(
            fn () => $this->actingAs($user)
                ->postJson('/test-api/customer/action', [
                    'cart_id' => 'invalid',
                ])
                ->assertOk()
                ->assertJson(['tenant_id' => null]),
            'carts'
        );

        $paymentLookupQueryCount = $this->countQueriesAgainstTable(
            fn () => $this->actingAs($user)
                ->postJson('/test-api/customer/action', [
                    'payment_id' => 'invalid',
                ])
                ->assertOk()
                ->assertJson(['tenant_id' => null]),
            'payments'
        );

        $this->assertSame(0, $cartLookupQueryCount);
        $this->assertSame(0, $paymentLookupQueryCount);
    }

    public function test_guest_user_does_not_set_tenant_context(): void
    {
        // 未認証のためアクセス拒否
        $this->getJson('/test-api/endpoint')
            ->assertUnauthorized();
    }

    private function countQueriesAgainstTable(callable $callback, string $tableName): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $callback();

            return collect(DB::getQueryLog())
                ->pluck('query')
                ->filter(fn (string $query): bool => preg_match(
                    '/\bfrom\s+["`]?'.preg_quote($tableName, '/').'["`]?\b/i',
                    $query
                ) === 1)
                ->count();
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }
}
