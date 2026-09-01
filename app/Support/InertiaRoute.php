<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use LogicException;

/**
 * Route::inertia() is registered as a runtime macro, so static analysis
 * cannot see its real return type. This wrapper carries an explicit one.
 */
final class InertiaRoute
{
    /**
     * @param  array<array-key, mixed>  $props
     */
    public static function get(string $uri, string $component, array $props = []): Route
    {
        $route = RouteFacade::inertia($uri, $component, $props);

        if (! $route instanceof Route) {
            throw new LogicException('Route::inertia() did not return a Route instance.');
        }

        return $route;
    }
}
