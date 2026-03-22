<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Feature;

use DynamicWeb\SanityCheck\SanityCheckServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Bootstraps with enable_command=false so the provider never registers the Artisan command.
 */
final class CommandDisabledWhenConfigOffTest extends Orchestra
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
        $app['config']->set('sanity-check.enabled', true);
        $app['config']->set('sanity-check.enable_command', false);
        $app['config']->set('sanity-check.enable_dashboard', false);
    }

    public function test_sanity_check_run_command_is_not_registered(): void
    {
        $this->assertNotContains('sanity-check:run', array_keys(Artisan::all()));
    }
}
