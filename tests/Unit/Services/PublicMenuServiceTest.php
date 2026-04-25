<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Tenant;
use App\Services\PublicMenuService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicMenuServiceTest extends TestCase
{
    use RefreshDatabase;

    private PublicMenuService $publicMenuService;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publicMenuService = app(PublicMenuService::class);
        $this->tenant = Tenant::factory()->create();
    }

    public function test_get_menu_returns_correct_structure(): void
    {
        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'テストカテゴリ',
        ]);

        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'テスト商品',
            'price' => 500,
        ]);
        $item->categories()->attach($category->id);

        $result = $this->publicMenuService->getMenu($this->tenant);

        $this->assertArrayHasKey('categories', $result);
        $this->assertCount(1, $result['categories']);
        $this->assertEquals('テストカテゴリ', $result['categories'][0]['name']);
        $this->assertCount(1, $result['categories'][0]['items']);
        $this->assertEquals('テスト商品', $result['categories'][0]['items'][0]['name']);
        $this->assertEquals(500, $result['categories'][0]['items'][0]['price']);
    }

    public function test_get_menu_caches_result(): void
    {
        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $item->categories()->attach($category->id);

        $cacheKey = "tenant:{$this->tenant->id}:menu";
        Cache::forget($cacheKey);

        $this->assertFalse(Cache::has($cacheKey));

        $this->publicMenuService->getMenu($this->tenant);

        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_invalidate_cache_removes_cached_data(): void
    {
        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $item->categories()->attach($category->id);

        $cacheKey = "tenant:{$this->tenant->id}:menu";

        $this->publicMenuService->getMenu($this->tenant);
        $this->assertTrue(Cache::has($cacheKey));

        $this->publicMenuService->invalidateCache($this->tenant->id);
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_menu_data_includes_nested_option_groups(): void
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

        Option::factory()->create([
            'option_group_id' => $optionGroup->id,
            'name' => 'S',
            'price' => 0,
        ]);

        Option::factory()->create([
            'option_group_id' => $optionGroup->id,
            'name' => 'M',
            'price' => 50,
        ]);

        $item->optionGroups()->attach($optionGroup->id);

        $result = $this->publicMenuService->getMenu($this->tenant);

        $itemData = $result['categories'][0]['items'][0];
        $this->assertCount(1, $itemData['option_groups']);
        $this->assertEquals('サイズ', $itemData['option_groups'][0]['name']);
        $this->assertTrue($itemData['option_groups'][0]['required']);
        $this->assertCount(2, $itemData['option_groups'][0]['options']);
    }

    public function test_is_available_is_calculated_at_runtime(): void
    {
        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $availableItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '利用可能商品',
            'is_active' => true,
            'is_sold_out' => false,
            'available_days' => 127,
        ]);
        $availableItem->categories()->attach($category->id);

        $soldOutItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '売り切れ商品',
            'is_active' => true,
            'is_sold_out' => true,
        ]);
        $soldOutItem->categories()->attach($category->id);

        $result = $this->publicMenuService->getMenu($this->tenant);

        $items = $result['categories'][0]['items'];

        $availableItemData = collect($items)->firstWhere('name', '利用可能商品');
        $soldOutItemData = collect($items)->firstWhere('name', '売り切れ商品');

        $this->assertTrue($availableItemData['is_available']);
        $this->assertFalse($soldOutItemData['is_available']);
    }

    public function test_tenant_context_is_restored_after_get_menu(): void
    {
        $otherTenant = Tenant::factory()->create();
        $tenantContext = app(TenantContext::class);

        $tenantContext->setTenantInstance($otherTenant);

        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $item = MenuItem::factory()->create(['tenant_id' => $this->tenant->id]);
        $item->categories()->attach($category->id);

        $this->publicMenuService->getMenu($this->tenant);

        $this->assertEquals($otherTenant->id, $tenantContext->getTenantId());
    }

    public function test_tenant_context_is_cleared_if_previously_null(): void
    {
        $tenantContext = app(TenantContext::class);
        $tenantContext->clear();

        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $item = MenuItem::factory()->create(['tenant_id' => $this->tenant->id]);
        $item->categories()->attach($category->id);

        $this->publicMenuService->getMenu($this->tenant);

        $this->assertNull($tenantContext->getTenantId());
    }

    public function test_only_active_categories_are_included(): void
    {
        MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'アクティブカテゴリ',
            'is_active' => true,
        ]);

        MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '非アクティブカテゴリ',
            'is_active' => false,
        ]);

        $result = $this->publicMenuService->getMenu($this->tenant);

        $categoryNames = array_column($result['categories'], 'name');
        $this->assertNotContains('非アクティブカテゴリ', $categoryNames);
    }

    public function test_only_active_items_are_included(): void
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

        $result = $this->publicMenuService->getMenu($this->tenant);

        $itemNames = array_column($result['categories'][0]['items'], 'name');
        $this->assertContains('アクティブ商品', $itemNames);
        $this->assertNotContains('非アクティブ商品', $itemNames);
    }

    public function test_only_active_option_groups_are_included(): void
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

        $result = $this->publicMenuService->getMenu($this->tenant);

        $groupNames = array_column($result['categories'][0]['items'][0]['option_groups'], 'name');
        $this->assertContains('アクティブグループ', $groupNames);
        $this->assertNotContains('非アクティブグループ', $groupNames);
    }

    public function test_only_active_options_are_included(): void
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

        $result = $this->publicMenuService->getMenu($this->tenant);

        $optionNames = array_column(
            $result['categories'][0]['items'][0]['option_groups'][0]['options'],
            'name'
        );
        $this->assertContains('アクティブオプション', $optionNames);
        $this->assertNotContains('非アクティブオプション', $optionNames);
    }

    public function test_unrestricted_items_have_precomputed_availability(): void
    {
        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $availableItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '制限なし利用可能',
            'is_sold_out' => false,
            'available_days' => MenuItem::ALL_DAYS,
            'available_from' => null,
            'available_until' => null,
        ]);
        $availableItem->categories()->attach($category->id);

        $soldOutItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '制限なし売り切れ',
            'is_sold_out' => true,
            'available_days' => MenuItem::ALL_DAYS,
            'available_from' => null,
            'available_until' => null,
        ]);
        $soldOutItem->categories()->attach($category->id);

        $result = $this->publicMenuService->getMenu($this->tenant);
        $items = $result['categories'][0]['items'];

        $available = collect($items)->firstWhere('name', '制限なし利用可能');
        $soldOut = collect($items)->firstWhere('name', '制限なし売り切れ');

        $this->assertTrue($available['is_available']);
        $this->assertFalse($soldOut['is_available']);
    }

    public function test_restricted_items_are_recalculated_based_on_current_time(): void
    {
        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // 11:00〜14:00 のみ利用可能なアイテム
        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '時間制限アイテム',
            'is_sold_out' => false,
            'available_days' => MenuItem::ALL_DAYS,
            'available_from' => '11:00:00',
            'available_until' => '14:00:00',
        ]);
        $item->categories()->attach($category->id);

        // 12:00（範囲内）でテスト
        Carbon::setTestNow(Carbon::create(2026, 2, 15, 12, 0, 0));
        Cache::forget("tenant:{$this->tenant->id}:menu");

        $result = $this->publicMenuService->getMenu($this->tenant);
        $itemData = collect($result['categories'][0]['items'])->firstWhere('name', '時間制限アイテム');
        $this->assertTrue($itemData['is_available']);

        // 16:00（範囲外）でテスト
        Carbon::setTestNow(Carbon::create(2026, 2, 15, 16, 0, 0));
        Cache::forget("tenant:{$this->tenant->id}:menu");

        $result = $this->publicMenuService->getMenu($this->tenant);
        $itemData = collect($result['categories'][0]['items'])->firstWhere('name', '時間制限アイテム');
        $this->assertFalse($itemData['is_available']);

        Carbon::setTestNow();
    }

    public function test_internal_flags_not_exposed_in_response(): void
    {
        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $item->categories()->attach($category->id);

        $result = $this->publicMenuService->getMenu($this->tenant);

        $this->assertArrayNotHasKey('has_restricted_items', $result);
        $this->assertArrayNotHasKey('needs_recalc', $result['categories'][0]['items'][0]);
    }

    // OpenAPI components.schemas.PublicOptionGroup / PublicOption と
    // 出力構造を厳密に一致させ、ドリフトを早期検知する
    public function test_option_group_output_matches_public_openapi_schema(): void
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
        Option::factory()->create(['option_group_id' => $optionGroup->id]);
        $item->optionGroups()->attach($optionGroup->id);

        $result = $this->publicMenuService->getMenu($this->tenant);

        $optionGroupData = $result['categories'][0]['items'][0]['option_groups'][0];
        $this->assertSame(
            ['id', 'name', 'required', 'min_select', 'max_select', 'options'],
            array_keys($optionGroupData)
        );

        $optionData = $optionGroupData['options'][0];
        $this->assertSame(
            ['id', 'name', 'price'],
            array_keys($optionData)
        );
    }

    public function test_categories_are_ordered_by_sort_order(): void
    {
        $category1 = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'カテゴリ1',
            'sort_order' => 3,
        ]);

        $category2 = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'カテゴリ2',
            'sort_order' => 1,
        ]);

        $category3 = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'カテゴリ3',
            'sort_order' => 2,
        ]);

        foreach ([$category1, $category2, $category3] as $category) {
            $item = MenuItem::factory()->create(['tenant_id' => $this->tenant->id]);
            $item->categories()->attach($category->id);
        }

        $result = $this->publicMenuService->getMenu($this->tenant);

        $this->assertEquals('カテゴリ2', $result['categories'][0]['name']);
        $this->assertEquals('カテゴリ3', $result['categories'][1]['name']);
        $this->assertEquals('カテゴリ1', $result['categories'][2]['name']);
    }
}
