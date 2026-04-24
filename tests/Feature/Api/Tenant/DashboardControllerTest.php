<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Tenant;

use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
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

    public function test_admin_can_get_sales_data(): void
    {
        $startDate = Carbon::today()->subDays(7)->format('Y-m-d');
        $endDate = Carbon::today()->format('Y-m-d');

        $response = $this->actingAs($this->admin)
            ->getJson("/api/tenant/dashboard/sales?period=daily&start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['date', 'sales', 'orders'],
                ],
            ]);
    }

    public function test_staff_can_access_sales_data(): void
    {
        $startDate = Carbon::today()->subDays(7)->format('Y-m-d');
        $endDate = Carbon::today()->format('Y-m-d');

        $response = $this->actingAs($this->staff)
            ->getJson("/api/tenant/dashboard/sales?period=daily&start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['date', 'sales', 'orders'],
                ],
            ]);
    }

    public function test_can_get_weekly_sales_data(): void
    {
        $startDate = Carbon::today()->subWeeks(4)->format('Y-m-d');
        $endDate = Carbon::today()->format('Y-m-d');

        $response = $this->actingAs($this->admin)
            ->getJson("/api/tenant/dashboard/sales?period=weekly&start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200);
    }

    public function test_can_get_monthly_sales_data(): void
    {
        $startDate = Carbon::today()->subMonths(3)->format('Y-m-d');
        $endDate = Carbon::today()->format('Y-m-d');

        $response = $this->actingAs($this->admin)
            ->getJson("/api/tenant/dashboard/sales?period=monthly&start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200);
    }

    public function test_sales_validation_error_on_missing_parameters(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/tenant/dashboard/sales');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['period', 'start_date', 'end_date']);
    }

    public function test_sales_validation_error_on_invalid_period(): void
    {
        $startDate = Carbon::today()->subDays(7)->format('Y-m-d');
        $endDate = Carbon::today()->format('Y-m-d');

        $response = $this->actingAs($this->admin)
            ->getJson("/api/tenant/dashboard/sales?period=invalid&start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['period']);
    }

    public function test_sales_validation_error_on_end_date_before_start_date(): void
    {
        $startDate = Carbon::today()->format('Y-m-d');
        $endDate = Carbon::today()->subDays(7)->format('Y-m-d');

        $response = $this->actingAs($this->admin)
            ->getJson("/api/tenant/dashboard/sales?period=daily&start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_admin_can_export_sales_csv(): void
    {
        $targetDate = Carbon::today()->subDay()->format('Y-m-d');
        $order = Order::factory()
            ->forTenant($this->tenant)
            ->completed()
            ->forBusinessDate($targetDate)
            ->withOrderCode('A101')
            ->create(['total_amount' => 1200]);

        $payment = Payment::factory()
            ->forOrder($order)
            ->completed()
            ->card()
            ->create(['amount' => 1200]);
        $order->update(['payment_id' => $payment->id]);

        $response = $this->actingAs($this->admin)->get(
            "/api/tenant/dashboard/export/csv?start_date={$targetDate}&end_date={$targetDate}"
        );

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('summary', $content);
        $this->assertStringContainsString('daily_breakdown', $content);
        $this->assertStringContainsString('payment_methods', $content);
        $this->assertStringContainsString('order_details', $content);
        $this->assertStringContainsString('A101', $content);
        $this->assertStringContainsString('note', $content);
    }

    public function test_staff_can_export_sales_csv(): void
    {
        $targetDate = Carbon::today()->subDay()->format('Y-m-d');

        $response = $this->actingAs($this->staff)->get(
            "/api/tenant/dashboard/export/csv?start_date={$targetDate}&end_date={$targetDate}"
        );

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_export_csv_validation_error_on_invalid_date_range(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/tenant/dashboard/export/csv?start_date=2025-01-01&end_date=2026-01-03');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_export_csv_validation_error_on_missing_parameters(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/tenant/dashboard/export/csv');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    public function test_admin_can_get_top_items(): void
    {
        $today = Carbon::today()->format('Y-m-d');

        $item1 = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'コーヒー',
            'price' => 500,
        ]);
        $item2 = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'ラテ',
            'price' => 500,
        ]);

        $order = Order::factory()
            ->forTenant($this->tenant)
            ->completed()
            ->forBusinessDate($today)
            ->create();

        OrderItem::factory()->for($order)->create([
            'menu_item_id' => $item1->id,
            'tenant_id' => $this->tenant->id,
            'name' => 'コーヒー',
            'price' => 500,
            'quantity' => 5,
        ]);
        OrderItem::factory()->for($order)->create([
            'menu_item_id' => $item2->id,
            'tenant_id' => $this->tenant->id,
            'name' => 'ラテ',
            'price' => 500,
            'quantity' => 3,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/tenant/dashboard/top-items');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['rank', 'menu_item_id', 'name', 'quantity', 'revenue'],
                ],
            ]);

        $this->assertEquals(1, $response->json('data.0.rank'));
        $this->assertEquals('コーヒー', $response->json('data.0.name'));
        $this->assertEquals(5, $response->json('data.0.quantity'));
    }

    public function test_staff_can_access_top_items(): void
    {
        $response = $this->actingAs($this->staff)
            ->getJson('/api/tenant/dashboard/top-items');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['rank', 'menu_item_id', 'name', 'quantity', 'revenue'],
                ],
            ]);
    }

    public function test_can_get_top_items_with_period(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/tenant/dashboard/top-items?period=week');

        $response->assertStatus(200);
    }

    public function test_can_get_top_items_with_limit(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/tenant/dashboard/top-items?limit=5');

        $response->assertStatus(200);
    }

    public function test_top_items_validation_error_on_invalid_period(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/tenant/dashboard/top-items?period=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['period']);
    }

    public function test_top_items_validation_error_on_invalid_limit(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/tenant/dashboard/top-items?limit=100');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['limit']);
    }

    public function test_admin_can_get_hourly_distribution(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/tenant/dashboard/hourly');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['hour', 'orders', 'sales'],
                ],
            ]);

        $this->assertCount(24, $response->json('data'));
    }

    public function test_staff_can_get_hourly_distribution(): void
    {
        $response = $this->actingAs($this->staff)
            ->getJson('/api/tenant/dashboard/hourly');

        $response->assertStatus(200);
    }

    public function test_can_get_hourly_distribution_with_date(): void
    {
        $date = Carbon::today()->subDays(1)->format('Y-m-d');

        $response = $this->actingAs($this->admin)
            ->getJson("/api/tenant/dashboard/hourly?date={$date}");

        $response->assertStatus(200);
    }

    public function test_hourly_validation_error_on_invalid_date(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/tenant/dashboard/hourly?date=invalid-date');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    public function test_unauthenticated_user_cannot_access(): void
    {
        $response = $this->getJson('/api/tenant/dashboard/hourly');

        $response->assertStatus(401);
    }

    public function test_customer_cannot_access(): void
    {
        $response = $this->actingAs($this->customer)
            ->getJson('/api/tenant/dashboard/hourly');

        $response->assertStatus(403);
    }

    public function test_customer_cannot_export_csv(): void
    {
        $targetDate = Carbon::today()->format('Y-m-d');

        $response = $this->actingAs($this->customer)
            ->getJson("/api/tenant/dashboard/export/csv?start_date={$targetDate}&end_date={$targetDate}");

        $response->assertStatus(403);
    }

    public function test_top_items_does_not_include_other_tenant_data(): void
    {
        $today = Carbon::today()->format('Y-m-d');

        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '自テナント商品',
            'price' => 500,
        ]);
        $order = Order::factory()->forTenant($this->tenant)->completed()->forBusinessDate($today)->create();
        OrderItem::factory()->for($order)->create([
            'menu_item_id' => $item->id,
            'tenant_id' => $this->tenant->id,
            'name' => '自テナント商品',
            'price' => 500,
            'quantity' => 10,
        ]);

        $otherTenant = Tenant::factory()->create();
        $otherItem = MenuItem::factory()->create([
            'tenant_id' => $otherTenant->id,
            'name' => '他テナント商品',
            'price' => 500,
        ]);
        $otherOrder = Order::factory()->forTenant($otherTenant)->completed()->forBusinessDate($today)->create();
        OrderItem::factory()->for($otherOrder)->create([
            'menu_item_id' => $otherItem->id,
            'tenant_id' => $otherTenant->id,
            'name' => '他テナント商品',
            'price' => 500,
            'quantity' => 100,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/tenant/dashboard/top-items');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('自テナント商品', $data[0]['name']);
    }

    public function test_export_csv_does_not_include_other_tenant_data(): void
    {
        $targetDate = Carbon::today()->subDay()->format('Y-m-d');

        $ownOrder = Order::factory()
            ->forTenant($this->tenant)
            ->completed()
            ->forBusinessDate($targetDate)
            ->withOrderCode('A901')
            ->create(['total_amount' => 900]);
        $ownPayment = Payment::factory()->forOrder($ownOrder)->completed()->card()->create(['amount' => 900]);
        $ownOrder->update(['payment_id' => $ownPayment->id]);

        $otherTenant = Tenant::factory()->create();
        $otherOrder = Order::factory()
            ->forTenant($otherTenant)
            ->completed()
            ->forBusinessDate($targetDate)
            ->withOrderCode('B999')
            ->create(['total_amount' => 9999]);
        $otherPayment = Payment::factory()->forOrder($otherOrder)->completed()->paypay()->create(['amount' => 9999]);
        $otherOrder->update(['payment_id' => $otherPayment->id]);

        $response = $this->actingAs($this->admin)->get(
            "/api/tenant/dashboard/export/csv?start_date={$targetDate}&end_date={$targetDate}"
        );

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('A901', $content);
        $this->assertStringNotContainsString('B999', $content);
    }
}
