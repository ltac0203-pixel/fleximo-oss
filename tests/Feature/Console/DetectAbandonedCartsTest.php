<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Events\CartAbandoned;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\MenuItem;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DetectAbandonedCartsTest extends TestCase
{
    use RefreshDatabase;

    private function createCartWithItems(Tenant $tenant, ?Carbon $updatedAt = null): Cart
    {
        $user = User::factory()->customer()->create();
        $cart = Cart::factory()->forTenant($tenant)->forUser($user)->create();

        $menuItem = MenuItem::factory()->create(['tenant_id' => $tenant->id]);
        CartItem::factory()->forCart($cart)->forMenuItem($menuItem)->create();

        // Eloquent のタイムスタンプ自動更新を回避するため DB で直接更新
        if ($updatedAt !== null) {
            DB::table('carts')->where('id', $cart->id)->update(['updated_at' => $updatedAt]);
        }

        return $cart->fresh();
    }

    #[Test]
    public function it_dispatches_cart_abandoned_for_carts_exceeding_threshold(): void
    {
        Event::fake([CartAbandoned::class]);
        Config::set('cart.abandoned_threshold_minutes', 30);

        $tenant = Tenant::factory()->create();
        $abandonedCart = $this->createCartWithItems($tenant, Carbon::now()->subMinutes(45));

        $this->artisan('carts:detect-abandoned')
            ->assertSuccessful();

        Event::assertDispatched(CartAbandoned::class, function (CartAbandoned $event) use ($abandonedCart) {
            return $event->cart->id === $abandonedCart->id;
        });
    }

    #[Test]
    public function it_does_not_dispatch_event_for_recently_updated_carts(): void
    {
        Event::fake([CartAbandoned::class]);
        Config::set('cart.abandoned_threshold_minutes', 30);

        $tenant = Tenant::factory()->create();
        $this->createCartWithItems($tenant, Carbon::now()->subMinutes(10));

        $this->artisan('carts:detect-abandoned')
            ->assertSuccessful();

        Event::assertNotDispatched(CartAbandoned::class);
    }

    #[Test]
    public function it_does_not_dispatch_event_for_empty_carts(): void
    {
        Event::fake([CartAbandoned::class]);
        Config::set('cart.abandoned_threshold_minutes', 30);

        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();

        // アイテムなしのカート
        $cart = Cart::factory()->forTenant($tenant)->forUser($user)->create();
        $cart->update(['updated_at' => Carbon::now()->subMinutes(45)]);

        $this->artisan('carts:detect-abandoned')
            ->assertSuccessful();

        Event::assertNotDispatched(CartAbandoned::class);
    }

    #[Test]
    public function it_outputs_zero_message_when_no_abandoned_carts(): void
    {
        Event::fake([CartAbandoned::class]);
        Config::set('cart.abandoned_threshold_minutes', 30);

        $this->artisan('carts:detect-abandoned')
            ->expectsOutput('放棄カートはありませんでした。')
            ->assertSuccessful();
    }

    #[Test]
    public function it_does_not_dispatch_event_for_yesterday_carts(): void
    {
        Event::fake([CartAbandoned::class]);
        Config::set('cart.abandoned_threshold_minutes', 30);

        $tenant = Tenant::factory()->create();
        // 前日のカートは当日分のみの検出対象外
        $this->createCartWithItems($tenant, Carbon::yesterday()->subHours(2));

        $this->artisan('carts:detect-abandoned')
            ->assertSuccessful();

        Event::assertNotDispatched(CartAbandoned::class);
    }

    #[Test]
    public function it_dispatches_event_for_each_abandoned_cart(): void
    {
        Event::fake([CartAbandoned::class]);
        Config::set('cart.abandoned_threshold_minutes', 30);

        $tenant = Tenant::factory()->create();
        $this->createCartWithItems($tenant, Carbon::now()->subMinutes(45));
        $this->createCartWithItems($tenant, Carbon::now()->subMinutes(60));

        $this->artisan('carts:detect-abandoned')
            ->assertSuccessful();

        Event::assertDispatchedTimes(CartAbandoned::class, 2);
    }
}
