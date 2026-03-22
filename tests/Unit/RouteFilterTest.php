<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Unit;

use DynamicWeb\SanityCheck\Services\RouteFilter;
use DynamicWeb\SanityCheck\Services\RouteScanner;
use DynamicWeb\SanityCheck\Tests\TestCase;
use Illuminate\Routing\Router;

final class RouteFilterTest extends TestCase
{
    public function test_include_route_names_keeps_only_matches(): void
    {
        $router = $this->app->make(Router::class);
        $scanner = new RouteScanner($router, ['GET']);
        $filter = $this->makeFilter(includeRouteNames: ['sanity.public']);

        $names = $filter->filter($scanner->discover())->pluck('name')->filter()->all();

        $this->assertSame(['sanity.public'], array_values(array_unique($names)));
    }

    public function test_exclude_route_names_removes_route(): void
    {
        $router = $this->app->make(Router::class);
        $scanner = new RouteScanner($router, ['GET']);
        $filter = $this->makeFilter(
            includeRouteNames: [],
            excludeRouteNames: ['sanity.redirect'],
        );

        $names = $filter->filter($scanner->discover())->pluck('name')->filter()->all();
        $this->assertNotContains('sanity.redirect', $names);
    }

    public function test_include_patterns_match_uri(): void
    {
        $router = $this->app->make(Router::class);
        $scanner = new RouteScanner($router, ['GET']);
        $filter = $this->makeFilter(
            includeRouteNames: [],
            includePatterns: ['*__fixture_unnamed*'],
        );

        $uris = $filter->filter($scanner->discover())->pluck('uriTemplate')->all();
        $this->assertTrue(collect($uris)->contains(fn (string $u) => str_contains($u, '__fixture_unnamed')));
    }

    public function test_exclude_patterns_drop_matches(): void
    {
        $router = $this->app->make(Router::class);
        $scanner = new RouteScanner($router, ['GET']);
        $filter = $this->makeFilter(
            includeRouteNames: ['sanity.public', 'sanity.param'],
            excludePatterns: ['*param*'],
        );

        $names = $filter->filter($scanner->discover())->pluck('name')->filter()->all();
        $this->assertContains('sanity.public', $names);
        $this->assertNotContains('sanity.param', $names);
    }

    public function test_skip_closure_routes_excludes_closures(): void
    {
        $router = $this->app->make(Router::class);
        $scanner = new RouteScanner($router, ['GET']);
        $filter = $this->makeFilter(
            includeRouteNames: ['sanity.public', 'sanity.invokable'],
            skipClosureRoutes: true,
        );

        $names = $filter->filter($scanner->discover())->pluck('name')->filter()->all();
        $this->assertNotContains('sanity.public', $names);
        $this->assertContains('sanity.invokable', $names);
    }

    public function test_max_routes_per_run_caps_output(): void
    {
        $router = $this->app->make(Router::class);
        $scanner = new RouteScanner($router, ['GET']);
        $filter = $this->makeFilter(
            includeRouteNames: [],
            maxRoutesPerRun: 2,
        );

        $this->assertLessThanOrEqual(2, $filter->filter($scanner->discover())->count());
    }

    public function test_ignore_missing_bound_models_excludes_unresolved_implicit_binding(): void
    {
        $router = $this->app->make(Router::class);
        $scanner = new RouteScanner($router, ['GET']);
        $filter = $this->makeFilter(
            includeRouteNames: ['sanity.post_binding'],
            ignoreMissingBoundModels: true,
            parameterResolverKeys: ['id'],
        );

        $names = $filter->filter($scanner->discover())->pluck('name')->filter()->all();
        $this->assertNotContains('sanity.post_binding', $names);
    }

    public function test_ignore_missing_bound_models_false_keeps_binding_route(): void
    {
        $router = $this->app->make(Router::class);
        $scanner = new RouteScanner($router, ['GET']);
        $filter = $this->makeFilter(
            includeRouteNames: ['sanity.post_binding'],
            ignoreMissingBoundModels: false,
            parameterResolverKeys: ['id'],
        );

        $names = $filter->filter($scanner->discover())->pluck('name')->filter()->all();
        $this->assertContains('sanity.post_binding', $names);
    }

    /**
     * @param  list<string>  $includeRouteNames
     * @param  list<string>  $includePatterns
     * @param  list<string>  $excludeRouteNames
     * @param  list<string>  $excludePatterns
     * @param  list<string>  $parameterResolverKeys
     */
    private function makeFilter(
        array $includeRouteNames = ['sanity.public'],
        array $includePatterns = [],
        array $excludeRouteNames = [],
        array $excludePatterns = [],
        bool $skipClosureRoutes = false,
        int $maxRoutesPerRun = 500,
        bool $ignoreMissingBoundModels = false,
        array $parameterResolverKeys = [],
    ): RouteFilter {
        return new RouteFilter(
            routeNamePrefixes: [],
            routeUriPrefixes: [],
            includeRouteNames: $includeRouteNames,
            includePatterns: $includePatterns,
            excludeRouteNames: $excludeRouteNames,
            excludePatterns: $excludePatterns,
            skipSignedRoutes: false,
            skipThrottledRoutes: false,
            skipClosureRoutes: $skipClosureRoutes,
            skipVendorRoutes: false,
            ignoreMissingBoundModels: $ignoreMissingBoundModels,
            parameterResolverKeys: $parameterResolverKeys,
            maxRoutesPerRun: $maxRoutesPerRun,
        );
    }
}
