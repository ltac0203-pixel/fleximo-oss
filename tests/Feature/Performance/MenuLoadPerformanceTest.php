<?php

declare(strict_types=1);

namespace Tests\Feature\Performance;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\QueryCountAssertions;
use Tests\TestCase;

class MenuLoadPerformanceTest extends TestCase
{
    use QueryCountAssertions;
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->tenant = Tenant::factory()->create(['status' => 'active', 'is_active' => true, 'is_approved' => true]);
        $this->user = User::factory()->customer()->create();
        $this->setTenantAlwaysOpen($this->tenant);

        // 10カテゴリ x 20メニュー x 3オプショングループ x 5オプション
        $categories = MenuCategory::factory(10)->create(['tenant_id' => $this->tenant->id]);

        $optionGroups = OptionGroup::factory(3)->create(['tenant_id' => $this->tenant->id]);
        foreach ($optionGroups as $group) {
            Option::factory(5)->create([
                'option_group_id' => $group->id,
                'tenant_id' => $this->tenant->id,
            ]);
        }

        foreach ($categories as $category) {
            $items = MenuItem::factory(20)->create(['tenant_id' => $this->tenant->id]);
            foreach ($items as $item) {
                $item->categories()->attach($category->id);
                $item->optionGroups()->attach($optionGroups->pluck('id'));
            }
        }
    }

    public function test_menu_responds_successfully_with_large_dataset(): void
    {
        $response = $this->actingAs($this->user)->getJson("/api/tenants/{$this->tenant->id}/menu");

        $response->assertStatus(200);
    }

    public function test_menu_query_count_with_large_dataset(): void
    {
        $this->assertQueryCountLessThan(20, function () {
            $this->actingAs($this->user)->getJson("/api/tenants/{$this->tenant->id}/menu");
        });
    }

    public function test_menu_cache_prevents_repeated_queries(): void
    {
        // 1回目のアクセスでキャッシュを作成
        $this->actingAs($this->user)->getJson("/api/tenants/{$this->tenant->id}/menu");

        // 2回目のアクセスではクエリ数が極小であること
        $queryCount = $this->countQueries(function () {
            $this->actingAs($this->user)->getJson("/api/tenants/{$this->tenant->id}/menu");
        });

        $this->assertLessThanOrEqual(3, $queryCount, "Expected 3 or fewer queries on cached request, but {$queryCount} were executed.");
    }
}
