<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Feature;

use DynamicWeb\SanityCheck\Tests\TestCase;

final class OrchestratorCliFilteringTest extends TestCase
{
    public function test_artisan_only_flag_limits_checked_routes(): void
    {
        $this->artisan('sanity-check:run', [
            '--only' => 'sanity.public',
        ])->assertExitCode(0);

        $this->artisan('sanity-check:run', [
            '--only' => 'sanity.public',
            '--json' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('"total_routes": 1');
    }

    public function test_artisan_except_flag_removes_matching_routes(): void
    {
        config([
            'sanity-check.include_route_names' => ['sanity.public', 'sanity.param'],
        ]);

        $this->artisan('sanity-check:run', [
            '--except' => '*param*',
            '--json' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('"total_routes": 1');
    }
}
