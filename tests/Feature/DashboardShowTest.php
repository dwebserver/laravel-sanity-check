<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Feature;

use DynamicWeb\SanityCheck\Tests\TestCase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

final class DashboardShowTest extends TestCase
{
    public function test_show_returns_404_for_unknown_run_uuid(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->get('/admin/sanity-check/runs/aaaaaaaa-bbbb-4ccc-dddd-eeeeeeeeeeee')
            ->assertNotFound();
    }
}
