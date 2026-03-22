<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Http\Controllers;

use DynamicWeb\SanityCheck\Enums\RunTrigger;
use DynamicWeb\SanityCheck\Http\Services\SanityCheckDashboardService;
use DynamicWeb\SanityCheck\Services\RunOrchestrator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Throwable;

final class SanityCheckDashboardController extends Controller
{
    public function __construct(
        private readonly SanityCheckDashboardService $dashboard,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $lastRun = (string) $request->query('last_run', '');
        if ($lastRun !== '') {
            $qs = $request->except('last_run');
            $url = route('sanity-check.show', ['uuid' => $lastRun]);
            if ($qs !== []) {
                $url .= '?'.http_build_query($qs);
            }

            return redirect()->to($url);
        }

        $run = $this->dashboard->resolveLatestRun();
        if ($run !== null) {
            $run = $this->dashboard->paginateFilteredBuckets($run, $request);
        }

        return view('sanity-check::dashboard.index', $this->viewPayload($request, $run, false));
    }

    public function show(Request $request, string $uuid): View
    {
        $run = $this->dashboard->resolveRunByUuid($uuid);
        if ($run === null) {
            abort(404);
        }

        $run = $this->dashboard->paginateFilteredBuckets($run, $request);

        return view('sanity-check::dashboard.show', $this->viewPayload($request, $run, true));
    }

    public function run(Request $request, RunOrchestrator $orchestrator): RedirectResponse
    {
        try {
            $bundle = $orchestrator->run(RunTrigger::Web, $request->user());

            return redirect()->route('sanity-check.show', ['uuid' => $bundle->summary->uuid]);
        } catch (Throwable $e) {
            report($e);

            $dashboardRoute = (string) config('sanity-check.dashboard_route_name', 'sanity-check.dashboard');

            return redirect()
                ->route($dashboardRoute)
                ->with('sanity_check_error', __('The sanity check could not be completed or saved. Please try again or contact an administrator.'));
        }
    }

    /**
     * @param  array<string, mixed>|null  $run
     * @return array<string, mixed>
     */
    private function viewPayload(Request $request, ?array $run, bool $isRunDetail): array
    {
        $history = collect();
        if ((bool) config('sanity-check.show_history', true)) {
            $limit = (int) config('sanity-check.history_limit', 20);
            $history = $this->dashboard->history($limit);
        }

        $counts = $run['counts'] ?? ['2xx' => 0, '3xx' => 0, '4xx' => 0, '5xx' => 0, 'ignored' => 0];

        $dashboardRouteName = (string) config('sanity-check.dashboard_route_name', 'sanity-check.dashboard');
        $filterFormAction = ($isRunDetail && $run !== null)
            ? route('sanity-check.show', ['uuid' => $run['uuid']])
            : route($dashboardRouteName);

        return [
            'title' => $isRunDetail && $run !== null
                ? 'Sanity check · run '.$run['uuid']
                : 'Sanity check',
            'pageHeading' => $isRunDetail && $run !== null
                ? 'Inspect run'
                : 'Sanity check',
            'isRunDetail' => $isRunDetail,
            'run' => $run,
            'counts' => $counts,
            'hasServerErrors' => ($counts['5xx'] ?? 0) > 0,
            'history' => $history,
            'saveRuns' => (bool) config('sanity-check.save_runs', true),
            'showHistory' => (bool) config('sanity-check.show_history', true),
            'uiTheme' => (string) config('sanity-check.ui_theme', 'dark'),
            'dashboardRouteName' => $dashboardRouteName,
            'exportJsonEnabled' => (bool) config('sanity-check.enable_json_export', true),
            'exportCsvEnabled' => (bool) config('sanity-check.enable_csv_export', false),
            'aboutTitle' => (string) config('sanity-check.about_panel_title', 'About this check'),
            'objective' => (string) config('sanity-check.objective_text'),
            'buckets' => SanityCheckDashboardService::CLASSIFICATION_BUCKETS,
            'filterQuery' => trim((string) $request->query('q', '')),
            'filterBucket' => trim((string) $request->query('bucket', '')),
            'filterUrls' => $this->filterUrls($run, $request, $isRunDetail, $dashboardRouteName),
            'filterFormAction' => $filterFormAction,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $run
     * @return array<string, string>
     */
    private function filterUrls(?array $run, Request $request, bool $isRunDetail, string $dashboardRouteName): array
    {
        $base = ($isRunDetail && $run !== null)
            ? route('sanity-check.show', ['uuid' => $run['uuid']])
            : route($dashboardRouteName);
        $q = trim((string) $request->query('q', ''));

        $build = static function (?string $bucket) use ($base, $q): string {
            $params = [];
            if ($q !== '') {
                $params['q'] = $q;
            }
            if ($bucket !== null && $bucket !== '') {
                $params['bucket'] = $bucket;
            }

            return $base.($params === [] ? '' : '?'.http_build_query($params));
        };

        $urls = ['all' => $build(null)];
        foreach (SanityCheckDashboardService::CLASSIFICATION_BUCKETS as $bucket) {
            $urls[$bucket] = $build($bucket);
        }

        return $urls;
    }
}
