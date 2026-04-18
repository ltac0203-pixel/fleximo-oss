<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\MenuCategory;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->clear();
    }

    public function test_menu_category_can_be_created(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        $category = MenuCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'ドリンク',
        ]);

        $this->assertDatabaseHas('menu_categories', [
            'tenant_id' => $tenant->id,
            'name' => 'ドリンク',
        ]);
    }

    public function test_tenant_scope_is_applied(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        MenuCategory::factory()->create(['tenant_id' => $tenant1->id]);
        MenuCategory::factory()->create(['tenant_id' => $tenant2->id]);

        app(TenantContext::class)->setTenant($tenant1->id);
        $categories = MenuCategory::all();

        $this->assertCount(1, $categories);
        $this->assertEquals($tenant1->id, $categories->first()->tenant_id);
    }

    public function test_active_scope_filters_active_categories(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        MenuCategory::factory()->create(['tenant_id' => $tenant->id, 'is_active' => true]);
        MenuCategory::factory()->create(['tenant_id' => $tenant->id, 'is_active' => false]);

        $activeCategories = MenuCategory::active()->get();

        $this->assertCount(1, $activeCategories);
        $this->assertTrue($activeCategories->first()->is_active);
    }

    public function test_ordered_scope_sorts_by_sort_order(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        MenuCategory::factory()->create(['tenant_id' => $tenant->id, 'sort_order' => 30]);
        MenuCategory::factory()->create(['tenant_id' => $tenant->id, 'sort_order' => 10]);
        MenuCategory::factory()->create(['tenant_id' => $tenant->id, 'sort_order' => 20]);

        $categories = MenuCategory::ordered()->get();

        $this->assertEquals(10, $categories[0]->sort_order);
        $this->assertEquals(20, $categories[1]->sort_order);
        $this->assertEquals(30, $categories[2]->sort_order);
    }

    public function test_active_and_ordered_scopes_can_be_combined(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        MenuCategory::factory()->create(['tenant_id' => $tenant->id, 'sort_order' => 30, 'is_active' => true]);
        MenuCategory::factory()->create(['tenant_id' => $tenant->id, 'sort_order' => 10, 'is_active' => false]);
        MenuCategory::factory()->create(['tenant_id' => $tenant->id, 'sort_order' => 20, 'is_active' => true]);

        $categories = MenuCategory::active()->ordered()->get();

        $this->assertCount(2, $categories);
        $this->assertEquals(20, $categories[0]->sort_order);
        $this->assertEquals(30, $categories[1]->sort_order);
    }

    public function test_belongs_to_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $category = MenuCategory::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertInstanceOf(Tenant::class, $category->tenant);
        $this->assertEquals($tenant->id, $category->tenant->id);
    }

    public function test_tenant_id_is_automatically_set(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        $category = MenuCategory::create([
            'name' => 'Auto Tenant Category',
            'sort_order' => 10,
        ]);

        $this->assertEquals($tenant->id, $category->tenant_id);
    }

    public function test_casts_is_active_to_boolean(): void
    {
        $category = MenuCategory::factory()->create(['is_active' => 1]);

        $this->assertIsBool($category->is_active);
        $this->assertTrue($category->is_active);
    }

    public function test_casts_sort_order_to_integer(): void
    {
        $category = MenuCategory::factory()->create(['sort_order' => '25']);

        $this->assertIsInt($category->sort_order);
        $this->assertEquals(25, $category->sort_order);
    }
}
