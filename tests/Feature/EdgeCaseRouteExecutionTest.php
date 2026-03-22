<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Feature;

use DynamicWeb\SanityCheck\Enums\OutcomeClassification;
use DynamicWeb\SanityCheck\Enums\RunTrigger;
use DynamicWeb\SanityCheck\Services\RunOrchestrator;
use DynamicWeb\SanityCheck\Tests\TestCase;

/**
 * Kernel execution edge cases through the orchestrator.
 */
final class EdgeCaseRouteExecutionTest extends TestCase
{
    public function test_not_found_route_classified_as_client_error(): void
    {
        config(['sanity-check.include_route_names' => ['sanity.not_found']]);

        $bundle = $this->app->make(RunOrchestrator::class)->run(RunTrigger::Cli, null);

        $this->assertCount(1, $bundle->items);
        $this->assertSame(OutcomeClassification::ClientError, $bundle->items[0]->classification);
        $this->assertSame(404, $bundle->items[0]->statusCode);
    }

    public function test_redirect_route_classified_as_redirect_by_default(): void
    {
        config(['sanity-check.include_route_names' => ['sanity.redirect']]);

        $bundle = $this->app->make(RunOrchestrator::class)->run(RunTrigger::Cli, null);

        $this->assertCount(1, $bundle->items);
        $this->assertSame(OutcomeClassification::Redirect, $bundle->items[0]->classification);
        $this->assertGreaterThanOrEqual(300, (int) $bundle->items[0]->statusCode);
        $this->assertLessThan(400, (int) $bundle->items[0]->statusCode);
    }

    public function test_throwing_route_surfaces_as_server_or_ignored_depending_on_kernel(): void
    {
        config(['sanity-check.include_route_names' => ['sanity.throws']]);

        $bundle = $this->app->make(RunOrchestrator::class)->run(RunTrigger::Cli, null);

        $this->assertCount(1, $bundle->items);
        $item = $bundle->items[0];
        $this->assertTrue(
            $item->classification === OutcomeClassification::ServerError
            || $item->classification === OutcomeClassification::Ignored,
            'Expected 500 response or transport-style failure from exception route'
        );
    }
}
