<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate {--path= : Output path for sitemap.xml}';

    protected $description = 'Generate the sitemap.xml file';

    public function handle(): int
    {
        $sitemap = Sitemap::create();

        // 静的ページ
        $staticPages = [
            ['url' => '/', 'priority' => 1.0, 'changefreq' => 'weekly'],
            ['url' => '/for-business', 'priority' => 0.9, 'changefreq' => 'weekly'],
            ['url' => '/tenant-application', 'priority' => 0.8, 'changefreq' => 'monthly'],
            ['url' => '/legal/terms', 'priority' => 0.5, 'changefreq' => 'yearly'],
            ['url' => '/legal/privacy-policy', 'priority' => 0.5, 'changefreq' => 'yearly'],
            ['url' => '/legal/transactions', 'priority' => 0.5, 'changefreq' => 'yearly'],
            ['url' => '/legal/tenant-terms', 'priority' => 0.5, 'changefreq' => 'yearly'],
        ];

        foreach ($staticPages as $page) {
            $sitemap->add(
                Url::create($page['url'])
                    ->setPriority($page['priority'])
                    ->setChangeFrequency($page['changefreq'])
            );
        }

        // 動的ページ(承認済みテナントのメニューページ)
        $tenants = Tenant::where('status', TenantStatus::Active)
            ->where('is_active', true)
            ->where('is_approved', true)
            ->get();

        foreach ($tenants as $tenant) {
            $sitemap->add(
                Url::create("/order/tenant/{$tenant->slug}/menu")
                    ->setPriority(0.6)
                    ->setChangeFrequency('daily')
                    ->setLastModificationDate($tenant->updated_at)
            );
        }

        $outputPath = $this->option('path');

        $sitemap->writeToFile(
            is_string($outputPath) && $outputPath !== ''
                ? $outputPath
                : public_path('sitemap.xml')
        );

        $this->info('Sitemap generated successfully!');

        return Command::SUCCESS;
    }
}
