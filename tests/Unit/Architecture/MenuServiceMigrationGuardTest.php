<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Http\Controllers\Tenant\MenuCategoryController;
use App\Http\Controllers\Tenant\MenuItemController;
use App\Http\Controllers\Tenant\OptionController;
use App\Http\Controllers\Tenant\OptionGroupController;
use App\Services\Menu\CategoryService;
use App\Services\Menu\MenuItemService;
use App\Services\Menu\OptionGroupService;
use App\Services\Menu\OptionService;
use App\Services\MenuService;
use ReflectionClass;
use ReflectionNamedType;
use Tests\TestCase;

class MenuServiceMigrationGuardTest extends TestCase
{
    public function test_legacy_menu_service_is_removed(): void
    {
        $this->assertFileDoesNotExist(
            base_path('app/Services/MenuService.php'),
            'Legacy MenuService file should remain removed.'
        );

        $this->assertFalse(
            class_exists(MenuService::class),
            'Legacy App\\Services\\MenuService should not be resolvable.'
        );
    }

    public function test_tenant_menu_controllers_depend_on_split_menu_services(): void
    {
        $expectedDependencies = [
            MenuCategoryController::class => CategoryService::class,
            MenuItemController::class => MenuItemService::class,
            OptionGroupController::class => OptionGroupService::class,
            OptionController::class => OptionService::class,
        ];

        foreach ($expectedDependencies as $controllerClass => $serviceClass) {
            $reflection = new ReflectionClass($controllerClass);
            $constructor = $reflection->getConstructor();

            $this->assertNotNull(
                $constructor,
                "{$controllerClass} must declare a constructor."
            );

            $parameters = $constructor->getParameters();
            $this->assertCount(
                1,
                $parameters,
                "{$controllerClass} constructor should keep a single service dependency."
            );

            $type = $parameters[0]->getType();
            $this->assertInstanceOf(
                ReflectionNamedType::class,
                $type,
                "{$controllerClass} constructor dependency must be a named class type."
            );

            $this->assertSame(
                $serviceClass,
                $type->getName(),
                "{$controllerClass} must depend on {$serviceClass}."
            );
        }
    }

    public function test_tenant_menu_controllers_do_not_reference_legacy_menu_service(): void
    {
        $controllerFiles = [
            'app/Http/Controllers/Tenant/MenuCategoryController.php',
            'app/Http/Controllers/Tenant/MenuItemController.php',
            'app/Http/Controllers/Tenant/OptionGroupController.php',
            'app/Http/Controllers/Tenant/OptionController.php',
        ];

        foreach ($controllerFiles as $file) {
            $source = file_get_contents(base_path($file));

            $this->assertNotFalse($source, "Failed to read {$file}.");
            $this->assertStringNotContainsString(
                'App\\Services\\MenuService',
                $source,
                "{$file} must not reference the legacy MenuService."
            );
        }
    }
}
