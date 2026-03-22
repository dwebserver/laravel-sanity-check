<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Unit;

use DynamicWeb\SanityCheck\Services\RouteScanner;
use DynamicWeb\SanityCheck\Tests\TestCase;
use Illuminate\Routing\Router;

final class RouteScannerTest extends TestCase
{
    public function test_discovers_only_configured_http_methods(): void
    {
        $router = $this->app->make(Router::class);
        $scanner = new RouteScanner($router, ['GET']);

        $methods = $scanner->discover()
            ->pluck('method')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->assertContains('GET', $methods);
        $this->assertNotContains('POST', $methods);
    }

    public function test_skips_head_when_get_allowed_and_route_has_both(): void
    {
        $router = $this->app->make(Router::class);
        $scanner = new RouteScanner($router, ['GET', 'HEAD']);

        $public = $scanner->discover()->filter(fn ($c) => $c->name === 'sanity.public');
        $methods = $public->pluck('method')->all();

        $this->assertContains('GET', $methods);
        $this->assertNotContains('HEAD', $methods);
    }

    public function test_post_route_not_discovered_when_only_get_allowed(): void
    {
        $router = $this->app->make(Router::class);
        $scanner = new RouteScanner($router, ['GET']);

        $names = $scanner->discover()->pluck('name')->filter()->all();
        $this->assertNotContains('sanity.post_only', $names);
    }

    public function test_unnamed_route_is_discovered_with_null_name(): void
    {
        $router = $this->app->make(Router::class);
        $scanner = new RouteScanner($router, ['GET']);

        $unnamed = $scanner->discover()->first(fn ($c) => str_contains($c->uriTemplate, '__fixture_unnamed'));
        $this->assertNotNull($unnamed);
        $this->assertNull($unnamed->name);
    }
}
