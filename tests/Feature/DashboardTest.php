<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Feature;

use DynamicWeb\SanityCheck\Tests\TestCase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

final class DashboardTest extends TestCase
{
    public function test_dashboard_renders(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $response = $this->get('/admin/sanity-check');

        $response->assertOk();
        $response->assertSee('Sanity check', false);
    }

    public function test_last_run_query_redirects_to_show_route(): void
    {
        $uuid = 'aaaaaaaa-bbbb-4ccc-dddd-eeeeeeeeeeee';

        $response = $this->get('/admin/sanity-check?last_run='.$uuid.'&q=test');

        $response->assertRedirect(route('sanity-check.show', ['uuid' => $uuid]).'?q=test');
    }

    public function test_dashboard_returns_403_when_authorization_required_and_guest(): void
    {
        config(['sanity-check.authorization_ability' => 'viewSanityCheck']);

        $response = $this->get('/admin/sanity-check');

        $response->assertForbidden();
        $response->assertSee('Access denied', false);
    }
}
