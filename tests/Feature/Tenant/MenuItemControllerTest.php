<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\AuditAction;
use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\OptionGroup;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuItemControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    private User $staff;

    private MenuCategory $category;

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

        $this->category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_tenant_admin_can_list_menu_items(): void
    {
        $item1 = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_sold_out' => false,
        ]);
        $item1->categories()->attach($this->category->id);

        $item2 = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_sold_out' => false,
        ]);
        $item2->categories()->attach($this->category->id);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/tenant/menu/items');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'price',
                        'is_active',
                        'is_sold_out',
                        'categories',
                        'option_groups',
                    ],
                ],
            ]);
    }

    public function test_menu_items_include_categories_and_option_groups(): void
    {
        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_sold_out' => false,
        ]);
        $item->categories()->attach($this->category->id);

        $optionGroup = OptionGroup::factory()->create(['tenant_id' => $this->tenant->id]);
        $item->optionGroups()->attach($optionGroup->id);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/tenant/menu/items');

        $response->assertStatus(200);

        $data = $response->json('data.0');
        $this->assertNotEmpty($data['categories']);
        $this->assertNotEmpty($data['option_groups']);
    }

    public function test_tenant_admin_can_create_menu_item(): void
    {
        $itemData = [
            'name' => 'ハンバーガー',
            'description' => '美味しいハンバーガー',
            'price' => 500,
            'category_ids' => [$this->category->id],
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/tenant/menu/items', $itemData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'ハンバーガー',
                    'description' => '美味しいハンバーガー',
                    'price' => 500,
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('menu_items', [
            'tenant_id' => $this->tenant->id,
            'name' => 'ハンバーガー',
            'price' => 500,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::MenuItemCreated->value,
        ]);
    }

    public function test_create_menu_item_assigns_next_sort_order_when_not_provided(): void
    {
        MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sort_order' => 1,
        ]);
        MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/tenant/menu/items', [
                'name' => 'テスト商品',
                'price' => 500,
                'category_ids' => [$this->category->id],
                'is_active' => true,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('menu_items', [
            'tenant_id' => $this->tenant->id,
            'name' => 'テスト商品',
            'sort_order' => 3,
        ]);
    }

    public function test_menu_item_requires_at_least_one_category(): void
    {
        $itemData = [
            'name' => 'ハンバーガー',
            'price' => 500,
            'category_ids' => [],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/tenant/menu/items', $itemData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_ids']);
    }

    public function test_menu_item_can_have_max_3_categories(): void
    {
        $categories = MenuCategory::factory()->count(4)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $itemData = [
            'name' => 'ハンバーガー',
            'price' => 500,
            'category_ids' => $categories->pluck('id')->toArray(),
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/tenant/menu/items', $itemData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_ids']);
    }

    public function test_cannot_use_other_tenant_category(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherCategory = MenuCategory::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $itemData = [
            'name' => 'ハンバーガー',
            'price' => 500,
            'category_ids' => [$otherCategory->id],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/tenant/menu/items', $itemData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_ids']);
    }

    public function test_tenant_admin_can_update_menu_item(): void
    {
        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '旧名前',
            'price' => 300,
        ]);
        $item->categories()->attach($this->category->id);

        $updateData = [
            'name' => '新名前',
            'price' => 400,
        ];

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/tenant/menu/items/{$item->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => '新名前',
                    'price' => 400,
                ],
            ]);
    }

    public function test_tenant_admin_can_delete_menu_item(): void
    {
        $item = MenuItem::factory()->create(['tenant_id' => $this->tenant->id]);
        $item->categories()->attach($this->category->id);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/tenant/menu/items/{$item->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('menu_items', ['id' => $item->id]);
        $this->assertDatabaseMissing('menu_item_categories', ['menu_item_id' => $item->id]);
    }

    public function test_tenant_admin_can_toggle_sold_out(): void
    {
        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_sold_out' => false,
        ]);
        $item->categories()->attach($this->category->id);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/tenant/menu/items/{$item->id}/sold-out");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_sold_out' => true,
                ],
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::MenuItemSoldOutToggled->value,
        ]);
    }

    public function test_tenant_staff_can_toggle_sold_out(): void
    {
        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_sold_out' => false,
        ]);
        $item->categories()->attach($this->category->id);

        $response = $this->actingAs($this->staff)
            ->patchJson("/api/tenant/menu/items/{$item->id}/sold-out");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_sold_out' => true,
                ],
            ]);
    }

    public function test_tenant_admin_can_attach_option_group(): void
    {
        $item = MenuItem::factory()->create(['tenant_id' => $this->tenant->id]);
        $item->categories()->attach($this->category->id);

        $optionGroup = OptionGroup::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/tenant/menu/items/{$item->id}/option-groups", [
                'option_group_id' => $optionGroup->id,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('menu_item_option_groups', [
            'menu_item_id' => $item->id,
            'option_group_id' => $optionGroup->id,
        ]);
    }

    public function test_cannot_attach_other_tenant_option_group(): void
    {
        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_sold_out' => false,
        ]);
        $item->categories()->attach($this->category->id);

        $otherTenant = Tenant::factory()->create();
        $otherOptionGroup = OptionGroup::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/tenant/menu/items/{$item->id}/option-groups", [
                'option_group_id' => $otherOptionGroup->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['option_group_id']);
    }

    public function test_tenant_admin_can_detach_option_group(): void
    {
        $item = MenuItem::factory()->create(['tenant_id' => $this->tenant->id]);
        $item->categories()->attach($this->category->id);

        $optionGroup = OptionGroup::factory()->create(['tenant_id' => $this->tenant->id]);
        $item->optionGroups()->attach($optionGroup->id);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/tenant/menu/items/{$item->id}/option-groups/{$optionGroup->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('menu_item_option_groups', [
            'menu_item_id' => $item->id,
            'option_group_id' => $optionGroup->id,
        ]);
    }

    public function test_tenant_staff_cannot_create_menu_item(): void
    {
        $itemData = [
            'name' => 'ハンバーガー',
            'price' => 500,
            'category_ids' => [$this->category->id],
        ];

        $response = $this->actingAs($this->staff)
            ->postJson('/api/tenant/menu/items', $itemData);

        $response->assertStatus(403);
    }

    public function test_cannot_view_other_tenant_menu_item(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherCategory = MenuCategory::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherItem = MenuItem::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherItem->categories()->attach($otherCategory->id);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/tenant/menu/items/{$otherItem->id}");

        $response->assertStatus(404);
    }

    // アレルゲン情報付きでメニュー商品を作成できる
    public function test_can_create_menu_item_with_allergen_info(): void
    {
        $itemData = [
            'name' => 'アレルゲンテスト商品',
            'price' => 800,
            'category_ids' => [$this->category->id],
            'allergens' => 41, // えび(1) + 小麦(8) + 卵(32)
            'allergen_advisories' => 18432, // 大豆(2048) + 豚肉(16384)
            'allergen_note' => '同一工場で卵・乳を含む製品を製造しています。',
            'nutrition_info' => [
                'energy' => 350.0,
                'protein' => 12.5,
                'fat' => 15.0,
                'carbohydrate' => 42.0,
                'salt' => 1.8,
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/tenant/menu/items', $itemData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'アレルゲンテスト商品',
                    'allergens' => 41,
                    'allergen_advisories' => 18432,
                    'allergen_labels' => ['えび', '小麦', '卵'],
                    'advisory_labels' => ['大豆', '豚肉'],
                    'allergen_note' => '同一工場で卵・乳を含む製品を製造しています。',
                    'nutrition_info' => [
                        'energy' => 350.0,
                        'protein' => 12.5,
                        'fat' => 15.0,
                        'carbohydrate' => 42.0,
                        'salt' => 1.8,
                    ],
                ],
            ]);
    }

    // アレルゲン情報付きでメニュー商品を更新できる
    public function test_can_update_menu_item_with_allergen_info(): void
    {
        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'allergens' => 0,
        ]);
        $item->categories()->attach($this->category->id);

        $updateData = [
            'allergens' => 41,
            'allergen_advisories' => 2048,
            'allergen_note' => 'コンタミ注意',
            'nutrition_info' => [
                'energy' => 500.0,
                'protein' => 20.0,
                'fat' => 25.0,
                'carbohydrate' => 50.0,
                'salt' => 2.5,
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/tenant/menu/items/{$item->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'allergens' => 41,
                    'allergen_advisories' => 2048,
                    'allergen_note' => 'コンタミ注意',
                ],
            ]);
    }

    // アレルゲン値が上限を超える場合にバリデーションエラーになる
    public function test_allergens_validation_rejects_over_max(): void
    {
        $itemData = [
            'name' => 'バリデーションテスト',
            'price' => 500,
            'category_ids' => [$this->category->id],
            'allergens' => 256, // 最大255を超過
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/tenant/menu/items', $itemData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['allergens']);
    }

    // 栄養成分に負の値を指定するとバリデーションエラーになる
    public function test_nutrition_info_validation_rejects_negative_values(): void
    {
        $itemData = [
            'name' => '栄養成分テスト',
            'price' => 500,
            'category_ids' => [$this->category->id],
            'nutrition_info' => [
                'energy' => -100,
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/tenant/menu/items', $itemData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nutrition_info.energy']);
    }
}
