<?php

declare(strict_types=1);

namespace App\Support\Seo;

use Illuminate\Support\Arr;

class PageSeoResolver
{
    public function resolve(string $pageKey, ?string $locale = null): array
    {
        $currentLocale = $locale ?? app()->getLocale();
        $pageConfig = config("seo.pages.{$pageKey}", []);

        if (! is_array($pageConfig)) {
            return [];
        }

        $defaultMetadata = Arr::get($pageConfig, 'default', []);
        $localizedMetadata = Arr::get($pageConfig, "locales.{$currentLocale}", []);

        if (! is_array($defaultMetadata) || ! is_array($localizedMetadata)) {
            return [];
        }

        return [...$defaultMetadata, ...$localizedMetadata];
    }
}
