<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests;

use DynamicWeb\SanityCheck\SanityCheckServiceProvider;
use DynamicWeb\SanityCheck\Tests\Fixtures\InvokableTestController;
use DynamicWeb\SanityCheck\Tests\Fixtures\PostBoundController;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

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
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('sanity-check.enabled', true);
        $app['config']->set('sanity-check.enable_dashboard', true);
        $app['config']->set('sanity-check.enable_command', true);
        $app['config']->set('sanity-check.authorization_ability', null);
        $app['config']->set('sanity-check.environment_allowlist', []);
        $app['config']->set('sanity-check.middleware', ['web']);
        $app['config']->set('sanity-check.dashboard_path', 'admin/sanity-check');
        $app['config']->set('sanity-check.dashboard_route_name', 'sanity-check.dashboard');
        $app['config']->set('sanity-check.skip_closure_routes', false);
        $app['config']->set('sanity-check.include_route_names', ['sanity.public', 'sanity.redirect', 'sanity.param']);
        $app['config']->set('sanity-check.parameter_resolvers', [
            'id' => static fn (): string => '1',
        ]);
        $app['config']->set('sanity-check.save_runs', true);
        $app['config']->set('sanity-check.retention_days', 0);
        $app['config']->set('sanity-check.max_saved_runs', 0);
    }

    protected function defineRoutes($router): void
    {
        Route::middleware('web')->group(function (): void {
            Route::get('/__sanity_public', fn () => response('ok', 200))->name('sanity.public');
            Route::get('/__sanity_redirect', fn () => redirect('/__sanity_public'))->name('sanity.redirect');
            Route::get('/__sanity_param/{id}', fn (string $id) => response('id:'.$id, 200))->name('sanity.param');
            Route::get('/__sanity_500', static fn () => response('server error', 500))->name('sanity.server_error');
            Route::post('/__fixture_post_only', static fn () => response('post-ok', 200))->name('sanity.post_only');
            Route::get('/__fixture_nr/{slug}', static fn (string $slug) => response('slug:'.$slug, 200))->name('sanity.no_resolver');
            Route::get('/__fixture_404', static fn () => abort(404))->name('sanity.not_found');
            Route::get('/__fixture_throw', static function (): never {
                throw new \RuntimeException('fixture explosion');
            })->name('sanity.throws');
            Route::get('/__fixture_invokable', InvokableTestController::class)->name('sanity.invokable');
            Route::get('/__fixture_unnamed', static fn () => response('unnamed', 200));
            Route::get('/__fixture_post_binding/{post}', [PostBoundController::class, 'show'])->name('sanity.post_binding');
        });
    }
}
