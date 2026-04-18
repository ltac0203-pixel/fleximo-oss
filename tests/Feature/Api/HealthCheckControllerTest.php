<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthCheckControllerTest extends TestCase
{
    public function test_health_check_returns_healthy_status(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'healthy',
            ])
            ->assertJsonStructure([
                'status',
            ])
            ->assertJsonMissingPath('checks');
    }

    public function test_health_check_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk();
    }

    public function test_health_check_returns_unhealthy_when_database_fails(): void
    {
        // DBコネクションを無効な設定で上書き
        DB::purge('mysql');
        config(['database.connections.mysql.host' => '127.0.0.1']);
        config(['database.connections.mysql.port' => 59999]);
        config(['database.connections.mysql.database' => 'nonexistent_db']);

        $response = $this->getJson('/api/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
            ])
            ->assertJsonMissingPath('checks');
    }

    public function test_health_check_returns_unhealthy_when_cache_fails(): void
    {
        Cache::shouldReceive('put')->once()->andThrow(new \RuntimeException('Cache unavailable'));

        $response = $this->getJson('/api/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
            ])
            ->assertJsonMissingPath('checks');
    }
}
