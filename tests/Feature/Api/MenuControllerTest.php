<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MenuControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->customer()->create();
    }

    public function test_authenticated_user_can_get_tenant_menu(): void
    {
        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'ドリンク',
        ]);

        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'コーヒー',
            'price' => 350,
        ]);
        $item->categories()->attach($category->id);

        $response = $this->actingAs($this->user)
            ->getJson("/api/tenants/{$this->tenant->id}/menu");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'categories' => [
                        '*' => [
                            'id',
                            'name',
                            'sort_order',
                            'items' => [
                                '*' => [
                                    'id',
                                    'name',
                                    'description',
                                    'price',
                                    'is_sold_out',
                                    'is_available',
                                    'option_groups',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $this->assertEquals('ドリンク', $response->json('data.categories.0.name'));
        $this->assertEquals('コーヒー', $response->json('data.categories.0.items.0.name'));
    }

    public function test_inactive_categories_are_excluded(): void
    {
        $activeCategory = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'アクティブカテゴリ',
            'is_active' => true,
        ]);

        MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '非アクティブカテゴリ',
            'is_active' => false,
        ]);

        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $item->categories()->attach($activeCategory->id);

        $response = $this->actingAs($this->user)
            ->getJson("/api/tenants/{$this->tenant->id}/menu");

        $response->assertOk();
        $categories = $response->json('data.categories');
        $this->assertCount(1, $categories);
        $this->assertEquals('アクティブカテゴリ', $categories[0]['name']);
    }

    public function test_inactive_items_are_excluded(): void
    {
        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $activeItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'アクティブ商品',
            'is_active' => true,
        ]);
        $activeItem->categories()->attach($category->id);

        $inactiveItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '非アクティブ商品',
            'is_active' => false,
        ]);
        $inactiveItem->categories()->attach($category->id);

        $response = $this->actingAs($this->user)
            ->getJson("/api/tenants/{$this->tenant->id}/menu");

        $response->assertOk();
        $items = $response->json('data.categories.0.items');
        $this->assertCount(1, $items);
        $this->assertEquals('アクティブ商品', $items[0]['name']);
    }

    public function test_sold_out_items_are_shown_with_flag(): void
    {
        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '売り切れ商品',
            'is_sold_out' => true,
        ]);
        $item->categories()->attach($category->id);

        $response = $this->actingAs($this->user)
            ->getJson("/api/tenants/{$this->tenant->id}/menu");

        $response->assertOk();
        $this->assertTrue($response->json('data.categories.0.items.0.is_sold_out'));
        $this->assertFalse($response->json('data.categories.0.items.0.is_available'));
    }

    public function test_menu_is_cached(): void
    {
        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $item->categories()->attach($category->id);

        Cache::forget("tenant:{$this->tenant->id}:menu");

        $this->actingAs($this->user)
            ->getJson("/api/tenants/{$this->tenant->id}/menu")
            ->assertOk();

        $this->assertTrue(Cache::has("tenant:{$this->tenant->id}:menu"));
    }

    public function test_inactive_tenant_returns_404(): void
    {
        $inactiveTenant = Tenant::factory()->inactive()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/tenants/{$inactiveTenant->id}/menu");

        $response->assertNotFound()
            ->assertJson(['message' => 'テナントが見つかりません']);
    }

    public function test_unauthenticated_user_cannot_get_menu(): void
    {
        $response = $this->getJson("/api/tenants/{$this->tenant->id}/menu");

        $response->assertUnauthorized();
    }

    public function test_categories_are_ordered_by_sort_order(): void
    {
        $category1 = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'カテゴリ1',
            'sort_order' => 2,
        ]);

        $category2 = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'カテゴリ2',
            'sort_order' => 1,
        ]);

        $category3 = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'カテゴリ3',
            'sort_order' => 3,
        ]);

        foreach ([$category1, $category2, $category3] as $category) {
            $item = MenuItem::factory()->create(['tenant_id' => $this->tenant->id]);
            $item->categories()->attach($category->id);
        }

        $response = $this->actingAs($this->user)
            ->getJson("/api/tenants/{$this->tenant->id}/menu");

        $response->assertOk();
        $categories = $response->json('data.categories');

        $this->assertEquals('カテゴリ2', $categories[0]['name']);
        $this->assertEquals('カテゴリ1', $categories[1]['name']);
        $this->assertEquals('カテゴリ3', $categories[2]['name']);
    }

    public function test_items_are_ordered_by_sort_order(): void
    {
        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $item1 = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '商品1',
            'sort_order' => 3,
        ]);
        $item1->categories()->attach($category->id);

        $item2 = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '商品2',
            'sort_order' => 1,
        ]);
        $item2->categories()->attach($category->id);

        $item3 = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '商品3',
            'sort_order' => 2,
        ]);
        $item3->categories()->attach($category->id);

        $response = $this->actingAs($this->user)
            ->getJson("/api/tenants/{$this->tenant->id}/menu");

        $response->assertOk();
        $items = $response->json('data.categories.0.items');

        $this->assertEquals('商品2', $items[0]['name']);
        $this->assertEquals('商品3', $items[1]['name']);
        $this->assertEquals('商品1', $items[2]['name']);
    }

    public function test_menu_includes_nested_option_groups(): void
    {
        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $item->categories()->attach($category->id);

        $optionGroup = OptionGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'サイズ',
            'required' => true,
            'min_select' => 1,
            'max_select' => 1,
        ]);

        $option1 = Option::factory()->create([
            'option_group_id' => $optionGroup->id,
            'name' => 'S',
            'price' => 0,
        ]);

        $option2 = Option::factory()->create([
            'option_group_id' => $optionGroup->id,
            'name' => 'M',
            'price' => 50,
        ]);

        $item->optionGroups()->attach($optionGroup->id);

        $response = $this->actingAs($this->user)
            ->getJson("/api/tenants/{$this->tenant->id}/menu");

        $response->assertOk();
        $itemData = $response->json('data.categories.0.items.0');

        $this->assertCount(1, $itemData['option_groups']);
        $this->assertEquals('サイズ', $itemData['option_groups'][0]['name']);
        $this->assertTrue($itemData['option_groups'][0]['required']);
        $this->assertCount(2, $itemData['option_groups'][0]['options']);
    }

    public function test_inactive_option_groups_are_excluded(): void
    {
        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $item->categories()->attach($category->id);

        $activeGroup = OptionGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'アクティブグループ',
            'is_active' => true,
        ]);
        Option::factory()->create(['option_group_id' => $activeGroup->id]);

        $inactiveGroup = OptionGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '非アクティブグループ',
            'is_active' => false,
        ]);
        Option::factory()->create(['option_group_id' => $inactiveGroup->id]);

        $item->optionGroups()->attach([$activeGroup->id, $inactiveGroup->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/tenants/{$this->tenant->id}/menu");

        $response->assertOk();
        $optionGroups = $response->json('data.categories.0.items.0.option_groups');

        $this->assertCount(1, $optionGroups);
        $this->assertEquals('アクティブグループ', $optionGroups[0]['name']);
    }

    public function test_inactive_options_are_excluded(): void
    {
        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $item->categories()->attach($category->id);

        $optionGroup = OptionGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        Option::factory()->create([
            'option_group_id' => $optionGroup->id,
            'name' => 'アクティブオプション',
            'is_active' => true,
        ]);

        Option::factory()->create([
            'option_group_id' => $optionGroup->id,
            'name' => '非アクティブオプション',
            'is_active' => false,
        ]);

        $item->optionGroups()->attach($optionGroup->id);

        $response = $this->actingAs($this->user)
            ->getJson("/api/tenants/{$this->tenant->id}/menu");

        $response->assertOk();
        $options = $response->json('data.categories.0.items.0.option_groups.0.options');

        $this->assertCount(1, $options);
        $this->assertEquals('アクティブオプション', $options[0]['name']);
    }

    public function test_different_tenant_menus_are_isolated(): void
    {
        $otherTenant = Tenant::factory()->create();

        $category1 = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'テナント1のカテゴリ',
        ]);
        $item1 = MenuItem::factory()->create(['tenant_id' => $this->tenant->id]);
        $item1->categories()->attach($category1->id);

        $category2 = MenuCategory::factory()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'テナント2のカテゴリ',
        ]);
        $item2 = MenuItem::factory()->create(['tenant_id' => $otherTenant->id]);
        $item2->categories()->attach($category2->id);

        $response = $this->actingAs($this->user)
            ->getJson("/api/tenants/{$this->tenant->id}/menu");

        $response->assertOk();
        $categories = $response->json('data.categories');

        $this->assertCount(1, $categories);
        $this->assertEquals('テナント1のカテゴリ', $categories[0]['name']);
    }

    public function test_tenant_admin_can_also_get_menu(): void
    {
        $tenantAdmin = User::factory()->tenantAdmin()->create();

        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $item = MenuItem::factory()->create(['tenant_id' => $this->tenant->id]);
        $item->categories()->attach($category->id);

        $response = $this->actingAs($tenantAdmin)
            ->getJson("/api/tenants/{$this->tenant->id}/menu");

        $response->assertOk();
    }

    public function test_nonexistent_tenant_returns_404(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/tenants/99999/menu');

        $response->assertNotFound();
    }

    public function test_empty_menu_returns_empty_categories(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/tenants/{$this->tenant->id}/menu");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'categories' => [],
                ],
            ]);
    }
}
