<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Feature;

use DynamicWeb\SanityCheck\Tests\TestCase;

final class ConfigPublishTest extends TestCase
{
    public function test_sanity_check_config_tag_publishes_file(): void
    {
        $this->artisan('vendor:publish', [
            '--tag' => 'sanity-check-config',
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertFileExists(config_path('sanity-check.php'));
        $this->assertStringContainsString('sanity-check', (string) file_get_contents(config_path('sanity-check.php')));
    }
}
