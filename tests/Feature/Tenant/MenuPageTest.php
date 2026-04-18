<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\OptionGroup;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class MenuPageTest extends TestCase
{
    use RefreshDatabase;

    private User $tenantAdmin;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'is_approved' => true,
        ]);

        $this->tenantAdmin = User::factory()->create([
            'role' => 'tenant_admin',
        ]);
        TenantUser::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->tenantAdmin->id,
            'role' => 'admin',
        ]);
    }

    public function test_categories_page_can_be_rendered(): void
    {
        $category = MenuCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.menu.categories.page'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Tenant/Menu/Categories/Index')
                ->has('categories')
        );
    }

    public function test_items_index_page_can_be_rendered(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.menu.items.page'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Tenant/Menu/Items/Index')
                ->has('items')
                ->has('categories')
        );
    }

    public function test_items_create_page_can_be_rendered(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.menu.items.create'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Tenant/Menu/Items/Create')
                ->has('categories')
                ->has('optionGroups')
        );
    }

    public function test_items_edit_page_can_be_rendered(): void
    {
        $item = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.menu.items.edit', ['item' => $item->id]));

        $response->assertStatus(200);
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Tenant/Menu/Items/Edit')
                ->has('item')
                ->has('categories')
                ->has('optionGroups')
        );
    }

    public function test_cannot_open_other_tenant_item_edit_page(): void
    {
        $otherTenant = Tenant::factory()->create([
            'is_approved' => true,
        ]);
        $otherTenantItem = MenuItem::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.menu.items.edit', ['item' => $otherTenantItem->id]));

        $response->assertStatus(404);
    }

    public function test_option_groups_index_page_can_be_rendered(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.menu.option-groups.page'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Tenant/Menu/OptionGroups/Index')
                ->has('optionGroups')
        );
    }

    public function test_option_groups_create_page_can_be_rendered(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.menu.option-groups.create'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Tenant/Menu/OptionGroups/Create')
        );
    }

    public function test_option_group_created_via_api_can_be_opened_in_edit_page(): void
    {
        $createResponse = $this->actingAs($this->tenantAdmin)
            ->postJson(route('tenant.option-groups.store'), [
                'name' => 'サイズ',
                'required' => true,
                'min_select' => 1,
                'max_select' => 1,
                'is_active' => true,
            ]);

        $createResponse->assertCreated();

        $optionGroupId = $createResponse->json('data.id');
        $this->assertIsInt($optionGroupId);

        $response = $this->actingAs($this->tenantAdmin, 'web')
            ->get(route('tenant.menu.option-groups.edit', ['optionGroup' => $optionGroupId]));

        $response->assertStatus(200);
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Tenant/Menu/OptionGroups/Edit')
                ->has('optionGroup')
        );
    }

    public function test_option_groups_edit_page_can_be_rendered(): void
    {
        $optionGroup = OptionGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.menu.option-groups.edit', ['optionGroup' => $optionGroup->id]));

        $response->assertStatus(200);
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Tenant/Menu/OptionGroups/Edit')
                ->has('optionGroup')
        );
    }

    public function test_cannot_open_other_tenant_option_group_edit_page(): void
    {
        $otherTenant = Tenant::factory()->create([
            'is_approved' => true,
        ]);
        $otherOptionGroup = OptionGroup::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.menu.option-groups.edit', ['optionGroup' => $otherOptionGroup->id]));

        $response->assertStatus(404);
    }

    public function test_guest_cannot_access_menu_pages(): void
    {
        $this->get(route('tenant.menu.categories.page'))
            ->assertRedirect(route('login'));

        $this->get(route('tenant.menu.items.page'))
            ->assertRedirect(route('login'));

        $this->get(route('tenant.menu.option-groups.page'))
            ->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_menu_pages(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $this->actingAs($customer)
            ->get(route('tenant.menu.categories.page'))
            ->assertStatus(403);
    }
}
