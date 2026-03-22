<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Feature;

use DynamicWeb\SanityCheck\Tests\TestCase;
use Illuminate\Support\Facades\Route;

final class PackageRoutesTest extends TestCase
{
    public function test_dashboard_and_core_named_routes_exist(): void
    {
        $this->assertTrue(Route::has('sanity-check.dashboard'));
        $this->assertTrue(Route::has('sanity-check.run'));
        $this->assertTrue(Route::has('sanity-check.show'));
        $this->assertTrue(Route::has('sanity-check.export'));
        $this->assertTrue(Route::has('sanity-check.export.run'));
    }

    public function test_dashboard_get_resolves_to_configured_path(): void
    {
        $route = Route::getRoutes()->getByName('sanity-check.dashboard');
        $this->assertNotNull($route);
        $this->assertStringContainsString('admin/sanity-check', (string) $route->uri());
    }
}
