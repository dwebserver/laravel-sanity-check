<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Feature;

use DynamicWeb\SanityCheck\DTOs\RunOrchestratorOptions;
use DynamicWeb\SanityCheck\Enums\OutcomeClassification;
use DynamicWeb\SanityCheck\Enums\RunTrigger;
use DynamicWeb\SanityCheck\Models\SanityCheckRun;
use DynamicWeb\SanityCheck\Services\RunOrchestrator;
use DynamicWeb\SanityCheck\Tests\TestCase;

/**
 * End-to-end run through the same orchestrator the dashboard uses.
 */
final class FullRunOrchestratorIntegrationTest extends TestCase
{
    public function test_full_run_executes_config_included_routes_and_persists(): void
    {
        $this->assertSame(0, SanityCheckRun::query()->count());

        $bundle = $this->app->make(RunOrchestrator::class)->run(RunTrigger::Cli, null);

        $this->assertSame(3, $bundle->summary->totalRoutes);
        $this->assertContains($bundle->summary->uuid, SanityCheckRun::query()->pluck('uuid')->all());

        $public = collect($bundle->items)->first(fn ($i) => $i->routeName === 'sanity.public');
        $this->assertNotNull($public);
        $this->assertSame(OutcomeClassification::Success, $public->classification);
    }

    public function test_no_save_option_skips_database_persistence(): void
    {
        SanityCheckRun::query()->delete();

        $before = SanityCheckRun::query()->count();
        $this->app->make(RunOrchestrator::class)->run(
            RunTrigger::Cli,
            null,
            new RunOrchestratorOptions(persist: false),
        );

        $this->assertSame($before, SanityCheckRun::query()->count());
    }

    public function test_unresolvable_route_is_ignored_when_configured(): void
    {
        config([
            'sanity-check.include_route_names' => ['sanity.no_resolver'],
            'sanity-check.parameter_resolvers' => [],
            'sanity-check.ignore_unresolvable_routes' => true,
        ]);

        $bundle = $this->app->make(RunOrchestrator::class)->run(RunTrigger::Cli, null);

        $this->assertCount(1, $bundle->items);
        $this->assertSame(OutcomeClassification::Ignored, $bundle->items[0]->classification);
        $this->assertFalse($bundle->items[0]->wasExecuted);
    }
}
