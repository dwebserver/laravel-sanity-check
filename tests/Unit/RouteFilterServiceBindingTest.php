<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Unit;

use DynamicWeb\SanityCheck\Services\RouteFilter;
use DynamicWeb\SanityCheck\Tests\TestCase;
use ReflectionClass;

/**
 * Ensures default package config is wired into {@see RouteFilter} (e.g. vendor skip).
 */
final class RouteFilterServiceBindingTest extends TestCase
{
    public function test_default_skip_vendor_routes_is_enabled_on_resolved_filter(): void
    {
        $this->assertTrue((bool) config('sanity-check.skip_vendor_routes', false));

        $filter = $this->app->make(RouteFilter::class);
        $prop = (new ReflectionClass($filter))->getProperty('skipVendorRoutes');
        $prop->setAccessible(true);

        $this->assertTrue($prop->getValue($filter));
    }
}
