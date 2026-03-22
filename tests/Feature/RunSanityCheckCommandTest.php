<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Feature;

use DynamicWeb\SanityCheck\Tests\TestCase;

final class RunSanityCheckCommandTest extends TestCase
{
    public function test_command_runs_with_zero_exit_code(): void
    {
        $this->artisan('sanity-check:run')
            ->assertExitCode(0);
    }

    public function test_command_exits_non_zero_when_server_errors_present(): void
    {
        config(['sanity-check.include_route_names' => ['sanity.server_error']]);

        $this->artisan('sanity-check:run', [
            '--only' => 'sanity.server_error',
        ])->assertExitCode(1);
    }

    public function test_json_mode_still_exits_non_zero_on_server_errors(): void
    {
        config(['sanity-check.include_route_names' => ['sanity.server_error']]);

        $this->artisan('sanity-check:run', [
            '--json' => true,
            '--only' => 'sanity.server_error',
        ])->assertExitCode(1);
    }

    public function test_invalid_limit_option_fails(): void
    {
        $this->artisan('sanity-check:run', ['--limit' => '0'])
            ->assertExitCode(1);

        $this->artisan('sanity-check:run', ['--limit' => 'nope'])
            ->assertExitCode(1);
    }

    public function test_no_save_runs_successfully(): void
    {
        $this->artisan('sanity-check:run', ['--no-save' => true])
            ->assertExitCode(0);
    }
}
