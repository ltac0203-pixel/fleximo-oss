<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Tests\TestCase;

class ControllerExceptionHandlingPolicyTest extends TestCase
{
    public function test_menu_category_controller_does_not_catch_category_has_items_exception(): void
    {
        $file = 'app/Http/Controllers/Tenant/MenuCategoryController.php';
        $source = file_get_contents(base_path($file));

        $this->assertNotFalse($source, "Failed to read {$file}.");
        $this->assertDoesNotMatchRegularExpression(
            '/catch\s*\(\s*CategoryHasItemsException\b/',
            $source,
            "{$file} should not catch CategoryHasItemsException directly."
        );
        $this->assertStringNotContainsString(
            '->render()',
            $source,
            "{$file} should not call exception render() directly."
        );
    }
}
