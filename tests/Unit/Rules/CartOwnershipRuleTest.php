<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Models\Cart;
use App\Models\User;
use App\Rules\CartOwnershipRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartOwnershipRuleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正常系: 自分のカートは検証を通過する
     */
    public function test_passes_validation_when_cart_belongs_to_authenticated_user(): void
    {
        $user = User::factory()->customer()->create();
        $cart = Cart::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $rule = new CartOwnershipRule;
        $failCalled = false;

        $rule->validate('cart_id', $cart->id, function () use (&$failCalled) {
            $failCalled = true;
        });

        $this->assertFalse($failCalled, '自分のカートは成功すべきです');
    }

    /**
     * 異常系: 他人のカートは検証を失敗する
     */
    public function test_fails_validation_when_cart_belongs_to_different_user(): void
    {
        $owner = User::factory()->customer()->create();
        $actor = User::factory()->customer()->create();
        $cart = Cart::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($actor);

        $rule = new CartOwnershipRule;
        $failCalled = false;
        $failMessage = null;

        $rule->validate('cart_id', $cart->id, function (string $message) use (&$failCalled, &$failMessage) {
            $failCalled = true;
            $failMessage = $message;
        });

        $this->assertTrue($failCalled, '他人のカートは失敗すべきです');
        $this->assertEquals('このカートにアクセスする権限がありません。', $failMessage);
    }

    /**
     * 異常系: 未認証ユーザーは検証を失敗する
     */
    public function test_fails_validation_when_user_is_unauthenticated(): void
    {
        $owner = User::factory()->customer()->create();
        $cart = Cart::factory()->create(['user_id' => $owner->id]);

        $rule = new CartOwnershipRule;
        $failCalled = false;
        $failMessage = null;

        $rule->validate('cart_id', $cart->id, function (string $message) use (&$failCalled, &$failMessage) {
            $failCalled = true;
            $failMessage = $message;
        });

        $this->assertTrue($failCalled, '未認証ユーザーは失敗すべきです');
        $this->assertEquals('このカートにアクセスする権限がありません。', $failMessage);
    }

    /**
     * 正常系: 存在しないカートIDは素通り（existsルールで後続チェック）
     */
    public function test_passes_validation_when_cart_does_not_exist(): void
    {
        $user = User::factory()->customer()->create();
        $this->actingAs($user);

        $rule = new CartOwnershipRule;
        $failCalled = false;

        $rule->validate('cart_id', 99999, function () use (&$failCalled) {
            $failCalled = true;
        });

        $this->assertFalse($failCalled, '存在しないカートはこのルールでは素通りすべきです');
    }
}
