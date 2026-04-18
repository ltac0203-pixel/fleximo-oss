<?php

declare(strict_types=1);

namespace Tests\Feature\ErrorHandling;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\CardRegistrationException;
use App\Exceptions\CategoryHasItemsException;
use App\Exceptions\EmptyCartException;
use App\Exceptions\InvalidOptionSelectionException;
use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\ItemNotAvailableException;
use App\Exceptions\OrderNumberGenerationException;
use App\Exceptions\OrderPausedException;
use App\Exceptions\PaymentFailedException;
use App\Exceptions\PaymentMethodNotAvailableException;
use App\Exceptions\TenantClosedException;
use App\Exceptions\UserAlreadyAssignedToTenantException;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ExceptionRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_registration_exception_renders_400(): void
    {
        $e = new CardRegistrationException('E01100101');
        $response = $e->render();

        $this->assertEquals(400, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('カード番号が正しくありません。', $data['error']['message']);
    }

    public function test_category_has_items_exception_renders_409(): void
    {
        $category = MenuCategory::factory()->create();
        $e = new CategoryHasItemsException($category);
        $response = $e->render();

        $this->assertEquals(409, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('CATEGORY_HAS_ITEMS', $data['error']);
        $this->assertEquals($category->id, $data['category_id']);
        $this->assertEquals($category->name, $data['category_name']);
    }

    public function test_empty_cart_exception_renders_422(): void
    {
        $e = new EmptyCartException;
        $response = $e->render();

        $this->assertEquals(422, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('EMPTY_CART', $data['error']);
    }

    public function test_invalid_option_selection_exception_renders_422(): void
    {
        $e = new InvalidOptionSelectionException('サイズ');
        $response = $e->render();

        $this->assertEquals(422, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('INVALID_OPTION_SELECTION', $data['error']);
        $this->assertEquals('サイズ', $data['option_group_name']);
    }

    public function test_invalid_status_transition_exception_renders_422(): void
    {
        $e = new InvalidStatusTransitionException(OrderStatus::Completed, OrderStatus::Accepted);
        $response = $e->render();

        $this->assertEquals(422, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('INVALID_STATUS_TRANSITION', $data['error']);
        $this->assertEquals('completed', $data['current_status']);
        $this->assertEquals('accepted', $data['target_status']);
    }

    public function test_item_not_available_exception_renders_422(): void
    {
        $menuItem = MenuItem::factory()->create();
        $e = new ItemNotAvailableException($menuItem);
        $response = $e->render();

        $this->assertEquals(422, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('ITEM_NOT_AVAILABLE', $data['error']);
        $this->assertEquals($menuItem->id, $data['menu_item_id']);
        $this->assertEquals($menuItem->name, $data['menu_item_name']);
    }

    public function test_order_number_generation_exception_renders_500(): void
    {
        $e = new OrderNumberGenerationException(1, Carbon::parse('2024-01-01'), 'max_retries_exceeded');
        $response = $e->render();

        $this->assertEquals(500, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('ORDER_NUMBER_GENERATION_FAILED', $data['error']);
        $this->assertEquals(1, $data['tenant_id']);
        $this->assertEquals('2024-01-01', $data['business_date']);
        $this->assertEquals('max_retries_exceeded', $data['reason']);
    }

    public function test_order_paused_exception_renders_422(): void
    {
        $e = new OrderPausedException;
        $response = $e->render();

        $this->assertEquals(422, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('ORDER_PAUSED', $data['error']);
    }

    public function test_payment_failed_exception_renders_422(): void
    {
        $e = new PaymentFailedException(fincodeErrorCode: 'E01100301');
        $response = $e->render();

        $this->assertEquals(422, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('PAYMENT_FAILED', $data['error']);
        $this->assertEquals('E01100301', $data['fincode_error_code']);
    }

    public function test_payment_method_not_available_exception_renders_422(): void
    {
        $e = new PaymentMethodNotAvailableException(PaymentMethod::PayPay);
        $response = $e->render();

        $this->assertEquals(422, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('PAYMENT_METHOD_NOT_AVAILABLE', $data['error']);
        $this->assertEquals('paypay', $data['payment_method']);
    }

    public function test_tenant_closed_exception_renders_422(): void
    {
        $e = new TenantClosedException;
        $response = $e->render();

        $this->assertEquals(422, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('TENANT_CLOSED', $data['error']);
    }

    public function test_user_already_assigned_exception_renders_409_with_log(): void
    {
        $user = User::factory()->create();

        Log::shouldReceive('warning')
            ->once()
            ->with('ユーザーが既に別テナントに所属', \Mockery::type('array'));

        $e = new UserAlreadyAssignedToTenantException($user);
        $response = $e->render();

        $this->assertEquals(409, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('USER_ALREADY_ASSIGNED', $data['error']);
    }
}
