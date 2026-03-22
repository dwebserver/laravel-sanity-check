<?php

declare(strict_types=1);

/**
 * Example: bind a stable ID for dynamic route segments in consuming applications.
 *
 * Register in config/sanity-check.php:
 *
 *   'parameter_resolvers' => [
 *       'id' => \Your\App\Resolvers\ExampleIdParameterResolver::class,
 *   ],
 *
 * Or use a closure (resolved via the container is not used for closures; they are invoked directly):
 *
 *   'parameter_resolvers' => [
 *       'slug' => fn (string $name, \Illuminate\Routing\Route $route) => 'demo-slug',
 *   ],
 */

namespace Your\App\Resolvers;

use DynamicWeb\SanityCheck\Contracts\ParameterResolverInterface;
use Illuminate\Routing\Route;

final class ExampleIdParameterResolver implements ParameterResolverInterface
{
    public function resolve(string $parameterName, Route $route): ?string
    {
        if ($parameterName === 'id') {
            return '1';
        }

        return null;
    }
}
