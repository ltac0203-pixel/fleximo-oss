<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Customer;

use App\Models\FincodeCard;
use App\Models\FincodeCustomer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Fincode\FincodeCardResponse;
use App\Services\Fincode\FincodeClient;
use App\Services\Fincode\FincodeCustomerResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CardControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->customer()->create();
        $this->tenant = Tenant::factory()->create(['fincode_shop_id' => 'shop_123']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_returns_empty_cards_for_new_customer(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/customer/tenants/{$this->tenant->id}/cards");

        $response->assertOk()
            ->assertJson([]);
    }

    public function test_index_returns_registered_cards(): void
    {
        $fincodeCustomer = FincodeCustomer::create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'fincode_customer_id' => 'cus_123',
        ]);

        FincodeCard::create([
            'fincode_customer_id' => $fincodeCustomer->id,
            'fincode_card_id' => 'card_1',
            'card_no_display' => '****1234',
            'brand' => 'VISA',
            'expire' => '2512',
            'is_default' => true,
        ]);

        FincodeCard::create([
            'fincode_customer_id' => $fincodeCustomer->id,
            'fincode_card_id' => 'card_2',
            'card_no_display' => '****5678',
            'brand' => 'MASTERCARD',
            'expire' => '2612',
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/customer/tenants/{$this->tenant->id}/cards");

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.is_default', true)
            ->assertJsonPath('0.brand', 'VISA');
    }

    public function test_store_registers_card_successfully(): void
    {
        $mockClient = Mockery::mock(FincodeClient::class);

        $customerResponse = new FincodeCustomerResponse(
            id: 'cus_new_123',
            name: $this->customer->name,
            email: $this->customer->email,
            errorCode: null,
            rawResponse: [],
        );
        $mockClient->shouldReceive('createCustomer')
            ->once()
            ->andReturn($customerResponse);

        $cardResponse = new FincodeCardResponse(
            id: 'card_new_456',
            customerId: 'cus_new_123',
            cardNo: '****9999',
            brand: 'JCB',
            expire: '2712',
            defaultFlag: true,
            errorCode: null,
            rawResponse: [],
        );
        $mockClient->shouldReceive('registerCard')
            ->once()
            ->andReturn($cardResponse);

        $this->app->instance(FincodeClient::class, $mockClient);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/customer/tenants/{$this->tenant->id}/cards", [
                'token' => 'tok_test_123',
                'is_default' => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.card_no_display', '****9999')
            ->assertJsonPath('data.brand', 'JCB')
            ->assertJsonPath('data.is_default', true)
            ->assertJsonPath('message', 'カードを登録しました。');

        $this->assertDatabaseHas('fincode_customers', [
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'fincode_customer_id' => 'cus_new_123',
        ]);

        $this->assertDatabaseHas('fincode_cards', [
            'fincode_card_id' => 'card_new_456',
        ]);
    }

    public function test_store_requires_token(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/customer/tenants/{$this->tenant->id}/cards", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['token']);
    }

    public function test_destroy_deletes_card_successfully(): void
    {
        $fincodeCustomer = FincodeCustomer::create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'fincode_customer_id' => 'cus_123',
        ]);

        $card = FincodeCard::create([
            'fincode_customer_id' => $fincodeCustomer->id,
            'fincode_card_id' => 'card_to_delete',
            'card_no_display' => '****1234',
            'brand' => 'VISA',
            'expire' => '2512',
            'is_default' => true,
        ]);

        $mockClient = Mockery::mock(FincodeClient::class);
        $mockClient->shouldReceive('deleteCard')
            ->once()
            ->with('cus_123', 'card_to_delete', 'shop_123')
            ->andReturn(true);

        $this->app->instance(FincodeClient::class, $mockClient);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->deleteJson("/api/customer/tenants/{$this->tenant->id}/cards/{$card->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'カードを削除しました。');

        $this->assertDatabaseMissing('fincode_cards', ['id' => $card->id]);
    }

    public function test_destroy_returns_404_for_non_existent_card(): void
    {
        $mockClient = Mockery::mock(FincodeClient::class);
        $this->app->instance(FincodeClient::class, $mockClient);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->deleteJson("/api/customer/tenants/{$this->tenant->id}/cards/99999");

        $response->assertNotFound();
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson("/api/customer/tenants/{$this->tenant->id}/cards");

        $response->assertUnauthorized();
    }

    public function test_requires_customer_role(): void
    {
        $tenantAdmin = User::factory()->tenantAdmin()->create();

        $response = $this->actingAs($tenantAdmin, 'sanctum')
            ->getJson("/api/customer/tenants/{$this->tenant->id}/cards");

        $response->assertForbidden();
    }

    public function test_index_does_not_show_other_users_cards(): void
    {
        $otherCustomer = User::factory()->customer()->create();

        $otherFincodeCustomer = FincodeCustomer::create([
            'user_id' => $otherCustomer->id,
            'tenant_id' => $this->tenant->id,
            'fincode_customer_id' => 'cus_other',
        ]);

        FincodeCard::create([
            'fincode_customer_id' => $otherFincodeCustomer->id,
            'fincode_card_id' => 'card_other',
            'card_no_display' => '****0000',
            'brand' => 'VISA',
            'expire' => '2512',
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/customer/tenants/{$this->tenant->id}/cards");

        $response->assertOk()
            ->assertJson([]);
    }

    public function test_destroy_cannot_delete_other_users_cards(): void
    {
        $otherCustomer = User::factory()->customer()->create();

        $otherFincodeCustomer = FincodeCustomer::create([
            'user_id' => $otherCustomer->id,
            'tenant_id' => $this->tenant->id,
            'fincode_customer_id' => 'cus_other',
        ]);

        $otherCard = FincodeCard::create([
            'fincode_customer_id' => $otherFincodeCustomer->id,
            'fincode_card_id' => 'card_other',
            'card_no_display' => '****0000',
            'brand' => 'VISA',
            'expire' => '2512',
            'is_default' => true,
        ]);

        $mockClient = Mockery::mock(FincodeClient::class);
        $this->app->instance(FincodeClient::class, $mockClient);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->deleteJson("/api/customer/tenants/{$this->tenant->id}/cards/{$otherCard->id}");

        $response->assertNotFound();

        // カードが削除されていないことを確認
        $this->assertDatabaseHas('fincode_cards', ['id' => $otherCard->id]);
    }

    public function test_index_returns_404_for_inactive_tenant(): void
    {
        $inactiveTenant = Tenant::factory()->inactive()->create([
            'fincode_shop_id' => 'shop_inactive',
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/customer/tenants/{$inactiveTenant->id}/cards");

        $response->assertNotFound()
            ->assertJson(['message' => 'テナントが見つかりません']);
    }

    public function test_store_returns_404_for_inactive_tenant(): void
    {
        $inactiveTenant = Tenant::factory()->inactive()->create([
            'fincode_shop_id' => 'shop_inactive',
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/customer/tenants/{$inactiveTenant->id}/cards", [
                'token' => 'tok_test_123',
                'is_default' => true,
            ]);

        $response->assertNotFound()
            ->assertJson(['message' => 'テナントが見つかりません']);
    }

    public function test_destroy_returns_404_for_inactive_tenant(): void
    {
        $inactiveTenant = Tenant::factory()->inactive()->create([
            'fincode_shop_id' => 'shop_inactive',
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->deleteJson("/api/customer/tenants/{$inactiveTenant->id}/cards/1");

        $response->assertNotFound()
            ->assertJson(['message' => 'テナントが見つかりません']);
    }

    public function test_store_returns_400_with_user_friendly_message_on_fincode_error(): void
    {
        $mockClient = Mockery::mock(FincodeClient::class);

        $customerResponse = new FincodeCustomerResponse(
            id: 'cus_new_123',
            name: $this->customer->name,
            email: $this->customer->email,
            errorCode: null,
            rawResponse: [],
        );
        $mockClient->shouldReceive('createCustomer')
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
        $mockClient->shouldReceive('registerCard')
            ->once()
            ->andReturn($cardErrorResponse);

        $this->app->instance(FincodeClient::class, $mockClient);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/customer/tenants/{$this->tenant->id}/cards", [
                'token' => 'tok_invalid',
                'is_default' => true,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('error.message', 'カード番号が正しくありません。');

        // fincode内部エラーコードが含まれていないことを確認
        $response->assertJsonMissing(['error' => ['code' => 'E01100101']]);
    }

    public function test_store_does_not_expose_fincode_internal_error_details(): void
    {
        $mockClient = Mockery::mock(FincodeClient::class);

        $errorResponse = new FincodeCustomerResponse(
            id: null,
            name: null,
            email: null,
            errorCode: 'E01200101',
            rawResponse: ['error_code' => 'E01200101', 'message' => 'Internal fincode error'],
        );
        $mockClient->shouldReceive('createCustomer')
            ->once()
            ->andReturn($errorResponse);

        $this->app->instance(FincodeClient::class, $mockClient);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/customer/tenants/{$this->tenant->id}/cards", [
                'token' => 'tok_test',
            ]);

        $response->assertStatus(400);

        $content = $response->json();

        // fincode内部エラーコードやメッセージが含まれていないことを確認
        $this->assertArrayNotHasKey('code', $content['error'] ?? []);
        $this->assertArrayNotHasKey('fincode_error_code', $content['error'] ?? []);
        $this->assertStringNotContainsString('Internal fincode error', $content['error']['message'] ?? '');
    }
}
