<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\CartAbandoned;
use App\Listeners\LogCartAbandoned;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogCartAbandonedTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_cart_abandoned_with_correct_context(): void
    {
        Log::spy();

        $customer = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();

        $cart = Cart::factory()
            ->forUser($customer)
            ->forTenant($tenant)
            ->create();

        CartItem::factory()->forCart($cart)->count(3)->create();

        $event = new CartAbandoned($cart);

        $listener = new LogCartAbandoned;
        $listener->handle($event);

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($cart, $customer, $tenant): bool {
                return $message === 'カートが放棄されました'
                    && ($context['cart_id'] ?? null) === $cart->id
                    && ($context['user_id'] ?? null) === $customer->id
                    && ($context['tenant_id'] ?? null) === $tenant->id
                    && ($context['item_count'] ?? null) === 3;
            });
    }
}
