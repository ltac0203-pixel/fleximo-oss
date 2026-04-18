<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Models\Cart;
use App\Models\FincodeCard;
use App\Models\FincodeCustomer;
use App\Models\Tenant;
use App\Models\User;
use App\Rules\CardOwnershipRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CardOwnershipRuleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正常系: 正しいテナントのカードは検証を通過する
     */
    public function test_passes_validation_when_card_belongs_to_correct_tenant(): void
    {
        // Arrange: 認証済みユーザー、テナント、カート、fincode顧客、カードを作成
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $fincodeCustomer = FincodeCustomer::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'fincode_customer_id' => 'c_test_'.uniqid(),
        ]);

        $card = FincodeCard::factory()->create([
            'fincode_customer_id' => $fincodeCustomer->id,
            'fincode_card_id' => 'cs_test_'.uniqid(),
        ]);

        $this->actingAs($user);

        // Act: バリデーションを実行
        $rule = new CardOwnershipRule($cart->id);
        $failCalled = false;
        $failMessage = null;

        $rule->validate('card_id', $card->id, function (string $message) use (&$failCalled, &$failMessage) {
            $failCalled = true;
            $failMessage = $message;
        });

        // Assert: バリデーションが成功することを確認
        $this->assertFalse($failCalled, 'バリデーションが失敗すべきではありません');
    }

    /**
     * 異常系: 異なるテナントのカードは検証を失敗する
     */
    public function test_fails_validation_when_card_belongs_to_different_tenant(): void
    {
        // Arrange: 2つの異なるテナントを作成
        $user = User::factory()->customer()->create();
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // tenant1のカートを作成
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant1->id,
        ]);

        // tenant2のfincode顧客とカードを作成
        $fincodeCustomer = FincodeCustomer::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant2->id,
            'fincode_customer_id' => 'c_test_'.uniqid(),
        ]);

        $card = FincodeCard::factory()->create([
            'fincode_customer_id' => $fincodeCustomer->id,
            'fincode_card_id' => 'cs_test_'.uniqid(),
        ]);

        $this->actingAs($user);

        // Act: バリデーションを実行
        $rule = new CardOwnershipRule($cart->id);
        $failCalled = false;
        $failMessage = null;

        $rule->validate('card_id', $card->id, function (string $message) use (&$failCalled, &$failMessage) {
            $failCalled = true;
            $failMessage = $message;
        });

        // Assert: バリデーションが失敗し、適切なエラーメッセージが返されることを確認
        $this->assertTrue($failCalled, 'バリデーションが失敗すべきです');
        $this->assertEquals('このカードは当店舗で使用できません。', $failMessage);
    }

    /**
     * 異常系: 他人のカードは検証を失敗する
     */
    public function test_fails_validation_when_card_belongs_to_different_user(): void
    {
        // Arrange: 2人のユーザーを作成
        $user1 = User::factory()->customer()->create();
        $user2 = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();

        // user1のカートを作成
        $cart = Cart::factory()->create([
            'user_id' => $user1->id,
            'tenant_id' => $tenant->id,
        ]);

        // user2のfincode顧客とカードを作成
        $fincodeCustomer = FincodeCustomer::factory()->create([
            'user_id' => $user2->id,
            'tenant_id' => $tenant->id,
            'fincode_customer_id' => 'c_test_'.uniqid(),
        ]);

        $card = FincodeCard::factory()->create([
            'fincode_customer_id' => $fincodeCustomer->id,
            'fincode_card_id' => 'cs_test_'.uniqid(),
        ]);

        // user1として認証
        $this->actingAs($user1);

        // Act: バリデーションを実行
        $rule = new CardOwnershipRule($cart->id);
        $failCalled = false;
        $failMessage = null;

        $rule->validate('card_id', $card->id, function (string $message) use (&$failCalled, &$failMessage) {
            $failCalled = true;
            $failMessage = $message;
        });

        // Assert: バリデーションが失敗し、適切なエラーメッセージが返されることを確認
        $this->assertTrue($failCalled, 'バリデーションが失敗すべきです');
        $this->assertEquals('このカードにアクセスする権限がありません。', $failMessage);
    }

    /**
     * 正常系: 存在しないカードIDは素通り（existsルールで後続チェック）
     */
    public function test_passes_validation_when_card_does_not_exist(): void
    {
        // Arrange: 認証済みユーザーとカートを作成
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($user);

        // Act: 存在しないカードIDでバリデーションを実行
        $rule = new CardOwnershipRule($cart->id);
        $failCalled = false;
        $failMessage = null;

        $rule->validate('card_id', 99999, function (string $message) use (&$failCalled, &$failMessage) {
            $failCalled = true;
            $failMessage = $message;
        });

        // Assert: バリデーションが素通りすることを確認
        $this->assertFalse($failCalled, '存在しないカードはこのルールでは素通りすべきです');
    }

    /**
     * エッジケース: cartIdがnullの場合はテナント検証をスキップ
     */
    public function test_skips_tenant_check_when_cart_id_is_null(): void
    {
        // Arrange: 認証済みユーザー、テナント、fincode顧客、カードを作成
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();

        $fincodeCustomer = FincodeCustomer::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'fincode_customer_id' => 'c_test_'.uniqid(),
        ]);

        $card = FincodeCard::factory()->create([
            'fincode_customer_id' => $fincodeCustomer->id,
            'fincode_card_id' => 'cs_test_'.uniqid(),
        ]);

        $this->actingAs($user);

        // Act: cartId=nullでバリデーションを実行
        $rule = new CardOwnershipRule(null);
        $failCalled = false;
        $failMessage = null;

        $rule->validate('card_id', $card->id, function (string $message) use (&$failCalled, &$failMessage) {
            $failCalled = true;
            $failMessage = $message;
        });

        // Assert: テナント検証がスキップされ、ユーザー所有権のみがチェックされることを確認
        $this->assertFalse($failCalled, 'cartIdがnullの場合はテナント検証をスキップすべきです');
    }

    /**
     * 異常系: tenantId指定時にテナント不一致なら検証を失敗する（cartなし）
     */
    public function test_fails_validation_when_tenant_id_is_specified_and_different(): void
    {
        $user = User::factory()->customer()->create();
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $fincodeCustomer = FincodeCustomer::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant2->id,
            'fincode_customer_id' => 'c_test_'.uniqid(),
        ]);

        $card = FincodeCard::factory()->create([
            'fincode_customer_id' => $fincodeCustomer->id,
            'fincode_card_id' => 'cs_test_'.uniqid(),
        ]);

        $this->actingAs($user);

        $rule = new CardOwnershipRule(null, $tenant1->id);
        $failCalled = false;
        $failMessage = null;

        $rule->validate('card_id', $card->id, function (string $message) use (&$failCalled, &$failMessage) {
            $failCalled = true;
            $failMessage = $message;
        });

        $this->assertTrue($failCalled, 'tenantId指定時にテナント不一致は失敗すべきです');
        $this->assertEquals('このカードは当店舗で使用できません。', $failMessage);
    }

    /**
     * 正常系: tenantId指定時にテナント一致なら検証を通過する（cartなし）
     */
    public function test_passes_validation_when_tenant_id_is_specified_and_matches(): void
    {
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();

        $fincodeCustomer = FincodeCustomer::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'fincode_customer_id' => 'c_test_'.uniqid(),
        ]);

        $card = FincodeCard::factory()->create([
            'fincode_customer_id' => $fincodeCustomer->id,
            'fincode_card_id' => 'cs_test_'.uniqid(),
        ]);

        $this->actingAs($user);

        $rule = new CardOwnershipRule(null, $tenant->id);
        $failCalled = false;

        $rule->validate('card_id', $card->id, function () use (&$failCalled) {
            $failCalled = true;
        });

        $this->assertFalse($failCalled, 'tenantId指定時にテナント一致は成功すべきです');
    }

    /**
     * エッジケース: カート自体が存在しない場合は検証を通過
     */
    public function test_passes_validation_when_cart_does_not_exist(): void
    {
        // Arrange: 認証済みユーザー、テナント、fincode顧客、カードを作成
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();

        $fincodeCustomer = FincodeCustomer::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'fincode_customer_id' => 'c_test_'.uniqid(),
        ]);

        $card = FincodeCard::factory()->create([
            'fincode_customer_id' => $fincodeCustomer->id,
            'fincode_card_id' => 'cs_test_'.uniqid(),
        ]);

        $this->actingAs($user);

        // Act: 存在しないcartIdでバリデーションを実行
        $rule = new CardOwnershipRule(99999);
        $failCalled = false;
        $failMessage = null;

        $rule->validate('card_id', $card->id, function (string $message) use (&$failCalled, &$failMessage) {
            $failCalled = true;
            $failMessage = $message;
        });

        // Assert: バリデーションが通過することを確認（後続バリデーションでエラーになる）
        $this->assertFalse($failCalled, 'カートが存在しない場合は素通りすべきです');
    }

    /**
     * 異常系: カードに紐づくfincode顧客が欠損している場合は検証を失敗する
     */
    public function test_fails_validation_when_card_customer_relation_is_missing(): void
    {
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $fincodeCustomer = FincodeCustomer::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'fincode_customer_id' => 'c_test_'.uniqid(),
        ]);

        $card = FincodeCard::factory()->create([
            'fincode_customer_id' => $fincodeCustomer->id,
            'fincode_card_id' => 'cs_test_'.uniqid(),
        ]);

        $this->actingAs($user);

        $this->disableForeignKeyChecks();
        try {
            DB::table('fincode_customers')->where('id', $fincodeCustomer->id)->delete();
        } finally {
            $this->enableForeignKeyChecks();
        }

        $rule = new CardOwnershipRule($cart->id);
        $failCalled = false;
        $failMessage = null;

        $rule->validate('card_id', $card->id, function (string $message) use (&$failCalled, &$failMessage) {
            $failCalled = true;
            $failMessage = $message;
        });

        $this->assertTrue($failCalled, 'fincode顧客欠損時は失敗すべきです');
        $this->assertEquals('このカードにアクセスする権限がありません。', $failMessage);
    }

    /**
     * 正常系: 同じユーザー、同じテナントのカードは許可される（複数カードケース）
     */
    public function test_passes_validation_with_multiple_cards_for_same_user_and_tenant(): void
    {
        // Arrange: 認証済みユーザー、テナント、カート、fincode顧客を作成
        $user = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $fincodeCustomer = FincodeCustomer::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'fincode_customer_id' => 'c_test_'.uniqid(),
        ]);

        // 同じ顧客に複数のカードを登録
        $card1 = FincodeCard::factory()->create([
            'fincode_customer_id' => $fincodeCustomer->id,
            'fincode_card_id' => 'cs_test_'.uniqid(),
        ]);

        $card2 = FincodeCard::factory()->create([
            'fincode_customer_id' => $fincodeCustomer->id,
            'fincode_card_id' => 'cs_test_'.uniqid(),
        ]);

        $this->actingAs($user);

        // Act: 両方のカードでバリデーションを実行
        $rule = new CardOwnershipRule($cart->id);

        $failCalled1 = false;
        $rule->validate('card_id', $card1->id, function () use (&$failCalled1) {
            $failCalled1 = true;
        });

        $failCalled2 = false;
        $rule->validate('card_id', $card2->id, function () use (&$failCalled2) {
            $failCalled2 = true;
        });

        // Assert: どちらのカードもバリデーションを通過することを確認
        $this->assertFalse($failCalled1, 'カード1のバリデーションが成功すべきです');
        $this->assertFalse($failCalled2, 'カード2のバリデーションが成功すべきです');
    }

    private function disableForeignKeyChecks(): void
    {
        $driver = DB::getDriverName();

        match ($driver) {
            'mysql', 'mariadb' => DB::statement('SET FOREIGN_KEY_CHECKS=0'),
            'sqlite' => DB::statement('PRAGMA foreign_keys = OFF'),
            default => $this->markTestSkipped("foreign key check toggling is not supported for driver: {$driver}"),
        };
    }

    private function enableForeignKeyChecks(): void
    {
        $driver = DB::getDriverName();

        match ($driver) {
            'mysql', 'mariadb' => DB::statement('SET FOREIGN_KEY_CHECKS=1'),
            'sqlite' => DB::statement('PRAGMA foreign_keys = ON'),
            default => null,
        };
    }
}
