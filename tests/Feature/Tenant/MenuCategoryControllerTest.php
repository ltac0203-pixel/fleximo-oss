<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\AuditAction;
use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    private User $staff;

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
    }

    public function test_tenant_admin_can_list_categories(): void
    {
        MenuCategory::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/tenant/menu/categories');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'sort_order', 'is_active'],
                ],
            ]);
    }

    public function test_tenant_staff_can_list_categories(): void
    {
        MenuCategory::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->staff)
            ->getJson('/api/tenant/menu/categories');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_cannot_list_other_tenant_categories(): void
    {
        $otherTenant = Tenant::factory()->create();
        MenuCategory::factory()->count(3)->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/tenant/menu/categories');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_tenant_admin_can_create_category(): void
    {
        $categoryData = [
            'name' => 'ドリンク',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/tenant/menu/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'ドリンク',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('menu_categories', [
            'tenant_id' => $this->tenant->id,
            'name' => 'ドリンク',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::MenuCategoryCreated->value,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_create_category_assigns_next_sort_order_when_not_provided(): void
    {
        MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sort_order' => 1,
        ]);
        MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/tenant/menu/categories', [
                'name' => 'デザート',
                'is_active' => true,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('menu_categories', [
            'tenant_id' => $this->tenant->id,
            'name' => 'デザート',
            'sort_order' => 3,
        ]);
    }

    public function test_create_category_uses_provided_sort_order(): void
    {
        MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/tenant/menu/categories', [
                'name' => '前菜',
                'sort_order' => 10,
                'is_active' => true,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('menu_categories', [
            'tenant_id' => $this->tenant->id,
            'name' => '前菜',
            'sort_order' => 10,
        ]);
    }

    public function test_tenant_staff_cannot_create_category(): void
    {
        $categoryData = [
            'name' => 'ドリンク',
        ];

        $response = $this->actingAs($this->staff)
            ->postJson('/api/tenant/menu/categories', $categoryData);

        $response->assertStatus(403);
    }

    public function test_tenant_admin_can_view_category(): void
    {
        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'フード',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/tenant/menu/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $category->id,
                    'name' => 'フード',
                ],
            ]);
    }

    public function test_cannot_view_other_tenant_category(): void
    {
        $otherTenant = Tenant::factory()->create();
        $category = MenuCategory::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/tenant/menu/categories/{$category->id}");

        $response->assertStatus(404);
    }

    public function test_tenant_admin_can_update_category(): void
    {
        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '旧名前',
        ]);

        $updateData = [
            'name' => '新名前',
            'is_active' => false,
        ];

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/tenant/menu/categories/{$category->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => '新名前',
                    'is_active' => false,
                ],
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::MenuCategoryUpdated->value,
        ]);
    }

    public function test_tenant_admin_can_delete_empty_category(): void
    {
        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/tenant/menu/categories/{$category->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('menu_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_cannot_delete_category_with_items(): void
    {
        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'テストカテゴリ',
        ]);

        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $item->categories()->attach($category->id);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/tenant/menu/categories/{$category->id}");

        $response->assertStatus(409)
            ->assertJson([
                'error' => 'CATEGORY_HAS_ITEMS',
            ]);
    }

    public function test_tenant_admin_can_reorder_categories(): void
    {
        $category1 = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sort_order' => 1,
        ]);
        $category2 = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sort_order' => 2,
        ]);
        $category3 = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sort_order' => 3,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/tenant/menu/categories/reorder', [
                'ordered_ids' => [$category3->id, $category1->id, $category2->id],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('menu_categories', [
            'id' => $category3->id,
            'sort_order' => 1,
        ]);
        $this->assertDatabaseHas('menu_categories', [
            'id' => $category1->id,
            'sort_order' => 2,
        ]);
        $this->assertDatabaseHas('menu_categories', [
            'id' => $category2->id,
            'sort_order' => 3,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::MenuCategoryReordered->value,
        ]);
    }

    public function test_customer_cannot_access_category_endpoints(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($customer)
            ->getJson('/api/tenant/menu/categories');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_category_endpoints(): void
    {
        $response = $this->getJson('/api/tenant/menu/categories');

        $response->assertStatus(401);
    }
}
