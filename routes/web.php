<?php

declare(strict_types=1);

use DynamicWeb\SanityCheck\Http\Controllers\SanityCheckDashboardController;
use DynamicWeb\SanityCheck\Http\Controllers\SanityCheckExportController;
use DynamicWeb\SanityCheck\Http\Middleware\AuthorizeSanityCheck;
use DynamicWeb\SanityCheck\Http\Middleware\EnsureSanityCheckEnvironment;
use Illuminate\Support\Facades\Route;

$middleware = array_merge(
    (array) config('sanity-check.middleware', ['web']),
    [
        EnsureSanityCheckEnvironment::class,
        AuthorizeSanityCheck::class,
    ],
);

$prefix = trim((string) config('sanity-check.dashboard_path', 'admin/sanity-check'), '/');
if ($prefix === '') {
    $prefix = 'sanity-check';
}

$dashboardRouteName = (string) config('sanity-check.dashboard_route_name', 'sanity-check.dashboard');

$uuidPattern = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';

Route::middleware($middleware)
    ->prefix($prefix)
    ->group(function () use ($dashboardRouteName, $uuidPattern): void {
        Route::get('/', [SanityCheckDashboardController::class, 'index'])->name($dashboardRouteName);
        Route::post('/run', [SanityCheckDashboardController::class, 'run'])->name('sanity-check.run');
        Route::get('/runs/{uuid}', [SanityCheckDashboardController::class, 'show'])
            ->where('uuid', $uuidPattern)
            ->name('sanity-check.show');

        if ((bool) config('sanity-check.enable_csv_export', false)) {
            Route::get('/export/csv/{uuid}', [SanityCheckExportController::class, 'csv'])
                ->where('uuid', $uuidPattern)
                ->name('sanity-check.export.csv.run');
            Route::get('/export/csv', [SanityCheckExportController::class, 'csv'])
                ->name('sanity-check.export.csv');
        }

        if ((bool) config('sanity-check.enable_json_export', true)) {
            Route::get('/export/{uuid}', [SanityCheckExportController::class, 'json'])
                ->where('uuid', $uuidPattern)
                ->name('sanity-check.export.run');
            Route::get('/export', [SanityCheckExportController::class, 'json'])
                ->name('sanity-check.export');
        }
    });
