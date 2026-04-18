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

class ApiMenuQueryTest extends TestCase
{
    use QueryCountAssertions;
    use RefreshDatabase;

    private Tenant $tenant;

    private User $staff;

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

        $this->seedMenuData($this->tenant);
    }

    public function test_menu_query_count_does_not_increase_with_item_count(): void
    {
        $baseCount = $this->countQueries(function () {
            $this->actingAs($this->staff)
                ->getJson("/api/tenants/{$this->tenant->id}/menu")
                ->assertOk();
        });

        // さらに同量のデータを追加
        $this->seedMenuData($this->tenant);

        $newCount = $this->countQueries(function () {
            $this->actingAs($this->staff)
                ->getJson("/api/tenants/{$this->tenant->id}/menu")
                ->assertOk();
        });

        // N+1がなければクエリ数が大幅に増えないことを確認（2倍未満）
        $this->assertLessThan(
            $baseCount * 2,
            $newCount,
            "Query count should not double when data doubles. Base: {$baseCount}, New: {$newCount}"
        );
    }

    public function test_menu_does_not_query_options_per_group(): void
    {
        $this->assertTableQueryCountLessThan(3, 'options', function () {
            $this->actingAs($this->staff)
                ->getJson("/api/tenants/{$this->tenant->id}/menu")
                ->assertOk();
        });
    }

    private function seedMenuData(Tenant $tenant): void
    {
        $categories = MenuCategory::factory()
            ->count(3)
            ->create(['tenant_id' => $tenant->id]);

        $optionGroups = OptionGroup::factory()
            ->count(2)
            ->create(['tenant_id' => $tenant->id]);

        foreach ($optionGroups as $og) {
            Option::factory()
                ->count(3)
                ->create([
                    'option_group_id' => $og->id,
                    'tenant_id' => $tenant->id,
                ]);
        }

        foreach ($categories as $category) {
            $items = MenuItem::factory()
                ->count(5)
                ->create(['tenant_id' => $tenant->id]);

            foreach ($items as $item) {
                $item->categories()->attach($category->id);

                foreach ($optionGroups as $og) {
                    $item->optionGroups()->attach($og->id);
                }
            }
        }
    }
}
