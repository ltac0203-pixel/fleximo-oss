<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapGenerateTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_generates_sitemap_for_active_approved_tenants_only(): void
    {
        Tenant::factory()->create([
            'slug' => 'active-approved',
            'status' => 'active',
            'is_active' => true,
            'is_approved' => true,
        ]);

        Tenant::factory()->create([
            'slug' => 'inactive-tenant',
            'status' => 'active',
            'is_active' => false,
            'is_approved' => true,
        ]);

        Tenant::factory()->create([
            'slug' => 'pending-tenant',
            'status' => 'pending',
            'is_active' => true,
            'is_approved' => false,
        ]);

        $outputPath = storage_path('framework/testing/generated-sitemap.xml');
        $outputDirectory = dirname($outputPath);

        if (! is_dir($outputDirectory)) {
            mkdir($outputDirectory, 0777, true);
        }

        if (file_exists($outputPath)) {
            unlink($outputPath);
        }

        $this->artisan('sitemap:generate', ['--path' => $outputPath])
            ->assertSuccessful();

        $this->assertFileExists($outputPath);

        $sitemapContents = file_get_contents($outputPath);

        self::assertIsString($sitemapContents);
        self::assertStringContainsString('/order/tenant/active-approved/menu', $sitemapContents);
        self::assertStringNotContainsString('/order/tenant/inactive-tenant/menu', $sitemapContents);
        self::assertStringNotContainsString('/order/tenant/pending-tenant/menu', $sitemapContents);

        unlink($outputPath);
    }
}
