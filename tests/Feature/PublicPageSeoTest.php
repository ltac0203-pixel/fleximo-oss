<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class PublicPageSeoTest extends TestCase
{
    public function test_tenant_application_complete_page_is_noindex(): void
    {
        $response = $this->get('/tenant-application/complete');

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('TenantApplication/Complete')
                ->where('seo.noindex', true)
        );
        $response->assertSee('content="noindex,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1"', false);
    }
}
