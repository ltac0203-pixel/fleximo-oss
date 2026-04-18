<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\TestCase;

class HealthCheckIpRestrictionTest extends TestCase
{
    public function test_health_check_allows_access_when_no_ips_configured(): void
    {
        config(['app.health_check_allowed_ips' => '']);

        $response = $this->getJson('/api/health');

        $response->assertOk();
    }

    public function test_health_check_allows_access_from_whitelisted_ip(): void
    {
        config(['app.health_check_allowed_ips' => '127.0.0.1,10.0.0.1']);

        $response = $this->call('GET', '/api/health', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        $this->assertEquals(200, $response->status());
    }

    public function test_health_check_blocks_access_from_non_whitelisted_ip(): void
    {
        config(['app.health_check_allowed_ips' => '10.0.0.1,10.0.0.2']);

        $response = $this->call('GET', '/api/health', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
        ]);

        $this->assertEquals(403, $response->status());
    }

    public function test_health_check_allows_access_with_multiple_ips(): void
    {
        config(['app.health_check_allowed_ips' => '10.0.0.1, 10.0.0.2, 192.168.1.100']);

        $response = $this->call('GET', '/api/health', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.100',
        ]);

        $this->assertEquals(200, $response->status());
    }
}
