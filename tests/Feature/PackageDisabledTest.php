<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Feature;

use DynamicWeb\SanityCheck\SanityCheckServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * When enabled=false, HTTP routes from the package must not be registered.
 */
final class PackageDisabledTest extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [SanityCheckServiceProvider::class];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('sanity-check.enabled', false);
        $app['config']->set('sanity-check.enable_dashboard', true);
    }

    public function test_dashboard_route_is_not_registered(): void
    {
        $this->assertFalse(Route::has('sanity-check.dashboard'));
    }
}
