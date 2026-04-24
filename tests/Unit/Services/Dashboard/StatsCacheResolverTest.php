<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Dashboard;

use App\Services\Dashboard\StatsCacheResolver;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class StatsCacheResolverTest extends TestCase
{
    private StatsCacheResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-04-24 10:00:00'));
        $this->resolver = new StatsCacheResolver;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_remember_for_date_uses_realtime_ttl_when_date_is_today(): void
    {
        Cache::spy();

        $this->resolver->rememberForDate('key:today', Carbon::today(), fn () => 'value');

        Cache::shouldHaveReceived('remember')
            ->once()
            ->with('key:today', StatsCacheResolver::TTL_REALTIME, Mockery::type(Closure::class));
    }

    public function test_remember_for_date_uses_historical_ttl_when_date_is_past(): void
    {
        Cache::spy();

        $this->resolver->rememberForDate('key:past', Carbon::parse('2026-04-20'), fn () => 'value');

        Cache::shouldHaveReceived('remember')
            ->once()
            ->with('key:past', StatsCacheResolver::TTL_HISTORICAL, Mockery::type(Closure::class));
    }

    public function test_remember_for_date_range_uses_realtime_ttl_when_range_includes_today(): void
    {
        Cache::spy();

        $this->resolver->rememberForDateRange(
            'key:range:includes-today',
            Carbon::parse('2026-04-20'),
            Carbon::parse('2026-04-30'),
            fn () => 'value'
        );

        Cache::shouldHaveReceived('remember')
            ->once()
            ->with('key:range:includes-today', StatsCacheResolver::TTL_REALTIME, Mockery::type(Closure::class));
    }

    public function test_remember_for_date_range_uses_historical_ttl_when_range_excludes_today(): void
    {
        Cache::spy();

        $this->resolver->rememberForDateRange(
            'key:range:past',
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
            fn () => 'value'
        );

        Cache::shouldHaveReceived('remember')
            ->once()
            ->with('key:range:past', StatsCacheResolver::TTL_HISTORICAL, Mockery::type(Closure::class));
    }

    public function test_remember_realtime_always_uses_realtime_ttl(): void
    {
        Cache::spy();

        $this->resolver->rememberRealtime('key:realtime', fn () => 'value');

        Cache::shouldHaveReceived('remember')
            ->once()
            ->with('key:realtime', StatsCacheResolver::TTL_REALTIME, Mockery::type(Closure::class));
    }

    public function test_ttl_constants_match_production_values(): void
    {
        // TTL 値はインフラ契約。誤った書き換えをテストで固定する。
        $this->assertSame(300, StatsCacheResolver::TTL_REALTIME);
        $this->assertSame(3600, StatsCacheResolver::TTL_HISTORICAL);
    }
}
