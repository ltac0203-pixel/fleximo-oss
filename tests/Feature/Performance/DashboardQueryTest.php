<?php

declare(strict_types=1);

namespace Tests\Feature\Performance;

use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\QueryCountAssertions;
use Tests\TestCase;

class DashboardQueryTest extends TestCase
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

        // 100注文を作成（order_codeのユニーク制約回避のため連番指定）
        $today = Carbon::today()->format('Y-m-d');
        $customer = User::factory()->customer()->create();
        for ($i = 0; $i < 100; $i++) {
            Order::factory()
                ->forTenant($this->tenant)
                ->forUser($customer)
                ->completed()
                ->forBusinessDate($today)
                ->withOrderCode(sprintf('T%03d', $i))
                ->create();
        }
    }

    public function test_summary_query_count_does_not_increase_with_order_volume(): void
    {
        $this->assertQueryCountLessThan(30, function () {
            $this->actingAs($this->staff)
                ->getJson('/api/tenant/dashboard/summary')
                ->assertOk();
        });
    }
}
