<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Unit;

use DynamicWeb\SanityCheck\Contracts\ParameterResolverInterface;
use DynamicWeb\SanityCheck\DTOs\RouteCandidateData;
use DynamicWeb\SanityCheck\Services\ParameterResolutionManager;
use DynamicWeb\SanityCheck\Tests\TestCase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

final class ParameterResolutionManagerTest extends TestCase
{
    public function test_resolves_all_parameters_via_first_successful_resolver(): void
    {
        $route = RouteFacade::getRoutes()->getByName('sanity.param');
        $this->assertInstanceOf(Route::class, $route);

        $candidate = new RouteCandidateData(
            name: 'sanity.param',
            uriTemplate: $route->uri(),
            method: 'GET',
            parameterNames: $route->parameterNames(),
            route: $route,
        );

        $chain = [
            new class implements ParameterResolverInterface
            {
                public function resolve(string $parameterName, Route $route): ?string
                {
                    return null;
                }
            },
            new class implements ParameterResolverInterface
            {
                public function resolve(string $parameterName, Route $route): ?string
                {
                    return $parameterName === 'id' ? '99' : null;
                }
            },
        ];

        $manager = new ParameterResolutionManager($chain);

        $this->assertSame(['id' => '99'], $manager->resolveAll($candidate));
    }

    public function test_returns_null_when_parameter_unresolved(): void
    {
        $route = RouteFacade::getRoutes()->getByName('sanity.no_resolver');
        $this->assertInstanceOf(Route::class, $route);

        $candidate = new RouteCandidateData(
            name: 'sanity.no_resolver',
            uriTemplate: $route->uri(),
            method: 'GET',
            parameterNames: $route->parameterNames(),
            route: $route,
        );

        $manager = new ParameterResolutionManager([
            new class implements ParameterResolverInterface
            {
                public function resolve(string $parameterName, Route $route): ?string
                {
                    return null;
                }
            },
        ]);

        $this->assertNull($manager->resolveAll($candidate));
    }
}
