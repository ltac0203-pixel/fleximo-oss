<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->clear();
    }

    public function test_option_can_be_created(): void
    {
        $tenant = Tenant::factory()->create();
        $group = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);

        $option = Option::factory()->create([
            'option_group_id' => $group->id,
            'name' => 'M',
            'price' => 50,
        ]);

        $this->assertDatabaseHas('options', [
            'option_group_id' => $group->id,
            'name' => 'M',
            'price' => 50,
        ]);
    }

    public function test_belongs_to_option_group(): void
    {
        $tenant = Tenant::factory()->create();
        $group = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);
        $option = Option::factory()->create(['option_group_id' => $group->id]);

        $this->assertInstanceOf(OptionGroup::class, $option->optionGroup);
        $this->assertEquals($group->id, $option->optionGroup->id);
    }

    public function test_active_scope_filters_active_options(): void
    {
        $tenant = Tenant::factory()->create();
        $group = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);

        Option::factory()->create(['option_group_id' => $group->id, 'is_active' => true]);
        Option::factory()->create(['option_group_id' => $group->id, 'is_active' => false]);

        $activeOptions = Option::active()->get();

        $this->assertCount(1, $activeOptions);
        $this->assertTrue($activeOptions->first()->is_active);
    }

    public function test_ordered_scope_sorts_by_sort_order(): void
    {
        $tenant = Tenant::factory()->create();
        $group = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);

        Option::factory()->create(['option_group_id' => $group->id, 'sort_order' => 30]);
        Option::factory()->create(['option_group_id' => $group->id, 'sort_order' => 10]);
        Option::factory()->create(['option_group_id' => $group->id, 'sort_order' => 20]);

        $options = Option::ordered()->get();

        $this->assertEquals(10, $options[0]->sort_order);
        $this->assertEquals(20, $options[1]->sort_order);
        $this->assertEquals(30, $options[2]->sort_order);
    }

    public function test_casts_is_active_to_boolean(): void
    {
        $option = Option::factory()->create(['is_active' => 1]);

        $this->assertIsBool($option->is_active);
        $this->assertTrue($option->is_active);
    }

    public function test_casts_price_to_integer(): void
    {
        $option = Option::factory()->create(['price' => '100']);

        $this->assertIsInt($option->price);
        $this->assertEquals(100, $option->price);
    }

    public function test_casts_sort_order_to_integer(): void
    {
        $option = Option::factory()->create(['sort_order' => '25']);

        $this->assertIsInt($option->sort_order);
        $this->assertEquals(25, $option->sort_order);
    }

    public function test_cascade_delete_when_option_group_is_deleted(): void
    {
        $tenant = Tenant::factory()->create();
        $group = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);

        $option1 = Option::factory()->create(['option_group_id' => $group->id]);
        $option2 = Option::factory()->create(['option_group_id' => $group->id]);

        $group->delete();

        $this->assertDatabaseMissing('options', ['id' => $option1->id]);
        $this->assertDatabaseMissing('options', ['id' => $option2->id]);
    }

    public function test_factory_inactive_state(): void
    {
        $option = Option::factory()->inactive()->create();

        $this->assertFalse($option->is_active);
    }

    public function test_factory_price_state(): void
    {
        $option = Option::factory()->price(250)->create();

        $this->assertEquals(250, $option->price);
    }

    public function test_factory_sort_order_state(): void
    {
        $option = Option::factory()->sortOrder(99)->create();

        $this->assertEquals(99, $option->sort_order);
    }

    public function test_factory_free_state(): void
    {
        $option = Option::factory()->free()->create();

        $this->assertEquals(0, $option->price);
    }

    public function test_option_has_belongs_to_tenant_trait(): void
    {
        $tenant = Tenant::factory()->create();
        $group = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);
        $option = Option::factory()->create(['option_group_id' => $group->id]);

        $this->assertNotNull($option->tenant_id);
        $this->assertInstanceOf(Tenant::class, $option->tenant);
        $this->assertEquals($tenant->id, $option->tenant_id);
    }

    public function test_tenant_scope_filters_options_by_tenant(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $group1 = OptionGroup::factory()->create(['tenant_id' => $tenant1->id]);
        $group2 = OptionGroup::factory()->create(['tenant_id' => $tenant2->id]);

        $option1 = Option::factory()->create(['option_group_id' => $group1->id]);
        $option2 = Option::factory()->create(['option_group_id' => $group2->id]);

        app(TenantContext::class)->setTenant($tenant1->id);

        $options = Option::all();

        $this->assertCount(1, $options);
        $this->assertEquals($option1->id, $options->first()->id);
    }

    public function test_option_tenant_id_matches_option_group_tenant_id(): void
    {
        $tenant = Tenant::factory()->create();
        $group = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);
        $option = Option::factory()->create(['option_group_id' => $group->id]);

        $this->assertEquals($group->tenant_id, $option->tenant_id);
    }

    public function test_cannot_access_other_tenant_option_directly(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $group = OptionGroup::factory()->create(['tenant_id' => $tenant1->id]);
        $option = Option::factory()->create(['option_group_id' => $group->id]);

        app(TenantContext::class)->setTenant($tenant2->id);

        $this->assertNull(Option::find($option->id));
    }

    public function test_cascade_delete_when_tenant_is_deleted(): void
    {
        $tenant = Tenant::factory()->create();
        $group = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);
        $option = Option::factory()->create(['option_group_id' => $group->id]);

        $optionId = $option->id;

        $tenant->delete();

        $this->assertDatabaseMissing('options', ['id' => $optionId]);
    }
}
