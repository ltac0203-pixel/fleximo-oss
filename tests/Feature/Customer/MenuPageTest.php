<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_menu_page_for_active_tenant(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Test Restaurant',
            'is_active' => true,
            'slug' => 'test-restaurant',
        ]);

        $response = $this->get('/order/tenant/test-restaurant/menu');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Customer/Tenant/Menu')
                ->has('tenant')
                ->has('menu')
                ->has('seo')
                ->has('structuredData', 2)
                ->where('seo.title', 'Test Restaurant メニュー')
                ->where('tenant.slug', 'test-restaurant')
        );
        $response->assertSee('"@type":"Restaurant"', false);
    }

    public function test_cannot_view_menu_page_for_inactive_tenant(): void
    {
        $tenant = Tenant::factory()->create([
            'is_active' => false,
            'slug' => 'inactive-restaurant',
        ]);

        $response = $this->get('/order/tenant/inactive-restaurant/menu');

        $response->assertStatus(404);
    }

    public function test_displays_menu_categories_and_items(): void
    {
        $tenant = Tenant::factory()->create([
            'is_active' => true,
            'slug' => 'food-restaurant',
        ]);

        $category = MenuCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'ドリンク',
            'is_active' => true,
        ]);

        $item = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'コーヒー',
            'price' => 300,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $item->categories()->attach($category->id);

        $response = $this->get('/order/tenant/food-restaurant/menu');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Customer/Tenant/Menu')
                ->has('menu.categories', 1)
                ->where('menu.categories.0.name', 'ドリンク')
                ->has('menu.categories.0.items', 1)
                ->where('menu.categories.0.items.0.name', 'コーヒー')
                ->where('menu.categories.0.items.0.price', 300)
        );
    }

    public function test_displays_sold_out_items(): void
    {
        $tenant = Tenant::factory()->create([
            'is_active' => true,
            'slug' => 'cafe',
        ]);

        $category = MenuCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        $item = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => '売り切れ商品',
            'is_active' => true,
            'is_sold_out' => true,
        ]);

        $item->categories()->attach($category->id);

        $response = $this->get('/order/tenant/cafe/menu');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->where('menu.categories.0.items.0.is_sold_out', true)
        );
    }

    public function test_does_not_display_inactive_categories(): void
    {
        $tenant = Tenant::factory()->create([
            'is_active' => true,
            'slug' => 'restaurant',
        ]);

        MenuCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'アクティブカテゴリ',
            'is_active' => true,
        ]);

        MenuCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => '非アクティブカテゴリ',
            'is_active' => false,
        ]);

        $response = $this->get('/order/tenant/restaurant/menu');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->has('menu.categories', 1)
                ->where('menu.categories.0.name', 'アクティブカテゴリ')
        );
    }

    public function test_returns_404_for_nonexistent_tenant(): void
    {
        $response = $this->get('/order/tenant/nonexistent-slug/menu');

        $response->assertStatus(404);
    }

    public function test_menu_page_does_not_require_authentication(): void
    {
        $tenant = Tenant::factory()->create([
            'is_active' => true,
            'slug' => 'public-restaurant',
        ]);

        $response = $this->get('/order/tenant/public-restaurant/menu');

        $response->assertStatus(200);
    }

    public function test_empty_menu_page_is_marked_noindex(): void
    {
        $tenant = Tenant::factory()->create([
            'is_active' => true,
            'slug' => 'empty-restaurant',
        ]);

        $response = $this->get('/order/tenant/empty-restaurant/menu');

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->where('seo.noindex', true)
        );
        $response->assertSee('content="noindex,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1"', false);
    }
}
