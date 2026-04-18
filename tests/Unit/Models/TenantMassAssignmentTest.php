<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantMassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function platform_fee_rate_bps_cannot_be_mass_assigned(): void
    {
        $tenant = Tenant::factory()->create();

        $tenant->update(['platform_fee_rate_bps' => 100]);

        $tenant->refresh();
        $this->assertNotEquals(100, $tenant->platform_fee_rate_bps);
    }

    #[Test]
    public function fincode_shop_id_cannot_be_mass_assigned(): void
    {
        $tenant = Tenant::factory()->create();

        $tenant->update(['fincode_shop_id' => 'malicious_shop_id']);

        $tenant->refresh();
        $this->assertNotEquals('malicious_shop_id', $tenant->fincode_shop_id);
    }

    #[Test]
    public function allowed_fields_can_be_mass_assigned(): void
    {
        $tenant = Tenant::factory()->create();

        $tenant->update(['name' => 'Updated Name']);

        $tenant->refresh();
        $this->assertEquals('Updated Name', $tenant->name);
    }
}
