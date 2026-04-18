<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\MetricType;
use App\Jobs\AggregateDailyAnalyticsJob;
use App\Models\AnalyticsCache;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AggregateDailyAnalyticsJobTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->customer()->create();
    }

    public function test_job_can_be_dispatched(): void
    {
        Queue::fake();

        $date = Carbon::today();

        AggregateDailyAnalyticsJob::dispatch($date);

        Queue::assertPushed(AggregateDailyAnalyticsJob::class, function ($job) use ($date) {
            return $job->date->toDateString() === $date->toDateString()
                && $job->tenantId === null;
        });
    }

    public function test_job_can_be_dispatched_for_specific_tenant(): void
    {
        Queue::fake();

        $date = Carbon::today();

        AggregateDailyAnalyticsJob::dispatch($date, $this->tenant->id);

        Queue::assertPushed(AggregateDailyAnalyticsJob::class, function ($job) use ($date) {
            return $job->date->toDateString() === $date->toDateString()
                && $job->tenantId === $this->tenant->id;
        });
    }

    public function test_job_aggregates_all_tenants_when_tenant_id_is_null(): void
    {
        Queue::fake();

        $date = Carbon::today();
        $tenant2 = Tenant::factory()->create();

        Order::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->paid()
            ->totalAmount(1000)
            ->create(['paid_at' => $date->copy()->setHour(10)]);

        Order::factory()
            ->forTenant($tenant2)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->paid()
            ->totalAmount(2000)
            ->create(['paid_at' => $date->copy()->setHour(11)]);

        $job = new AggregateDailyAnalyticsJob($date);
        $job->handle(app(AnalyticsService::class));

        Queue::assertPushed(AggregateDailyAnalyticsJob::class, 2);
        Queue::assertPushed(AggregateDailyAnalyticsJob::class, function ($job) use ($date) {
            return $job->tenantId === $this->tenant->id
                && $job->date->toDateString() === $date->toDateString();
        });
        Queue::assertPushed(AggregateDailyAnalyticsJob::class, function ($job) use ($date, $tenant2) {
            return $job->tenantId === $tenant2->id
                && $job->date->toDateString() === $date->toDateString();
        });

        $this->assertNull(AnalyticsCache::getCached($this->tenant->id, MetricType::DailySales, $date));
        $this->assertNull(AnalyticsCache::getCached($tenant2->id, MetricType::DailySales, $date));

        $platformSales = AnalyticsCache::getCached(null, MetricType::DailySales, $date);
        $this->assertNotNull($platformSales);
        $this->assertEquals(3000, $platformSales['total_sales']);
    }

    public function test_job_aggregates_specific_tenant_only(): void
    {
        $date = Carbon::today();
        $tenant2 = Tenant::factory()->create();

        Order::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->paid()
            ->totalAmount(1000)
            ->create(['paid_at' => $date->copy()->setHour(10)]);

        Order::factory()
            ->forTenant($tenant2)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->paid()
            ->totalAmount(2000)
            ->create(['paid_at' => $date->copy()->setHour(11)]);

        $job = new AggregateDailyAnalyticsJob($date, $this->tenant->id);
        $job->handle(app(AnalyticsService::class));

        $this->assertNotNull(AnalyticsCache::getCached($this->tenant->id, MetricType::DailySales, $date));
        $this->assertNull(AnalyticsCache::getCached($tenant2->id, MetricType::DailySales, $date));

        $this->assertNull(AnalyticsCache::getCached(null, MetricType::DailySales, $date));
    }

    public function test_job_has_correct_unique_id(): void
    {
        $date = Carbon::parse('2026-01-15');

        $job1 = new AggregateDailyAnalyticsJob($date);
        $this->assertEquals('daily_analytics_2026-01-15_all', $job1->uniqueId());

        $job2 = new AggregateDailyAnalyticsJob($date, 123);
        $this->assertEquals('daily_analytics_2026-01-15_tenant_123', $job2->uniqueId());
    }

    public function test_job_implements_should_be_unique(): void
    {
        $job = new AggregateDailyAnalyticsJob(Carbon::today());

        $this->assertInstanceOf(ShouldBeUnique::class, $job);
    }

    public function test_job_has_correct_tags(): void
    {
        $date = Carbon::parse('2026-01-15');

        $job1 = new AggregateDailyAnalyticsJob($date);
        $tags1 = $job1->tags();
        $this->assertContains('analytics', $tags1);
        $this->assertContains('daily', $tags1);
        $this->assertContains('date:2026-01-15', $tags1);
        $this->assertContains('platform', $tags1);

        $job2 = new AggregateDailyAnalyticsJob($date, 123);
        $tags2 = $job2->tags();
        $this->assertContains('tenant:123', $tags2);
        $this->assertNotContains('platform', $tags2);
    }

    public function test_job_has_correct_retry_and_timeout_settings(): void
    {
        $parentJob = new AggregateDailyAnalyticsJob(Carbon::today());
        $childJob = new AggregateDailyAnalyticsJob(Carbon::today(), tenantId: 1);

        $this->assertEquals(3, $parentJob->tries);
        $this->assertEquals([60, 300, 900], $parentJob->backoff);
        $this->assertEquals(300, $parentJob->timeout);
        $this->assertEquals(85, $childJob->timeout);
    }

    public function test_job_creates_all_metric_types(): void
    {
        $date = Carbon::today();

        Order::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->paid()
            ->totalAmount(1000)
            ->create(['paid_at' => $date->copy()->setHour(12)]);

        $job = new AggregateDailyAnalyticsJob($date, $this->tenant->id);
        $job->handle(app(AnalyticsService::class));

        $this->assertNotNull(AnalyticsCache::getCached($this->tenant->id, MetricType::DailySales, $date));
        $this->assertNotNull(AnalyticsCache::getCached($this->tenant->id, MetricType::DailyOrderStats, $date));
        $this->assertNotNull(AnalyticsCache::getCached($this->tenant->id, MetricType::HourlyDistribution, $date));
    }
}
