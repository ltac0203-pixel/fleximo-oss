<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Tenant;

use App\Enums\OrderStatus;
use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class KdsControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    private User $staff;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        $this->admin = User::factory()->create([
            'role' => UserRole::TenantAdmin,
        ]);
        TenantUser::factory()->create([
            'user_id' => $this->admin->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Admin,
        ]);

        $this->staff = User::factory()->create([
            'role' => UserRole::TenantStaff,
        ]);
        TenantUser::factory()->create([
            'user_id' => $this->staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $this->customer = User::factory()->create([
            'role' => UserRole::Customer,
        ]);
    }

    public function test_staff_can_list_kds_orders(): void
    {
        Order::factory()
            ->forTenant($this->tenant)
            ->accepted()
            ->count(3)
            ->create();

        $response = $this->actingAs($this->staff)
            ->getJson('/api/tenant/kds/orders');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'order_code',
                        'status',
                        'status_label',
                        'item_count',
                        'elapsed_seconds',
                        'elapsed_display',
                        'is_warning',
                        'accepted_at',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'server_time',
                ],
            ]);
    }

    public function test_admin_can_list_kds_orders(): void
    {
        Order::factory()
            ->forTenant($this->tenant)
            ->accepted()
            ->count(2)
            ->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/tenant/kds/orders');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_by_status(): void
    {
        Order::factory()->forTenant($this->tenant)->accepted()->create();
        Order::factory()->forTenant($this->tenant)->inProgress()->create();
        Order::factory()->forTenant($this->tenant)->ready()->create();

        $response = $this->actingAs($this->staff)
            ->getJson('/api/tenant/kds/orders?statuses[]=in_progress');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'in_progress');
    }

    public function test_can_filter_by_multiple_statuses(): void
    {
        Order::factory()->forTenant($this->tenant)->accepted()->create();
        Order::factory()->forTenant($this->tenant)->inProgress()->create();
        Order::factory()->forTenant($this->tenant)->ready()->create();
        Order::factory()->forTenant($this->tenant)->completed()->create();

        $response = $this->actingAs($this->staff)
            ->getJson('/api/tenant/kds/orders?statuses[]=accepted&statuses[]=in_progress');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_by_business_date(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');

        Order::factory()
            ->forTenant($this->tenant)
            ->accepted()
            ->forBusinessDate($today)
            ->count(2)
            ->create();

        Order::factory()
            ->forTenant($this->tenant)
            ->accepted()
            ->forBusinessDate($yesterday)
            ->create();

        $response = $this->actingAs($this->staff)
            ->getJson("/api/tenant/kds/orders?business_date={$today}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_get_order_detail(): void
    {
        $order = Order::factory()
            ->forTenant($this->tenant)
            ->accepted()
            ->create();

        OrderItem::factory()
            ->for($order)
            ->count(2)
            ->create();

        $response = $this->actingAs($this->staff)
            ->getJson("/api/tenant/kds/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_code',
                    'status',
                    'status_label',
                    'items' => [
                        '*' => ['id', 'name', 'quantity'],
                    ],
                    'elapsed_seconds',
                    'elapsed_display',
                    'is_warning',
                ],
            ]);
    }

    public function test_can_transition_accepted_to_in_progress(): void
    {
        Event::fake([OrderStatusChanged::class]);

        $order = Order::factory()
            ->forTenant($this->tenant)
            ->accepted()
            ->create();

        $response = $this->actingAs($this->staff)
            ->patchJson("/api/tenant/kds/orders/{$order->id}/status", [
                'status' => 'in_progress',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('message', '注文を「調理中」に更新しました。');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'in_progress',
        ]);

        Event::assertDispatched(OrderStatusChanged::class, function ($event) use ($order) {
            return $event->order->id === $order->id
                && $event->previousStatus === OrderStatus::Accepted
                && $event->newStatus === OrderStatus::InProgress;
        });
    }

    public function test_can_transition_in_progress_to_ready(): void
    {
        Event::fake([OrderStatusChanged::class]);

        $order = Order::factory()
            ->forTenant($this->tenant)
            ->inProgress()
            ->create();

        $response = $this->actingAs($this->staff)
            ->patchJson("/api/tenant/kds/orders/{$order->id}/status", [
                'status' => 'ready',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'ready');

        Event::assertDispatched(OrderStatusChanged::class);
    }

    public function test_can_transition_ready_to_completed(): void
    {
        Event::fake([OrderStatusChanged::class]);

        $order = Order::factory()
            ->forTenant($this->tenant)
            ->ready()
            ->create();

        $response = $this->actingAs($this->staff)
            ->patchJson("/api/tenant/kds/orders/{$order->id}/status", [
                'status' => 'completed',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');

        Event::assertDispatched(OrderStatusChanged::class);
    }

    public function test_elapsed_time_is_calculated(): void
    {
        $order = Order::factory()
            ->forTenant($this->tenant)
            ->create([
                'status' => OrderStatus::Accepted,
                'accepted_at' => Carbon::now()->subMinutes(5),
            ]);

        $response = $this->actingAs($this->staff)
            ->getJson("/api/tenant/kds/orders/{$order->id}");

        $response->assertStatus(200);

        $elapsedSeconds = $response->json('data.elapsed_seconds');

        $this->assertGreaterThanOrEqual(299, $elapsedSeconds);
        $this->assertLessThanOrEqual(310, $elapsedSeconds);
    }

    public function test_warning_flag_is_set_correctly(): void
    {

        $warningOrder = Order::factory()
            ->forTenant($this->tenant)
            ->create([
                'status' => OrderStatus::Accepted,
                'accepted_at' => Carbon::now()->subMinutes(16),
            ]);

        $normalOrder = Order::factory()
            ->forTenant($this->tenant)
            ->create([
                'status' => OrderStatus::Accepted,
                'accepted_at' => Carbon::now()->subMinutes(5),
            ]);

        $response1 = $this->actingAs($this->staff)
            ->getJson("/api/tenant/kds/orders/{$warningOrder->id}");
        $response1->assertJsonPath('data.is_warning', true);

        $response2 = $this->actingAs($this->staff)
            ->getJson("/api/tenant/kds/orders/{$normalOrder->id}");
        $response2->assertJsonPath('data.is_warning', false);
    }

    public function test_unauthenticated_user_cannot_access(): void
    {
        $response = $this->getJson('/api/tenant/kds/orders');

        $response->assertStatus(401);
    }

    public function test_customer_cannot_access(): void
    {
        $response = $this->actingAs($this->customer)
            ->getJson('/api/tenant/kds/orders');

        $response->assertStatus(403);
    }

    public function test_cannot_access_other_tenant_order(): void
    {
        $otherTenant = Tenant::factory()->create();
        $order = Order::factory()
            ->forTenant($otherTenant)
            ->accepted()
            ->create();

        $response = $this->actingAs($this->staff)
            ->getJson("/api/tenant/kds/orders/{$order->id}");

        $response->assertStatus(404);
    }

    public function test_invalid_status_transition_returns_error(): void
    {
        $order = Order::factory()
            ->forTenant($this->tenant)
            ->accepted()
            ->create();

        $response = $this->actingAs($this->staff)
            ->patchJson("/api/tenant/kds/orders/{$order->id}/status", [
                'status' => 'completed',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'INVALID_STATUS_TRANSITION');
    }

    public function test_cannot_update_completed_order(): void
    {
        $order = Order::factory()
            ->forTenant($this->tenant)
            ->completed()
            ->create();

        $response = $this->actingAs($this->staff)
            ->patchJson("/api/tenant/kds/orders/{$order->id}/status", [
                'status' => 'ready',
            ]);

        $response->assertStatus(422);
    }

    public function test_validation_error_on_invalid_status_value(): void
    {
        $order = Order::factory()
            ->forTenant($this->tenant)
            ->accepted()
            ->create();

        $response = $this->actingAs($this->staff)
            ->patchJson("/api/tenant/kds/orders/{$order->id}/status", [
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_validation_error_on_missing_status(): void
    {
        $order = Order::factory()
            ->forTenant($this->tenant)
            ->accepted()
            ->create();

        $response = $this->actingAs($this->staff)
            ->patchJson("/api/tenant/kds/orders/{$order->id}/status", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_default_filter_returns_kds_visible_orders_only(): void
    {
        Order::factory()->forTenant($this->tenant)->pendingPayment()->create();
        Order::factory()->forTenant($this->tenant)->paid()->create();
        Order::factory()->forTenant($this->tenant)->accepted()->create();
        Order::factory()->forTenant($this->tenant)->inProgress()->create();
        Order::factory()->forTenant($this->tenant)->ready()->create();
        Order::factory()->forTenant($this->tenant)->completed()->create();

        $response = $this->actingAs($this->staff)
            ->getJson('/api/tenant/kds/orders');

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data');
    }

    public function test_orders_are_sorted_by_accepted_at_asc(): void
    {
        $order1 = Order::factory()
            ->forTenant($this->tenant)
            ->create([
                'status' => OrderStatus::Accepted,
                'accepted_at' => Carbon::now()->subMinutes(10),
            ]);

        $order2 = Order::factory()
            ->forTenant($this->tenant)
            ->create([
                'status' => OrderStatus::Accepted,
                'accepted_at' => Carbon::now()->subMinutes(5),
            ]);

        $order3 = Order::factory()
            ->forTenant($this->tenant)
            ->create([
                'status' => OrderStatus::Accepted,
                'accepted_at' => Carbon::now()->subMinutes(15),
            ]);

        $response = $this->actingAs($this->staff)
            ->getJson('/api/tenant/kds/orders');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals($order3->id, $data[0]['id']);
        $this->assertEquals($order1->id, $data[1]['id']);
        $this->assertEquals($order2->id, $data[2]['id']);
    }

    public function test_can_cancel_order(): void
    {
        Event::fake([OrderStatusChanged::class]);

        $order = Order::factory()
            ->forTenant($this->tenant)
            ->inProgress()
            ->create();

        $response = $this->actingAs($this->staff)
            ->patchJson("/api/tenant/kds/orders/{$order->id}/status", [
                'status' => 'cancelled',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        Event::assertDispatched(OrderStatusChanged::class);
    }

    public function test_can_filter_by_updated_since(): void
    {

        $oldOrder = Order::factory()
            ->forTenant($this->tenant)
            ->accepted()
            ->create();
        $oldOrder->updated_at = Carbon::now()->subHour();
        $oldOrder->saveQuietly();

        $newOrder = Order::factory()
            ->forTenant($this->tenant)
            ->accepted()
            ->create();

        $response = $this->actingAs($this->staff)
            ->getJson('/api/tenant/kds/orders?updated_since='.urlencode(Carbon::now()->subMinutes(30)->toIso8601String()));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $newOrder->id);
    }

    public function test_returns_all_orders_without_updated_since(): void
    {
        Order::factory()
            ->forTenant($this->tenant)
            ->accepted()
            ->count(3)
            ->create();

        $response = $this->actingAs($this->staff)
            ->getJson('/api/tenant/kds/orders');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_response_includes_server_time(): void
    {
        $response = $this->actingAs($this->staff)
            ->getJson('/api/tenant/kds/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'server_time',
                ],
            ]);

        $serverTime = $response->json('meta.server_time');
        $this->assertNotNull($serverTime);
        $this->assertInstanceOf(Carbon::class, Carbon::parse($serverTime));
    }

    public function test_response_includes_updated_at(): void
    {
        Order::factory()
            ->forTenant($this->tenant)
            ->accepted()
            ->create();

        $response = $this->actingAs($this->staff)
            ->getJson('/api/tenant/kds/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'updated_at',
                    ],
                ],
            ]);

        $updatedAt = $response->json('data.0.updated_at');
        $this->assertNotNull($updatedAt);
        $this->assertInstanceOf(Carbon::class, Carbon::parse($updatedAt));
    }

    public function test_validation_error_on_invalid_updated_since(): void
    {
        $response = $this->actingAs($this->staff)
            ->getJson('/api/tenant/kds/orders?updated_since=invalid-date');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['updated_since']);
    }

    public function test_updated_since_includes_orders_at_exact_boundary(): void
    {
        $boundaryTime = Carbon::parse('2026-02-14 12:00:05');

        $orderAtBoundary = Order::factory()
            ->forTenant($this->tenant)
            ->accepted()
            ->create();
        $orderAtBoundary->updated_at = $boundaryTime;
        $orderAtBoundary->saveQuietly();

        $orderBeforeBoundary = Order::factory()
            ->forTenant($this->tenant)
            ->accepted()
            ->create();
        $orderBeforeBoundary->updated_at = $boundaryTime->copy()->subSeconds(5);
        $orderBeforeBoundary->saveQuietly();

        // updated_since と同一秒の注文も含まれることを検証（>= の動作確認）
        $response = $this->actingAs($this->staff)
            ->getJson('/api/tenant/kds/orders?updated_since='.urlencode($boundaryTime->toIso8601String()));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $orderAtBoundary->id);
    }

    public function test_server_time_is_approximately_one_second_before_now(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-14 12:00:10'));

        $response = $this->actingAs($this->staff)
            ->getJson('/api/tenant/kds/orders');

        $response->assertStatus(200);

        $serverTime = Carbon::parse($response->json('meta.server_time'));
        $expectedTime = Carbon::parse('2026-02-14 12:00:09');

        // server_time は現在時刻の約1秒前であること
        $this->assertTrue(
            $serverTime->equalTo($expectedTime),
            "server_time should be approximately 1 second before now. Got: {$serverTime->toIso8601String()}, Expected: {$expectedTime->toIso8601String()}"
        );

        Carbon::setTestNow();
    }
}
