<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\Allergen;
use App\Enums\AllergenAdvisory;
use App\Exceptions\ItemNotAvailableException;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MenuItemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->clear();
    }

    public function test_menu_item_can_be_created(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        $item = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'コーヒー',
            'price' => 350,
        ]);

        $this->assertDatabaseHas('menu_items', [
            'tenant_id' => $tenant->id,
            'name' => 'コーヒー',
            'price' => 350,
        ]);
    }

    public function test_tenant_scope_is_applied(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        MenuItem::factory()->create(['tenant_id' => $tenant1->id]);
        MenuItem::factory()->create(['tenant_id' => $tenant2->id]);

        app(TenantContext::class)->setTenant($tenant1->id);
        $items = MenuItem::all();

        $this->assertCount(1, $items);
        $this->assertEquals($tenant1->id, $items->first()->tenant_id);
    }

    public function test_active_scope_filters_active_items(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        MenuItem::factory()->create(['tenant_id' => $tenant->id, 'is_active' => true]);
        MenuItem::factory()->create(['tenant_id' => $tenant->id, 'is_active' => false]);

        $activeItems = MenuItem::active()->get();

        $this->assertCount(1, $activeItems);
        $this->assertTrue($activeItems->first()->is_active);
    }

    public function test_available_scope_filters_available_items(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        MenuItem::factory()->create(['tenant_id' => $tenant->id, 'is_active' => true, 'is_sold_out' => false]);
        MenuItem::factory()->create(['tenant_id' => $tenant->id, 'is_active' => true, 'is_sold_out' => true]);
        MenuItem::factory()->create(['tenant_id' => $tenant->id, 'is_active' => false, 'is_sold_out' => false]);

        $availableItems = MenuItem::available()->get();

        $this->assertCount(1, $availableItems);
        $this->assertTrue($availableItems->first()->is_active);
        $this->assertFalse($availableItems->first()->is_sold_out);
    }

    public function test_ordered_scope_sorts_by_sort_order(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        MenuItem::factory()->create(['tenant_id' => $tenant->id, 'sort_order' => 30]);
        MenuItem::factory()->create(['tenant_id' => $tenant->id, 'sort_order' => 10]);
        MenuItem::factory()->create(['tenant_id' => $tenant->id, 'sort_order' => 20]);

        $items = MenuItem::ordered()->get();

        $this->assertEquals(10, $items[0]->sort_order);
        $this->assertEquals(20, $items[1]->sort_order);
        $this->assertEquals(30, $items[2]->sort_order);
    }

    public function test_belongs_to_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $item = MenuItem::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertInstanceOf(Tenant::class, $item->tenant);
        $this->assertEquals($tenant->id, $item->tenant->id);
    }

    public function test_tenant_id_is_automatically_set(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        $item = MenuItem::create([
            'name' => 'Auto Tenant Item',
            'price' => 500,
            'sort_order' => 10,
        ]);

        $this->assertEquals($tenant->id, $item->tenant_id);
    }

    public function test_casts_is_active_to_boolean(): void
    {
        $item = MenuItem::factory()->create(['is_active' => 1]);

        $this->assertIsBool($item->is_active);
        $this->assertTrue($item->is_active);
    }

    public function test_casts_is_sold_out_to_boolean(): void
    {
        $item = MenuItem::factory()->create(['is_sold_out' => 1]);

        $this->assertIsBool($item->is_sold_out);
        $this->assertTrue($item->is_sold_out);
    }

    public function test_casts_price_to_integer(): void
    {
        $item = MenuItem::factory()->create(['price' => '500']);

        $this->assertIsInt($item->price);
        $this->assertEquals(500, $item->price);
    }

    public function test_casts_available_days_to_integer(): void
    {
        $item = MenuItem::factory()->create(['available_days' => '127']);

        $this->assertIsInt($item->available_days);
        $this->assertEquals(127, $item->available_days);
    }

    public function test_categories_relationship(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        $category1 = MenuCategory::factory()->create(['tenant_id' => $tenant->id, 'name' => 'ドリンク']);
        $category2 = MenuCategory::factory()->create(['tenant_id' => $tenant->id, 'name' => 'フード']);

        $item = MenuItem::factory()->create(['tenant_id' => $tenant->id]);
        $item->categories()->attach([$category1->id, $category2->id]);

        $this->assertCount(2, $item->categories);
        $this->assertTrue($item->categories->contains('id', $category1->id));
        $this->assertTrue($item->categories->contains('id', $category2->id));
    }

    public function test_is_available_on_returns_true_for_available_day(): void
    {
        $item = MenuItem::factory()->create(['available_days' => MenuItem::ALL_DAYS]);

        for ($day = 0; $day <= 6; $day++) {
            $this->assertTrue($item->isAvailableOn($day));
        }
    }

    public function test_is_available_on_returns_false_for_unavailable_day(): void
    {

        $item = MenuItem::factory()->create(['available_days' => MenuItem::WEEKDAYS]);

        $this->assertFalse($item->isAvailableOn(0));

        $this->assertTrue($item->isAvailableOn(1));

        $this->assertFalse($item->isAvailableOn(6));
    }

    public function test_is_available_now_returns_false_when_inactive(): void
    {
        $item = MenuItem::factory()->inactive()->create();

        $this->assertFalse($item->isAvailableNow());
    }

    public function test_ensure_available_now_throws_exception_when_not_available(): void
    {
        $item = MenuItem::factory()->inactive()->create();

        $this->expectException(ItemNotAvailableException::class);

        $item->ensureAvailableNow();
    }

    public function test_is_available_now_returns_false_when_sold_out(): void
    {
        $item = MenuItem::factory()->soldOut()->create();

        $this->assertFalse($item->isAvailableNow());
    }

    public function test_is_available_now_returns_false_when_day_not_available(): void
    {

        $currentDayFlag = 1 << Carbon::now()->dayOfWeek;
        $availableDays = MenuItem::ALL_DAYS ^ $currentDayFlag;

        $item = MenuItem::factory()->create(['available_days' => $availableDays]);

        $this->assertFalse($item->isAvailableNow());
    }

    public function test_is_available_now_returns_false_when_outside_time_range(): void
    {
        Carbon::setTestNow(Carbon::createFromTime(15, 0, 0));

        $item = MenuItem::factory()->morningOnly()->create();

        $this->assertFalse($item->isAvailableNow());

        Carbon::setTestNow();
    }

    public function test_is_available_now_returns_true_when_within_time_range(): void
    {
        Carbon::setTestNow(Carbon::createFromTime(10, 0, 0));

        $item = MenuItem::factory()->morningOnly()->create([
            'available_days' => MenuItem::ALL_DAYS,
        ]);

        $this->assertTrue($item->isAvailableNow());

        Carbon::setTestNow();
    }

    public function test_ensure_available_now_does_not_throw_when_available(): void
    {
        Carbon::setTestNow(Carbon::createFromTime(10, 0, 0));

        try {
            $item = MenuItem::factory()->morningOnly()->create([
                'available_days' => MenuItem::ALL_DAYS,
            ]);

            $item->ensureAvailableNow();

            $this->assertTrue(true);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_is_available_now_ignores_time_when_only_one_is_set(): void
    {
        $item = MenuItem::factory()->create([
            'available_from' => '09:00:00',
            'available_until' => null,
            'available_days' => MenuItem::ALL_DAYS,
        ]);

        $this->assertTrue($item->isAvailableNow());
    }

    public function test_day_constants_are_correct(): void
    {
        $this->assertEquals(1, MenuItem::SUNDAY);
        $this->assertEquals(2, MenuItem::MONDAY);
        $this->assertEquals(4, MenuItem::TUESDAY);
        $this->assertEquals(8, MenuItem::WEDNESDAY);
        $this->assertEquals(16, MenuItem::THURSDAY);
        $this->assertEquals(32, MenuItem::FRIDAY);
        $this->assertEquals(64, MenuItem::SATURDAY);
        $this->assertEquals(127, MenuItem::ALL_DAYS);
        $this->assertEquals(62, MenuItem::WEEKDAYS);
        $this->assertEquals(65, MenuItem::WEEKENDS);
    }

    public function test_factory_weekdays_only_state(): void
    {
        $item = MenuItem::factory()->weekdaysOnly()->create();

        $this->assertEquals(MenuItem::WEEKDAYS, $item->available_days);
    }

    public function test_factory_weekends_only_state(): void
    {
        $item = MenuItem::factory()->weekendsOnly()->create();

        $this->assertEquals(MenuItem::WEEKENDS, $item->available_days);
    }

    // hasAllergenがアレルゲン設定済みの場合にtrueを返す
    public function test_has_allergen_returns_true_when_allergen_is_set(): void
    {
        $item = MenuItem::factory()->create([
            'allergens' => Allergen::Shrimp->value | Allergen::Egg->value,
        ]);

        $this->assertTrue($item->hasAllergen(Allergen::Shrimp));
        $this->assertTrue($item->hasAllergen(Allergen::Egg));
    }

    // hasAllergenがアレルゲン未設定の場合にfalseを返す
    public function test_has_allergen_returns_false_when_allergen_is_not_set(): void
    {
        $item = MenuItem::factory()->create([
            'allergens' => Allergen::Shrimp->value,
        ]);

        $this->assertFalse($item->hasAllergen(Allergen::Wheat));
        $this->assertFalse($item->hasAllergen(Allergen::Milk));
    }

    // getAllergenLabelsが日本語ラベル配列を返す
    public function test_get_allergen_labels_returns_japanese_labels(): void
    {
        $item = MenuItem::factory()->create([
            'allergens' => Allergen::Shrimp->value | Allergen::Wheat->value | Allergen::Egg->value,
        ]);

        $labels = $item->getAllergenLabels();

        $this->assertEquals(['えび', '小麦', '卵'], $labels);
    }

    // getAdvisoryLabelsが日本語ラベル配列を返す
    public function test_get_advisory_labels_returns_japanese_labels(): void
    {
        $item = MenuItem::factory()->create([
            'allergen_advisories' => AllergenAdvisory::Soybean->value | AllergenAdvisory::Pork->value,
        ]);

        $labels = $item->getAdvisoryLabels();

        $this->assertEquals(['大豆', '豚肉'], $labels);
    }

    // hasAnyAllergenInfoがアレルゲン設定時にtrueを返す
    public function test_has_any_allergen_info_returns_true_when_allergens_set(): void
    {
        $item = MenuItem::factory()->create([
            'allergens' => Allergen::Shrimp->value,
        ]);

        $this->assertTrue($item->hasAnyAllergenInfo());
    }

    // hasAnyAllergenInfoが未設定時にfalseを返す
    public function test_has_any_allergen_info_returns_false_when_nothing_set(): void
    {
        $item = MenuItem::factory()->create([
            'allergens' => 0,
            'allergen_advisories' => 0,
            'allergen_note' => null,
        ]);

        $this->assertFalse($item->hasAnyAllergenInfo());
    }

    // nutrition_infoアクセサが配列を返す
    public function test_nutrition_info_accessor_returns_array(): void
    {
        $nutritionData = [
            'energy' => 350.0,
            'protein' => 12.5,
            'fat' => 15.0,
            'carbohydrate' => 42.0,
            'salt' => 1.8,
        ];

        $item = MenuItem::factory()->create([
            'nutrition_info' => $nutritionData,
        ]);

        $item->refresh();
        $this->assertIsArray($item->nutrition_info);
        $this->assertEquals(350.0, $item->nutrition_info['energy']);
        $this->assertEquals(12.5, $item->nutrition_info['protein']);
    }

    // nutrition_infoアクセサが未設定時にnullを返す
    public function test_nutrition_info_accessor_returns_null_when_not_set(): void
    {
        $item = MenuItem::factory()->create([
            'nutrition_info' => null,
        ]);

        $item->refresh();
        $this->assertNull($item->nutrition_info);
    }

    // allergensがintegerにキャストされる
    public function test_casts_allergens_to_integer(): void
    {
        $item = MenuItem::factory()->create(['allergens' => '41']);

        $this->assertIsInt($item->allergens);
        $this->assertEquals(41, $item->allergens);
    }

    // allergen_advisoriesがintegerにキャストされる
    public function test_casts_allergen_advisories_to_integer(): void
    {
        $item = MenuItem::factory()->create(['allergen_advisories' => '2048']);

        $this->assertIsInt($item->allergen_advisories);
        $this->assertEquals(2048, $item->allergen_advisories);
    }
}
