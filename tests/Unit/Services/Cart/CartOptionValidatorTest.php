<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cart;

use App\Exceptions\InvalidOptionSelectionException;
use App\Models\MenuItem;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Tenant;
use App\Services\Cart\CartOptionValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartOptionValidatorTest extends TestCase
{
    use RefreshDatabase;

    private CartOptionValidator $validator;

    private Tenant $tenant;

    private MenuItem $menuItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new CartOptionValidator;
        $this->tenant = Tenant::factory()->create(['is_active' => true]);
        $this->menuItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'is_sold_out' => false,
        ]);
    }

    public function test_有効なオプションは検証を通過(): void
    {
        $optionGroup = OptionGroup::factory()->optional()->create([
            'tenant_id' => $this->tenant->id,
            'max_select' => 2,
        ]);
        $option = Option::factory()->create(['option_group_id' => $optionGroup->id]);
        $this->menuItem->optionGroups()->attach($optionGroup->id);
        $this->menuItem->load('optionGroups.options');

        $this->validator->validateOptionsForMenuItem($this->menuItem, [$option->id]);

        $this->assertTrue(true);
    }

    public function test_商品に紐付いていないオプションは例外をスロー(): void
    {
        $otherOptionGroup = OptionGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $otherOption = Option::factory()->create(['option_group_id' => $otherOptionGroup->id]);

        $this->menuItem->load('optionGroups.options');

        $this->expectException(InvalidOptionSelectionException::class);

        $this->validator->validateOptionsForMenuItem($this->menuItem, [$otherOption->id]);
    }

    public function test_必須オプション未選択で例外をスロー(): void
    {
        $optionGroup = OptionGroup::factory()->required()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        Option::factory()->create(['option_group_id' => $optionGroup->id]);
        $this->menuItem->optionGroups()->attach($optionGroup->id);
        $this->menuItem->load('optionGroups.options');

        $this->expectException(InvalidOptionSelectionException::class);

        $this->validator->validateOptionsForMenuItem($this->menuItem, []);
    }

    public function test_オプション選択数超過で例外をスロー(): void
    {
        $optionGroup = OptionGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'max_select' => 1,
            'min_select' => 0,
        ]);
        $option1 = Option::factory()->create(['option_group_id' => $optionGroup->id]);
        $option2 = Option::factory()->create(['option_group_id' => $optionGroup->id]);
        $this->menuItem->optionGroups()->attach($optionGroup->id);
        $this->menuItem->load('optionGroups.options');

        $this->expectException(InvalidOptionSelectionException::class);

        $this->validator->validateOptionsForMenuItem($this->menuItem, [$option1->id, $option2->id]);
    }

    public function test_オプションなし商品で空配列は検証を通過(): void
    {
        $this->menuItem->load('optionGroups.options');

        $this->validator->validateOptionsForMenuItem($this->menuItem, []);

        $this->assertTrue(true);
    }
}
