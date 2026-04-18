<?php

declare(strict_types=1);

namespace Tests\Feature\Multitenancy;

use App\Enums\OrderStatus;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossTenantAccessTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        app(TenantContext::class)->clear();

        $this->tenantA = Tenant::factory()->create(['is_active' => true]);
        $this->tenantB = Tenant::factory()->create(['is_active' => true]);
        $this->customer = User::factory()->customer()->create();
    }

    public function test_tenant_a_orders_invisible_from_tenant_b_context(): void
    {
        $orderA = Order::factory()->forTenant($this->tenantA)->forUser($this->customer)->create();
        $orderB = Order::factory()->forTenant($this->tenantB)->forUser($this->customer)->create();

        // テナントAのコンテキストからはテナントBの注文が見えない
        app(TenantContext::class)->setTenant($this->tenantA->id);

        $orders = Order::all();
        $this->assertCount(1, $orders);
        $this->assertEquals($this->tenantA->id, $orders->first()->tenant_id);

        // テナントBの注文をIDで直接検索しても見えない
        $this->assertNull(Order::find($orderB->id));
    }

    public function test_tenant_a_menu_items_invisible_from_tenant_b_context(): void
    {
        $itemA = MenuItem::factory()->create(['tenant_id' => $this->tenantA->id]);
        $itemB = MenuItem::factory()->create(['tenant_id' => $this->tenantB->id]);

        app(TenantContext::class)->setTenant($this->tenantA->id);

        $items = MenuItem::all();
        $this->assertCount(1, $items);
        $this->assertEquals($this->tenantA->id, $items->first()->tenant_id);
        $this->assertNull(MenuItem::find($itemB->id));
    }

    public function test_customer_cannot_add_tenant_b_item_to_tenant_a_cart(): void
    {
        // テナントBのメニュー商品
        $itemB = MenuItem::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        // テナントAへのカート追加リクエストでテナントBの商品IDを指定
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/customer/cart/items', [
                'tenant_id' => $this->tenantA->id,
                'menu_item_id' => $itemB->id,
                'quantity' => 1,
            ]);

        // バリデーションで商品がテナントに属していないことを検出
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['menu_item_id']);
    }

    public function test_tenant_admin_cannot_access_other_tenant_staff(): void
    {
        $adminA = User::factory()->tenantAdmin()->create();
        TenantUser::factory()->create([
            'user_id' => $adminA->id,
            'tenant_id' => $this->tenantA->id,
        ]);

        $staffB = User::factory()->tenantStaff()->create();
        TenantUser::factory()->create([
            'user_id' => $staffB->id,
            'tenant_id' => $this->tenantB->id,
        ]);

        // テナントAの管理者がテナントBのスタッフ詳細にアクセス
        $response = $this->actingAs($adminA, 'sanctum')
            ->getJson("/api/tenant/staff/{$staffB->id}");

        // ensureStaffBelongsToTenant()でテナント所属チェックにより404
        $response->assertNotFound();
    }

    public function test_tenant_admin_cannot_list_other_tenant_orders(): void
    {
        $adminA = User::factory()->tenantAdmin()->create();
        TenantUser::factory()->create([
            'user_id' => $adminA->id,
            'tenant_id' => $this->tenantA->id,
        ]);

        // テナントBの注文を作成
        Order::factory()->forTenant($this->tenantB)->forUser($this->customer)->create([
            'status' => OrderStatus::Accepted,
        ]);

        // テナントAの管理者がKDS（注文一覧）を取得
        $response = $this->actingAs($adminA, 'sanctum')
            ->getJson('/api/tenant/kds/orders');

        $response->assertOk();

        // テナントBの注文は含まれない
        $data = $response->json('data');
        foreach ($data as $order) {
            $this->assertEquals($this->tenantA->id, $order['tenant_id'] ?? null);
        }
        // テナントAの注文がないので空であること
        $this->assertEmpty($data);
    }

    public function test_tenant_admin_cannot_manage_other_tenant_menu_categories(): void
    {
        $adminA = User::factory()->tenantAdmin()->create();
        TenantUser::factory()->create([
            'user_id' => $adminA->id,
            'tenant_id' => $this->tenantA->id,
        ]);

        $categoryB = MenuCategory::factory()->create([
            'tenant_id' => $this->tenantB->id,
        ]);

        // テナントAの管理者がテナントBのカテゴリーを更新
        $response = $this->actingAs($adminA, 'sanctum')
            ->patchJson("/api/tenant/menu/categories/{$categoryB->id}", [
                'name' => '不正更新',
            ]);

        $response->assertNotFound();
    }

    public function test_tenant_admin_cannot_manage_other_tenant_menu_items(): void
    {
        $adminA = User::factory()->tenantAdmin()->create();
        TenantUser::factory()->create([
            'user_id' => $adminA->id,
            'tenant_id' => $this->tenantA->id,
        ]);

        $itemB = MenuItem::factory()->create([
            'tenant_id' => $this->tenantB->id,
        ]);

        // テナントAの管理者がテナントBのメニュー商品を削除
        $response = $this->actingAs($adminA, 'sanctum')
            ->deleteJson("/api/tenant/menu/items/{$itemB->id}");

        $response->assertNotFound();

        // 商品が削除されていないことを確認
        $this->assertDatabaseHas('menu_items', ['id' => $itemB->id]);
    }

    public function test_data_exists_but_scoped_correctly(): void
    {
        Order::factory()->forTenant($this->tenantA)->forUser($this->customer)->count(3)->create();
        Order::factory()->forTenant($this->tenantB)->forUser($this->customer)->count(2)->create();

        // テナントスコープなしでは全件見える
        $allOrders = Order::withoutTenantScope()->get();
        $this->assertCount(5, $allOrders);

        // テナントAスコープでは3件
        app(TenantContext::class)->setTenant($this->tenantA->id);
        $this->assertCount(3, Order::all());

        // テナントBスコープでは2件
        app(TenantContext::class)->clear();
        app(TenantContext::class)->setTenant($this->tenantB->id);
        $this->assertCount(2, Order::all());
    }
}
