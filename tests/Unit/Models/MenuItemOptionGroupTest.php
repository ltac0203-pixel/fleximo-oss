<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\MenuItem;
use App\Models\OptionGroup;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuItemOptionGroupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->clear();
    }

    public function test_menu_item_can_have_option_groups(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        $menuItem = MenuItem::factory()->create(['tenant_id' => $tenant->id]);
        $group1 = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);
        $group2 = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);

        $menuItem->optionGroups()->attach([
            $group1->id => ['sort_order' => 10],
            $group2->id => ['sort_order' => 20],
        ]);

        $this->assertCount(2, $menuItem->optionGroups);
        $this->assertDatabaseHas('menu_item_option_groups', [
            'menu_item_id' => $menuItem->id,
            'option_group_id' => $group1->id,
            'sort_order' => 10,
        ]);
    }

    public function test_option_group_can_be_shared_by_multiple_menu_items(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        $group = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);
        $menuItem1 = MenuItem::factory()->create(['tenant_id' => $tenant->id]);
        $menuItem2 = MenuItem::factory()->create(['tenant_id' => $tenant->id]);

        $menuItem1->optionGroups()->attach($group->id, ['sort_order' => 10]);
        $menuItem2->optionGroups()->attach($group->id, ['sort_order' => 20]);

        $this->assertCount(2, $group->menuItems);
        $this->assertTrue($group->menuItems->contains($menuItem1));
        $this->assertTrue($group->menuItems->contains($menuItem2));
    }

    public function test_pivot_sort_order_is_saved(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        $menuItem = MenuItem::factory()->create(['tenant_id' => $tenant->id]);
        $group = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);

        $menuItem->optionGroups()->attach($group->id, ['sort_order' => 99]);

        $this->assertEquals(99, $menuItem->optionGroups()->first()->pivot->sort_order);
    }

    public function test_option_groups_are_sorted_by_pivot_sort_order(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        $menuItem = MenuItem::factory()->create(['tenant_id' => $tenant->id]);
        $group1 = OptionGroup::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Group1']);
        $group2 = OptionGroup::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Group2']);
        $group3 = OptionGroup::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Group3']);

        $menuItem->optionGroups()->attach([
            $group1->id => ['sort_order' => 30],
            $group2->id => ['sort_order' => 10],
            $group3->id => ['sort_order' => 20],
        ]);

        $optionGroups = $menuItem->optionGroups;

        $this->assertEquals('Group2', $optionGroups[0]->name);
        $this->assertEquals('Group3', $optionGroups[1]->name);
        $this->assertEquals('Group1', $optionGroups[2]->name);
    }

    public function test_detach_removes_association(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        $menuItem = MenuItem::factory()->create(['tenant_id' => $tenant->id]);
        $group = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);

        $menuItem->optionGroups()->attach($group->id, ['sort_order' => 10]);
        $this->assertCount(1, $menuItem->optionGroups);

        $menuItem->optionGroups()->detach($group->id);
        $menuItem->refresh();

        $this->assertCount(0, $menuItem->optionGroups);
        $this->assertDatabaseMissing('menu_item_option_groups', [
            'menu_item_id' => $menuItem->id,
            'option_group_id' => $group->id,
        ]);
    }

    public function test_cascade_delete_when_menu_item_is_deleted(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        $menuItem = MenuItem::factory()->create(['tenant_id' => $tenant->id]);
        $group = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);

        $menuItem->optionGroups()->attach($group->id, ['sort_order' => 10]);

        $menuItemId = $menuItem->id;
        $menuItem->delete();

        $this->assertDatabaseMissing('menu_item_option_groups', [
            'menu_item_id' => $menuItemId,
        ]);

        $this->assertDatabaseHas('option_groups', ['id' => $group->id]);
    }

    public function test_cascade_delete_when_option_group_is_deleted(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        $menuItem = MenuItem::factory()->create(['tenant_id' => $tenant->id]);
        $group = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);

        $menuItem->optionGroups()->attach($group->id, ['sort_order' => 10]);

        $groupId = $group->id;
        $group->delete();

        $this->assertDatabaseMissing('menu_item_option_groups', [
            'option_group_id' => $groupId,
        ]);

        $this->assertDatabaseHas('menu_items', ['id' => $menuItem->id]);
    }

    public function test_unique_constraint_prevents_duplicate_associations(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        $menuItem = MenuItem::factory()->create(['tenant_id' => $tenant->id]);
        $group = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);

        $menuItem->optionGroups()->attach($group->id, ['sort_order' => 10]);

        $this->expectException(QueryException::class);

        $menuItem->optionGroups()->attach($group->id, ['sort_order' => 20]);
    }

    public function test_sync_replaces_all_associations(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        $menuItem = MenuItem::factory()->create(['tenant_id' => $tenant->id]);
        $group1 = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);
        $group2 = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);
        $group3 = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);

        $menuItem->optionGroups()->attach([
            $group1->id => ['sort_order' => 10],
            $group2->id => ['sort_order' => 20],
        ]);

        $menuItem->optionGroups()->sync([
            $group2->id => ['sort_order' => 10],
            $group3->id => ['sort_order' => 20],
        ]);

        $menuItem->refresh();

        $this->assertCount(2, $menuItem->optionGroups);
        $this->assertFalse($menuItem->optionGroups->contains($group1));
        $this->assertTrue($menuItem->optionGroups->contains($group2));
        $this->assertTrue($menuItem->optionGroups->contains($group3));
    }
}
