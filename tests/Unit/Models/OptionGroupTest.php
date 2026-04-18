<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\MenuItem;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class OptionGroupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->clear();
    }

    public function test_option_group_can_be_created(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        $group = OptionGroup::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'サイズ',
        ]);

        $this->assertDatabaseHas('option_groups', [
            'tenant_id' => $tenant->id,
            'name' => 'サイズ',
        ]);
    }

    public function test_tenant_scope_is_applied(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        OptionGroup::factory()->create(['tenant_id' => $tenant1->id]);
        OptionGroup::factory()->create(['tenant_id' => $tenant2->id]);

        app(TenantContext::class)->setTenant($tenant1->id);
        $groups = OptionGroup::all();

        $this->assertCount(1, $groups);
        $this->assertEquals($tenant1->id, $groups->first()->tenant_id);
    }

    public function test_active_scope_filters_active_groups(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        OptionGroup::factory()->create(['tenant_id' => $tenant->id, 'is_active' => true]);
        OptionGroup::factory()->create(['tenant_id' => $tenant->id, 'is_active' => false]);

        $activeGroups = OptionGroup::active()->get();

        $this->assertCount(1, $activeGroups);
        $this->assertTrue($activeGroups->first()->is_active);
    }

    public function test_ordered_scope_sorts_by_sort_order(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        OptionGroup::factory()->create(['tenant_id' => $tenant->id, 'sort_order' => 30]);
        OptionGroup::factory()->create(['tenant_id' => $tenant->id, 'sort_order' => 10]);
        OptionGroup::factory()->create(['tenant_id' => $tenant->id, 'sort_order' => 20]);

        $groups = OptionGroup::ordered()->get();

        $this->assertEquals(10, $groups[0]->sort_order);
        $this->assertEquals(20, $groups[1]->sort_order);
        $this->assertEquals(30, $groups[2]->sort_order);
    }

    public function test_belongs_to_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $group = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertInstanceOf(Tenant::class, $group->tenant);
        $this->assertEquals($tenant->id, $group->tenant->id);
    }

    public function test_tenant_id_is_automatically_set(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        $group = OptionGroup::create([
            'name' => 'Auto Tenant Group',
            'required' => false,
            'min_select' => 0,
            'max_select' => 1,
            'sort_order' => 10,
        ]);

        $this->assertEquals($tenant->id, $group->tenant_id);
    }

    public function test_has_many_options(): void
    {
        $tenant = Tenant::factory()->create();
        $group = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);

        Option::factory()->create(['option_group_id' => $group->id]);
        Option::factory()->create(['option_group_id' => $group->id]);

        $this->assertCount(2, $group->options);
        $this->assertInstanceOf(Option::class, $group->options->first());
    }

    public function test_belongs_to_many_menu_items(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        $group = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);
        $menuItem1 = MenuItem::factory()->create(['tenant_id' => $tenant->id]);
        $menuItem2 = MenuItem::factory()->create(['tenant_id' => $tenant->id]);

        $menuItem1->optionGroups()->attach($group->id, ['sort_order' => 10]);
        $menuItem2->optionGroups()->attach($group->id, ['sort_order' => 20]);

        $this->assertCount(2, $group->menuItems);
    }

    public function test_casts_required_to_boolean(): void
    {
        $group = OptionGroup::factory()->create(['required' => 1]);

        $this->assertIsBool($group->required);
        $this->assertTrue($group->required);
    }

    public function test_casts_is_active_to_boolean(): void
    {
        $group = OptionGroup::factory()->create(['is_active' => 1]);

        $this->assertIsBool($group->is_active);
        $this->assertTrue($group->is_active);
    }

    public function test_casts_min_select_to_integer(): void
    {
        $group = OptionGroup::factory()->create(['min_select' => '2']);

        $this->assertIsInt($group->min_select);
        $this->assertEquals(2, $group->min_select);
    }

    public function test_casts_max_select_to_integer(): void
    {
        $group = OptionGroup::factory()->create(['max_select' => '3']);

        $this->assertIsInt($group->max_select);
        $this->assertEquals(3, $group->max_select);
    }

    public function test_validate_option_selection_passes_for_valid_selection(): void
    {
        $group = OptionGroup::factory()->create([
            'required' => true,
            'min_select' => 1,
            'max_select' => 2,
        ]);

        $option1 = Option::factory()->create(['option_group_id' => $group->id, 'is_active' => true]);
        $option2 = Option::factory()->create(['option_group_id' => $group->id, 'is_active' => true]);

        $result = $group->validateOptionSelection([$option1->id, $option2->id]);

        $this->assertTrue($result);
    }

    public function test_validate_option_selection_fails_for_required_group_with_no_selection(): void
    {
        $group = OptionGroup::factory()->create([
            'required' => true,
            'min_select' => 1,
            'max_select' => 1,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $group->validateOptionSelection([]);
    }

    public function test_validate_option_selection_fails_when_below_min_select(): void
    {
        $group = OptionGroup::factory()->create([
            'required' => false,
            'min_select' => 2,
            'max_select' => 3,
        ]);

        $option1 = Option::factory()->create(['option_group_id' => $group->id, 'is_active' => true]);

        $this->expectException(InvalidArgumentException::class);

        $group->validateOptionSelection([$option1->id]);
    }

    public function test_validate_option_selection_fails_when_above_max_select(): void
    {
        $group = OptionGroup::factory()->create([
            'required' => false,
            'min_select' => 0,
            'max_select' => 2,
        ]);

        $option1 = Option::factory()->create(['option_group_id' => $group->id, 'is_active' => true]);
        $option2 = Option::factory()->create(['option_group_id' => $group->id, 'is_active' => true]);
        $option3 = Option::factory()->create(['option_group_id' => $group->id, 'is_active' => true]);

        $this->expectException(InvalidArgumentException::class);

        $group->validateOptionSelection([$option1->id, $option2->id, $option3->id]);
    }

    public function test_validate_option_selection_fails_for_invalid_option_ids(): void
    {
        $group = OptionGroup::factory()->create([
            'required' => false,
            'min_select' => 0,
            'max_select' => 2,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $group->validateOptionSelection([99999]);
    }

    public function test_validate_option_selection_passes_for_optional_group_with_no_selection(): void
    {
        $group = OptionGroup::factory()->create([
            'required' => false,
            'min_select' => 0,
            'max_select' => 3,
        ]);

        $result = $group->validateOptionSelection([]);

        $this->assertTrue($result);
    }

    public function test_factory_inactive_state(): void
    {
        $group = OptionGroup::factory()->inactive()->create();

        $this->assertFalse($group->is_active);
    }

    public function test_factory_required_state(): void
    {
        $group = OptionGroup::factory()->required()->create();

        $this->assertTrue($group->required);
        $this->assertEquals(1, $group->min_select);
    }

    public function test_factory_optional_state(): void
    {
        $group = OptionGroup::factory()->optional()->create();

        $this->assertFalse($group->required);
        $this->assertEquals(0, $group->min_select);
    }

    public function test_factory_multiple_select_state(): void
    {
        $group = OptionGroup::factory()->multipleSelect(1, 5)->create();

        $this->assertEquals(1, $group->min_select);
        $this->assertEquals(5, $group->max_select);
    }

    public function test_factory_sort_order_state(): void
    {
        $group = OptionGroup::factory()->sortOrder(99)->create();

        $this->assertEquals(99, $group->sort_order);
    }
}
