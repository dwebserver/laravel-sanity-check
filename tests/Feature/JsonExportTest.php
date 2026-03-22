<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Feature;

use DynamicWeb\SanityCheck\Enums\RunTrigger;
use DynamicWeb\SanityCheck\Services\RunOrchestrator;
use DynamicWeb\SanityCheck\Tests\TestCase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

final class JsonExportTest extends TestCase
{
    public function test_export_latest_returns_json_after_persisted_run(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->app->make(RunOrchestrator::class)->run(RunTrigger::Cli, null);

        $response = $this->getJson('/admin/sanity-check/export');

        $response->assertOk();
        $response->assertJsonStructure([
            'uuid',
            'environment',
            'counts',
            'items',
        ]);
        $this->assertNotEmpty($response->json('uuid'));
        $this->assertIsArray($response->json('items'));
    }

    public function test_export_unknown_uuid_returns_404(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $response = $this->getJson('/admin/sanity-check/export/aaaaaaaa-bbbb-4ccc-dddd-eeeeeeeeeeee');

        $response->assertNotFound();
    }
}
