<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Tenant;
use App\Models\WebhookLog;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WebhookLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->clear();
    }

    public function test_tenant_id_is_automatically_set_on_create(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant->id);

        $webhookLog = WebhookLog::create([
            'provider' => 'fincode',
            'fincode_id' => 'pay_auto_tenant_001',
            'event_type' => 'payment.completed',
            'payload' => [
                'id' => 'pay_auto_tenant_001',
                'event' => 'payment.completed',
            ],
        ]);

        $this->assertEquals($tenant->id, $webhookLog->tenant_id);
    }

    public function test_unprocessed_returns_only_current_tenant_records(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $tenant1Unprocessed = WebhookLog::create([
            'tenant_id' => $tenant1->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_tenant1_unprocessed_001',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_tenant1_unprocessed_001', 'event' => 'payment.completed'],
        ]);

        WebhookLog::create([
            'tenant_id' => $tenant1->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_tenant1_processed_001',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_tenant1_processed_001', 'event' => 'payment.completed'],
            'processed' => true,
            'processed_at' => now(),
        ]);

        WebhookLog::create([
            'tenant_id' => $tenant1->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_tenant1_failed_001',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_tenant1_failed_001', 'event' => 'payment.completed'],
            'error_message' => 'Failed to process',
        ]);

        WebhookLog::create([
            'tenant_id' => $tenant2->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_tenant2_unprocessed_001',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_tenant2_unprocessed_001', 'event' => 'payment.completed'],
        ]);

        app(TenantContext::class)->setTenant($tenant1->id);

        $unprocessedLogs = WebhookLog::unprocessed();

        $this->assertCount(1, $unprocessedLogs);
        $this->assertEquals($tenant1Unprocessed->id, $unprocessedLogs->first()->id);
        $this->assertTrue($unprocessedLogs->every(fn (WebhookLog $log): bool => $log->tenant_id === $tenant1->id));
    }

    public function test_unprocessed_without_tenant_context_returns_all_tenants(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $tenant1Unprocessed = WebhookLog::create([
            'tenant_id' => $tenant1->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_no_context_tenant1_001',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_no_context_tenant1_001', 'event' => 'payment.completed'],
        ]);

        $tenant2Unprocessed = WebhookLog::create([
            'tenant_id' => $tenant2->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_no_context_tenant2_001',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_no_context_tenant2_001', 'event' => 'payment.completed'],
        ]);

        WebhookLog::create([
            'tenant_id' => $tenant2->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_no_context_tenant2_processed_001',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_no_context_tenant2_processed_001', 'event' => 'payment.completed'],
            'processed' => true,
            'processed_at' => now(),
        ]);

        app(TenantContext::class)->clear();

        $unprocessedLogs = WebhookLog::unprocessed();

        $this->assertCount(2, $unprocessedLogs);
        $this->assertEqualsCanonicalizing(
            [$tenant1Unprocessed->id, $tenant2Unprocessed->id],
            $unprocessedLogs->pluck('id')->all()
        );
    }

    public function test_unprocessed_handles_large_dataset_with_single_select_query(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        WebhookLog::factory()->count(150)->create([
            'tenant_id' => $tenant1->id,
            'provider' => 'fincode',
            'event_type' => 'payment.completed',
            'processed' => false,
            'error_message' => null,
        ]);
        WebhookLog::factory()->count(150)->processed()->create([
            'tenant_id' => $tenant1->id,
            'provider' => 'fincode',
            'event_type' => 'payment.completed',
        ]);
        WebhookLog::factory()->count(150)->failed()->create([
            'tenant_id' => $tenant1->id,
            'provider' => 'fincode',
            'event_type' => 'payment.completed',
        ]);
        WebhookLog::factory()->count(150)->create([
            'tenant_id' => $tenant2->id,
            'provider' => 'fincode',
            'event_type' => 'payment.completed',
            'processed' => false,
            'error_message' => null,
        ]);

        app(TenantContext::class)->setTenant($tenant1->id);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $unprocessedLogs = WebhookLog::unprocessed();
        DB::disableQueryLog();

        $selectQueries = collect(DB::getQueryLog())
            ->filter(static fn (array $query): bool => str_starts_with(strtolower($query['query']), 'select'))
            ->count();

        $this->assertSame(1, $selectQueries);
        $this->assertCount(150, $unprocessedLogs);
        $this->assertTrue($unprocessedLogs->every(
            fn (WebhookLog $log): bool => $log->tenant_id === $tenant1->id
                && $log->processed === false
                && $log->error_message === null
        ));
        $this->assertSame(
            $unprocessedLogs->sortBy('created_at')->pluck('id')->all(),
            $unprocessedLogs->pluck('id')->all()
        );
    }
}
