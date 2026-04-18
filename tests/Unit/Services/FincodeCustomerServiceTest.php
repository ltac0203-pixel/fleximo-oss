<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\CardRegistrationException;
use App\Models\FincodeCard;
use App\Models\FincodeCustomer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Fincode\FincodeApiException;
use App\Services\Fincode\FincodeCardResponse;
use App\Services\Fincode\FincodeClient;
use App\Services\Fincode\FincodeCustomerResponse;
use App\Services\FincodeCustomerService;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class FincodeCustomerServiceTest extends TestCase
{
    use RefreshDatabase;

    private FincodeClient $mockClient;

    private FincodeCustomerService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = Mockery::mock(FincodeClient::class);
        $this->service = new FincodeCustomerService($this->mockClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_ensure_customer_exists_creates_new_customer(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['fincode_shop_id' => 'tenant_shop_123']);

        $customerResponse = new FincodeCustomerResponse(
            id: 'cus_new_123',
            name: $user->name,
            email: $user->email,
            errorCode: null,
            rawResponse: ['id' => 'cus_new_123'],
        );
        $this->mockClient
            ->shouldReceive('createCustomer')
            ->once()
            ->with(Mockery::on(function ($params) use ($user) {
                return $params['name'] === $user->name
                    && $params['email'] === $user->email
                    && $params['tenant_shop_id'] === 'tenant_shop_123';
            }))
            ->andReturn($customerResponse);

        $result = $this->service->ensureCustomerExists($user, $tenant);

        $this->assertInstanceOf(FincodeCustomer::class, $result);
        $this->assertEquals('cus_new_123', $result->fincode_customer_id);
        $this->assertDatabaseHas('fincode_customers', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'fincode_customer_id' => 'cus_new_123',
        ]);
    }

    public function test_ensure_customer_exists_returns_existing(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['fincode_shop_id' => 'tenant_shop_123']);

        $existing = FincodeCustomer::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'fincode_customer_id' => 'cus_existing_456',
        ]);

        // API は呼ばれない
        $this->mockClient->shouldNotReceive('createCustomer');

        $result = $this->service->ensureCustomerExists($user, $tenant);

        $this->assertEquals($existing->id, $result->id);
        $this->assertEquals('cus_existing_456', $result->fincode_customer_id);
    }

    public function test_ensure_customer_exists_is_idempotent_on_repeated_calls(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['fincode_shop_id' => 'tenant_shop_123']);

        $customerResponse = new FincodeCustomerResponse(
            id: 'cus_idempotent',
            name: $user->name,
            email: $user->email,
            errorCode: null,
            rawResponse: ['id' => 'cus_idempotent'],
        );
        // 初回のみ API が呼ばれる
        $this->mockClient
            ->shouldReceive('createCustomer')
            ->once()
            ->andReturn($customerResponse);

        // 1回目: 顧客を新規作成
        $result1 = $this->service->ensureCustomerExists($user, $tenant);
        $this->assertEquals('cus_idempotent', $result1->fincode_customer_id);

        // 2回目: 既存レコードをそのまま返す（API は呼ばれない）
        $result2 = $this->service->ensureCustomerExists($user, $tenant);
        $this->assertEquals($result1->id, $result2->id);
    }

    public function test_ensure_customer_exists_throws_on_api_failure(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['fincode_shop_id' => 'tenant_shop_123']);

        $errorResponse = new FincodeCustomerResponse(
            id: null,
            name: null,
            email: null,
            errorCode: 'E001001',
            rawResponse: ['error_code' => 'E001001'],
        );
        $this->mockClient
            ->shouldReceive('createCustomer')
            ->once()
            ->andReturn($errorResponse);

        $this->expectException(FincodeApiException::class);

        $this->service->ensureCustomerExists($user, $tenant);
    }

    public function test_register_customer_with_card_creates_new_customer_and_card(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['fincode_shop_id' => 'tenant_shop_123']);

        // 顧客登録のモック
        $customerResponse = new FincodeCustomerResponse(
            id: 'cus_123',
            name: $user->name,
            email: $user->email,
            errorCode: null,
            rawResponse: ['id' => 'cus_123'],
        );
        $this->mockClient
            ->shouldReceive('createCustomer')
            ->once()
            ->andReturn($customerResponse);

        // カード登録のモック
        $cardResponse = new FincodeCardResponse(
            id: 'card_456',
            customerId: 'cus_123',
            cardNo: '************1234',
            brand: 'VISA',
            expire: '2512',
            defaultFlag: true,
            errorCode: null,
            rawResponse: [],
        );
        $this->mockClient
            ->shouldReceive('registerCard')
            ->once()
            ->andReturn($cardResponse);

        $card = $this->service->registerCustomerWithCard(
            $user,
            $tenant,
            'tok_test_token',
            true
        );

        $this->assertInstanceOf(FincodeCard::class, $card);
        $this->assertEquals('************1234', $card->card_no_display);
        $this->assertEquals('VISA', $card->brand);
        $this->assertTrue($card->is_default);

        // DBに保存されたことを確認
        $this->assertDatabaseHas('fincode_customers', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'fincode_customer_id' => 'cus_123',
        ]);

        $this->assertDatabaseHas('fincode_cards', [
            'fincode_card_id' => 'card_456',
            'card_no_display' => '************1234',
        ]);
    }

    public function test_register_customer_with_card_uses_existing_customer(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['fincode_shop_id' => 'tenant_shop_123']);

        // 既存の顧客レコードを作成
        $existingCustomer = FincodeCustomer::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'fincode_customer_id' => 'existing_cus_123',
        ]);

        // 顧客登録は呼ばれない
        $this->mockClient
            ->shouldNotReceive('createCustomer');

        // カード登録のモック
        $cardResponse = new FincodeCardResponse(
            id: 'card_789',
            customerId: 'existing_cus_123',
            cardNo: '************5678',
            brand: 'MASTERCARD',
            expire: '2612',
            defaultFlag: true,
            errorCode: null,
            rawResponse: [],
        );
        $this->mockClient
            ->shouldReceive('registerCard')
            ->once()
            ->with('existing_cus_123', Mockery::any())
            ->andReturn($cardResponse);

        $card = $this->service->registerCustomerWithCard(
            $user,
            $tenant,
            'tok_test_token',
            true
        );

        $this->assertEquals('card_789', $card->fincode_card_id);
        $this->assertEquals($existingCustomer->id, $card->fincode_customer_id);
    }

    public function test_get_cards_returns_empty_collection_when_no_customer(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $cards = $this->service->getCards($user, $tenant);
        $queries = DB::getQueryLog();
        $selectQueries = array_filter($queries, static fn (array $query): bool => str_starts_with(strtolower($query['query']), 'select'));

        $this->assertTrue($cards->isEmpty());
        $this->assertCount(1, $selectQueries);
    }

    public function test_get_cards_returns_cards_for_customer(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $customer = FincodeCustomer::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'fincode_customer_id' => 'cus_123',
        ]);

        FincodeCard::create([
            'fincode_customer_id' => $customer->id,
            'fincode_card_id' => 'card_1',
            'card_no_display' => '****1111',
            'brand' => 'VISA',
            'expire' => '2512',
            'is_default' => true,
        ]);

        FincodeCard::create([
            'fincode_customer_id' => $customer->id,
            'fincode_card_id' => 'card_2',
            'card_no_display' => '****2222',
            'brand' => 'MASTERCARD',
            'expire' => '2612',
            'is_default' => false,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $cards = $this->service->getCards($user, $tenant);
        $queries = DB::getQueryLog();
        $selectQueries = array_filter($queries, static fn (array $query): bool => str_starts_with(strtolower($query['query']), 'select'));

        $this->assertCount(2, $cards);
        // デフォルトカードが先に来る
        $this->assertTrue($cards->first()->is_default);
        $this->assertCount(1, $selectQueries);
    }

    public function test_delete_card_removes_card_from_fincode_and_db(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['fincode_shop_id' => 'tenant_shop_123']);

        $customer = FincodeCustomer::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'fincode_customer_id' => 'cus_123',
        ]);

        $card = FincodeCard::create([
            'fincode_customer_id' => $customer->id,
            'fincode_card_id' => 'card_to_delete',
            'card_no_display' => '****1234',
            'brand' => 'VISA',
            'expire' => '2512',
            'is_default' => true,
        ]);

        $this->mockClient
            ->shouldReceive('deleteCard')
            ->once()
            ->with('cus_123', 'card_to_delete', 'tenant_shop_123')
            ->andReturn(true);

        $result = $this->service->deleteCard($user, $tenant, $card->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('fincode_cards', ['id' => $card->id]);
    }

    public function test_delete_card_returns_false_when_card_not_found(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $customer = FincodeCustomer::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'fincode_customer_id' => 'cus_123',
        ]);

        $result = $this->service->deleteCard($user, $tenant, 99999);

        $this->assertFalse($result);
    }

    public function test_delete_card_returns_false_when_no_customer(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $result = $this->service->deleteCard($user, $tenant, 1);

        $this->assertFalse($result);
    }

    public function test_throws_card_registration_exception_with_token_not_consumed_when_customer_creation_fails(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['fincode_shop_id' => 'tenant_shop_123']);

        $errorResponse = new FincodeCustomerResponse(
            id: null,
            name: null,
            email: null,
            errorCode: 'E001001',
            rawResponse: ['error_code' => 'E001001'],
        );

        $this->mockClient
            ->shouldReceive('createCustomer')
            ->once()
            ->andReturn($errorResponse);

        try {
            $this->service->registerCustomerWithCard(
                $user,
                $tenant,
                'tok_test_token',
                true
            );
            $this->fail('CardRegistrationException should have been thrown');
        } catch (CardRegistrationException $e) {
            $this->assertEquals('顧客登録に失敗しました。', $e->getMessage());
            $this->assertFalse($e->tokenConsumed);
        }
    }

    public function test_throws_card_registration_exception_with_token_consumed_when_card_api_returns_error(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['fincode_shop_id' => 'tenant_shop_123']);

        $customerResponse = new FincodeCustomerResponse(
            id: 'cus_123',
            name: $user->name,
            email: $user->email,
            errorCode: null,
            rawResponse: ['id' => 'cus_123'],
        );
        $this->mockClient
            ->shouldReceive('createCustomer')
            ->once()
            ->andReturn($customerResponse);

        $cardErrorResponse = new FincodeCardResponse(
            id: null,
            customerId: null,
            cardNo: null,
            brand: null,
            expire: null,
            defaultFlag: null,
            errorCode: 'E01100101',
            rawResponse: ['error_code' => 'E01100101'],
        );
        $this->mockClient
            ->shouldReceive('registerCard')
            ->once()
            ->andReturn($cardErrorResponse);

        try {
            $this->service->registerCustomerWithCard(
                $user,
                $tenant,
                'tok_test_token',
                true
            );
            $this->fail('CardRegistrationException should have been thrown');
        } catch (CardRegistrationException $e) {
            $this->assertEquals('カード登録に失敗しました。', $e->getMessage());
            $this->assertTrue($e->tokenConsumed);
        }
    }

    public function test_throws_card_registration_exception_with_token_consumed_when_card_api_throws(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['fincode_shop_id' => 'tenant_shop_123']);

        $customerResponse = new FincodeCustomerResponse(
            id: 'cus_123',
            name: $user->name,
            email: $user->email,
            errorCode: null,
            rawResponse: ['id' => 'cus_123'],
        );
        $this->mockClient
            ->shouldReceive('createCustomer')
            ->once()
            ->andReturn($customerResponse);

        $this->mockClient
            ->shouldReceive('registerCard')
            ->once()
            ->andThrow(new \RuntimeException('Connection timeout'));

        try {
            $this->service->registerCustomerWithCard(
                $user,
                $tenant,
                'tok_test_token',
                true
            );
            $this->fail('CardRegistrationException should have been thrown');
        } catch (CardRegistrationException $e) {
            $this->assertEquals('カード登録に失敗しました。', $e->getMessage());
            $this->assertTrue($e->tokenConsumed);
        }
    }

    public function test_logs_error_when_customer_creation_fails(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['fincode_shop_id' => 'tenant_shop_123']);

        $errorResponse = new FincodeCustomerResponse(
            id: null,
            name: null,
            email: null,
            errorCode: 'E001001',
            rawResponse: ['error_code' => 'E001001'],
        );

        $this->mockClient
            ->shouldReceive('createCustomer')
            ->once()
            ->andReturn($errorResponse);

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to create fincode customer', Mockery::on(function ($context) use ($user, $tenant) {
                return $context['user_id'] === $user->id
                    && $context['tenant_id'] === $tenant->id
                    && $context['error_code'] === 'E001001';
            }));

        try {
            $this->service->registerCustomerWithCard(
                $user,
                $tenant,
                'tok_test_token',
                true
            );
            $this->fail('CardRegistrationException should have been thrown');
        } catch (CardRegistrationException $e) {
            // 例外は期待される
            $this->assertTrue(true);
        }
    }

    public function test_logs_error_when_card_registration_fails(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['fincode_shop_id' => 'tenant_shop_123']);

        $customerResponse = new FincodeCustomerResponse(
            id: 'cus_123',
            name: $user->name,
            email: $user->email,
            errorCode: null,
            rawResponse: ['id' => 'cus_123'],
        );
        $this->mockClient
            ->shouldReceive('createCustomer')
            ->once()
            ->andReturn($customerResponse);

        $cardErrorResponse = new FincodeCardResponse(
            id: null,
            customerId: null,
            cardNo: null,
            brand: null,
            expire: null,
            defaultFlag: null,
            errorCode: 'E01100101',
            rawResponse: ['error_code' => 'E01100101'],
        );
        $this->mockClient
            ->shouldReceive('registerCard')
            ->once()
            ->andReturn($cardErrorResponse);

        Log::shouldReceive('info')
            ->zeroOrMoreTimes();

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to register card', Mockery::on(function ($context) use ($user, $tenant) {
                return $context['user_id'] === $user->id
                    && $context['tenant_id'] === $tenant->id
                    && $context['error_code'] === 'E01100101';
            }));

        try {
            $this->service->registerCustomerWithCard(
                $user,
                $tenant,
                'tok_test_token',
                true
            );
            $this->fail('CardRegistrationException should have been thrown');
        } catch (CardRegistrationException $e) {
            // 例外は期待される
            $this->assertTrue(true);
        }
    }

    public function test_throws_card_registration_exception_when_card_deletion_fails(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['fincode_shop_id' => 'tenant_shop_123']);

        $customer = FincodeCustomer::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'fincode_customer_id' => 'cus_123',
        ]);

        $card = FincodeCard::create([
            'fincode_customer_id' => $customer->id,
            'fincode_card_id' => 'card_to_delete',
            'card_no_display' => '****1234',
            'brand' => 'VISA',
            'expire' => '2512',
            'is_default' => true,
        ]);

        $this->mockClient
            ->shouldReceive('deleteCard')
            ->once()
            ->andThrow(new FincodeApiException('E99999', [], 'カード削除に失敗しました'));

        $this->expectException(CardRegistrationException::class);

        $this->service->deleteCard($user, $tenant, $card->id);
    }

    public function test_ensure_customer_lock_acquired_but_already_created_returns_existing(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['fincode_shop_id' => 'tenant_shop_123']);

        // ロック取得中に先行プロセスがレコードを作成するシナリオをシミュレート
        $mockLock = Mockery::mock(Lock::class);
        $mockLock->shouldReceive('block')
            ->once()
            ->with(5)
            ->andReturnUsing(function () use ($user, $tenant) {
                // ロック待機中に別プロセスが作成完了
                FincodeCustomer::create([
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                    'fincode_customer_id' => 'cus_created_by_other_process',
                ]);
            });
        $mockLock->shouldReceive('release')->once();

        Cache::shouldReceive('lock')
            ->once()
            ->with("fincode_customer:{$user->id}:{$tenant->id}", 10)
            ->andReturn($mockLock);

        // API は呼ばれない（ロック後の再チェックで既存レコードが見つかるため）
        $this->mockClient->shouldNotReceive('createCustomer');

        $result = $this->service->ensureCustomerExists($user, $tenant);

        $this->assertEquals('cus_created_by_other_process', $result->fincode_customer_id);
    }

    public function test_ensure_customer_lock_timeout_recovers_with_existing_record(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['fincode_shop_id' => 'tenant_shop_123']);

        // ロックタイムアウト時に他プロセスが既に作成済み → DBにレコードがある
        $mockLock = Mockery::mock(Lock::class);
        $mockLock->shouldReceive('block')
            ->once()
            ->with(5)
            ->andReturnUsing(function () use ($user, $tenant) {
                // タイムアウト前に別プロセスが作成完了
                FincodeCustomer::create([
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                    'fincode_customer_id' => 'cus_timeout_recovery',
                ]);
                throw new LockTimeoutException;
            });

        Cache::shouldReceive('lock')
            ->once()
            ->with("fincode_customer:{$user->id}:{$tenant->id}", 10)
            ->andReturn($mockLock);

        // API は呼ばれない
        $this->mockClient->shouldNotReceive('createCustomer');

        $result = $this->service->ensureCustomerExists($user, $tenant);

        $this->assertEquals('cus_timeout_recovery', $result->fincode_customer_id);
    }

    public function test_ensure_customer_lock_timeout_throws_when_no_record(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['fincode_shop_id' => 'tenant_shop_123']);

        // ロックタイムアウト → DBにもレコードなし → 例外
        $mockLock = Mockery::mock(Lock::class);
        $mockLock->shouldReceive('block')
            ->once()
            ->with(5)
            ->andThrow(new LockTimeoutException);

        Cache::shouldReceive('lock')
            ->once()
            ->with("fincode_customer:{$user->id}:{$tenant->id}", 10)
            ->andReturn($mockLock);

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to acquire lock for fincode customer creation', Mockery::on(function ($context) use ($user, $tenant) {
                return $context['user_id'] === $user->id
                    && $context['tenant_id'] === $tenant->id;
            }));

        // API は呼ばれない
        $this->mockClient->shouldNotReceive('createCustomer');

        $this->expectException(FincodeApiException::class);
        $this->expectExceptionMessage('顧客登録が混み合っています。しばらくしてから再度お試しください。');

        $this->service->ensureCustomerExists($user, $tenant);
    }

    public function test_logs_error_when_card_deletion_fails(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['fincode_shop_id' => 'tenant_shop_123']);

        $customer = FincodeCustomer::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'fincode_customer_id' => 'cus_123',
        ]);

        $card = FincodeCard::create([
            'fincode_customer_id' => $customer->id,
            'fincode_card_id' => 'card_to_delete',
            'card_no_display' => '****1234',
            'brand' => 'VISA',
            'expire' => '2512',
            'is_default' => true,
        ]);

        $this->mockClient
            ->shouldReceive('deleteCard')
            ->once()
            ->andThrow(new FincodeApiException('E99999', [], 'カード削除に失敗しました'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to delete card from fincode', Mockery::on(function ($context) use ($user, $tenant, $card) {
                return $context['user_id'] === $user->id
                    && $context['tenant_id'] === $tenant->id
                    && $context['card_id'] === $card->id
                    && $context['error_code'] === 'E99999';
            }));

        try {
            $this->service->deleteCard($user, $tenant, $card->id);
            $this->fail('CardRegistrationException should have been thrown');
        } catch (CardRegistrationException $e) {
            // 例外は期待される
            $this->assertTrue(true);
        }
    }
}
