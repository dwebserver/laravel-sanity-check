<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Feature;

use DynamicWeb\SanityCheck\Contracts\ResultRepositoryInterface;
use DynamicWeb\SanityCheck\Contracts\RouteScannerInterface;
use DynamicWeb\SanityCheck\Contracts\RouteTesterInterface;
use DynamicWeb\SanityCheck\Services\RunOrchestrator;
use DynamicWeb\SanityCheck\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

final class ServiceProviderRegistrationTest extends TestCase
{
    public function test_config_is_merged_under_sanity_check_key(): void
    {
        $this->assertIsArray(config('sanity-check'));
        $this->assertArrayHasKey('enabled', config('sanity-check'));
        $this->assertArrayHasKey('allowed_methods', config('sanity-check'));
    }

    public function test_core_container_bindings_resolve(): void
    {
        $this->assertInstanceOf(RouteScannerInterface::class, $this->app->make(RouteScannerInterface::class));
        $this->assertInstanceOf(RouteTesterInterface::class, $this->app->make(RouteTesterInterface::class));
        $this->assertInstanceOf(ResultRepositoryInterface::class, $this->app->make(ResultRepositoryInterface::class));
        $this->assertInstanceOf(RunOrchestrator::class, $this->app->make(RunOrchestrator::class));
    }

    public function test_sanity_check_run_command_is_registered(): void
    {
        $commands = array_keys(Artisan::all());
        $this->assertContains('sanity-check:run', $commands);
    }
}
