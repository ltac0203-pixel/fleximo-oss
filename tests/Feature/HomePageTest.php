<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_does_not_expose_framework_or_php_versions(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Welcome')
                ->missing('laravelVersion')
                ->missing('phpVersion')
        );
    }

    public function test_home_page_renders_server_side_seo_metadata(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<meta name="description"', false);
        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('"@type":"Organization"', false);
        $response->assertSee('"@type":"WebSite"', false);
    }
}
