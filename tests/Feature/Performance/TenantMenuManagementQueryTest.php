<?php

declare(strict_types=1);

namespace Tests\Feature\Performance;

use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\QueryCountAssertions;
use Tests\TestCase;

class TenantMenuManagementQueryTest extends TestCase
{
    use QueryCountAssertions;
    use RefreshDatabase;

    private Tenant $tenant;

    private User $staff;

    private MenuItem $menuItem;

    private OptionGroup $optionGroup;

    private Option $option;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->tenant = Tenant::factory()->create();

        $this->staff = User::factory()->create([
            'role' => UserRole::TenantAdmin,
        ]);
        TenantUser::factory()->create([
            'user_id' => $this->staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Admin,
        ]);

        $this->setTenantAlwaysOpen($this->tenant);

        // メニューカテゴリ・アイテム・オプショングループ・オプションを作成
        $category = MenuCategory::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->menuItem = MenuItem::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->menuItem->categories()->attach($category->id);

        $this->optionGroup = OptionGroup::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->menuItem->optionGroups()->attach($this->optionGroup->id);

        $this->option = Option::factory()->create([
            'option_group_id' => $this->optionGroup->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_menu_item_update_does_not_lazy_load_categories(): void
    {
        $this->assertQueryCountLessThan(15, function () {
            $this->actingAs($this->staff)
                ->patchJson("/api/tenant/menu/items/{$this->menuItem->id}", [
                    'name' => '更新テスト',
                ])
                ->assertOk();
        });
    }

    public function test_option_update_does_not_lazy_load_option_group(): void
    {
        $this->assertQueryCountLessThan(15, function () {
            $this->actingAs($this->staff)
                ->patchJson("/api/tenant/option-groups/{$this->optionGroup->id}/options/{$this->option->id}", [
                    'name' => '更新',
                ])
                ->assertOk();
        });
    }
}
