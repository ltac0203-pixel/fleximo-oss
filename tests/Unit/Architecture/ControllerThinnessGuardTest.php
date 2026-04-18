<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Tests\TestCase;

class ControllerThinnessGuardTest extends TestCase
{
    public function test_target_controllers_do_not_issue_direct_eloquent_queries(): void
    {
        $targetControllers = [
            'app/Http/Controllers/Api/Customer/CartController.php' => ['Cart'],
            'app/Http/Controllers/Webhook/FincodeWebhookController.php' => ['Tenant'],
            'app/Http/Controllers/Tenant/TenantMenuController.php' => ['MenuCategory', 'MenuItem', 'OptionGroup'],
        ];

        $queryMethods = '(query|where|find|findOrFail|first|firstOrCreate|create|update|delete|with|withCount|get|paginate|max|min|sum|count|latest|oldest)';

        foreach ($targetControllers as $file => $models) {
            $source = file_get_contents(base_path($file));

            $this->assertNotFalse($source, "Failed to read {$file}.");

            foreach ($models as $model) {
                $pattern = '/\b'.preg_quote($model, '/')."::{$queryMethods}\\s*\\(/";
                $this->assertDoesNotMatchRegularExpression(
                    $pattern,
                    $source,
                    "{$file} must not call {$model} static query methods directly."
                );
            }
        }
    }
}
