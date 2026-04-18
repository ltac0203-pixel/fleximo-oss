<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use Illuminate\Routing\Route;
use Tests\TestCase;

class RegisteredMiddlewareUsageTest extends TestCase
{
    public function test_all_registered_app_middleware_aliases_are_used_by_at_least_one_route(): void
    {
        $router = app('router');

        $registeredAppAliases = collect($router->getMiddleware())
            ->filter(static fn (string $middlewareClass): bool => str_starts_with($middlewareClass, 'App\\Http\\Middleware\\'));

        $resolvedRouteMiddleware = collect($router->getRoutes()->getRoutes())
            ->flatMap(static fn (Route $route): array => $route->gatherMiddleware())
            ->map(static fn (string $middleware): string => explode(':', $middleware, 2)[0])
            ->unique()
            ->values();

        $unusedAliases = $registeredAppAliases->filter(
            static fn (string $middlewareClass, string $alias): bool => ! $resolvedRouteMiddleware->contains($middlewareClass)
                && ! $resolvedRouteMiddleware->contains($alias)
        );

        $unusedAliasSummary = $unusedAliases
            ->map(static fn (string $middlewareClass, string $alias): string => sprintf('%s => %s', $alias, $middlewareClass))
            ->implode(', ');

        $this->assertCount(
            0,
            $unusedAliases,
            'Unused app middleware aliases detected: '.$unusedAliasSummary
        );
    }
}
