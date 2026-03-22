<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Http\Services;

use DynamicWeb\SanityCheck\Models\SanityCheckRun;
use DynamicWeb\SanityCheck\Support\EphemeralRunCache;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves run payloads for the dashboard and applies search, classification filters, and pagination.
 */
final class SanityCheckDashboardService
{
    /** @var list<string> */
    public const CLASSIFICATION_BUCKETS = ['2xx', '3xx', '4xx', '5xx', 'ignored'];

    /**
     * @return array<string, mixed>|null
     */
    public function resolveRunByUuid(string $uuid): ?array
    {
        $model = SanityCheckRun::with('items')->where('uuid', $uuid)->first();
        if ($model instanceof SanityCheckRun) {
            return $this->runModelToView($model);
        }

        $cached = Cache::get(EphemeralRunCache::key($uuid));
        if (is_array($cached)) {
            return $this->ephemeralToView($cached);
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolveLatestRun(): ?array
    {
        $model = SanityCheckRun::with('items')->orderByDesc('id')->first();
        if (! $model instanceof SanityCheckRun) {
            return null;
        }

        return $this->runModelToView($model);
    }

    /**
     * @return EloquentCollection<int, SanityCheckRun>
     */
    public function history(int $limit): EloquentCollection
    {
        return SanityCheckRun::query()
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'uuid', 'meta', 'created_at']);
    }

    /**
     * @param  array<string, mixed>  $run
     * @return array<string, mixed>
     */
    public function paginateFilteredBuckets(array $run, Request $request): array
    {
        $perPage = max(1, (int) config('sanity-check.results_per_page', 25));
        $q = mb_strtolower(trim((string) $request->query('q', '')));
        $bucketParam = trim((string) $request->query('bucket', ''));

        /** @var list<array<string, mixed>> $itemRows */
        $itemRows = $run['items'] ?? [];
        $totalInRun = count($itemRows);
        $items = collect($itemRows);
        if ($q !== '') {
            $items = $items->filter(function (array $row) use ($q): bool {
                $haystacks = [
                    mb_strtolower((string) ($row['route_name'] ?? '')),
                    mb_strtolower((string) ($row['uri'] ?? '')),
                    mb_strtolower((string) ($row['resolved_uri'] ?? '')),
                ];
                foreach ($haystacks as $h) {
                    if ($h !== '' && str_contains($h, $q)) {
                        return true;
                    }
                }

                return false;
            });
        }

        $visibleBuckets = self::CLASSIFICATION_BUCKETS;
        if ($bucketParam !== '' && in_array($bucketParam, self::CLASSIFICATION_BUCKETS, true)) {
            $visibleBuckets = [$bucketParam];
        }

        $grouped = $items->groupBy('classification');
        $inVisibleBuckets = 0;
        foreach ($visibleBuckets as $b) {
            $inVisibleBuckets += $grouped->get($b, collect())->count();
        }

        $paginators = [];

        foreach (self::CLASSIFICATION_BUCKETS as $bucket) {
            if (! in_array($bucket, $visibleBuckets, true)) {
                $paginators[$bucket] = null;

                continue;
            }

            $rows = $grouped->get($bucket, collect());
            $pageName = 'p_'.$bucket;
            $page = max(1, (int) $request->query($pageName, '1'));
            $slice = $rows->slice(($page - 1) * $perPage, $perPage)->values();

            $paginators[$bucket] = new LengthAwarePaginator(
                $slice,
                $rows->count(),
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'pageName' => $pageName,
                    'query' => $request->query(),
                ]
            );
        }

        $run['paginators'] = $paginators;
        $run['visible_buckets'] = $visibleBuckets;
        $run['filter_query'] = trim((string) $request->query('q', ''));
        $run['filter_bucket'] = $bucketParam;
        $run['filter_total_in_run'] = $totalInRun;
        $run['filter_after_search_count'] = $items->count();
        $run['filter_visible_rows_count'] = $inVisibleBuckets;

        return $run;
    }

    /**
     * @return array<string, mixed>
     */
    private function runModelToView(SanityCheckRun $run): array
    {
        $items = $run->items->map(static function ($item): array {
            return [
                'route_name' => $item->route_name,
                'uri' => $item->uri,
                'method' => $item->method,
                'resolved_uri' => $item->resolved_uri,
                'status_code' => $item->status_code,
                'classification' => $item->legacy_classification_bucket,
                'response_time_ms' => $item->response_time_ms,
                'ignored_reason' => data_get($item->meta, 'ignored_reason'),
                'error_message' => data_get($item->meta, 'error_message'),
                'action' => $item->action,
            ];
        })->all();

        return [
            'uuid' => $run->uuid,
            'environment' => $run->environment,
            'trigger' => $run->trigger,
            'created_at' => $run->created_at,
            'started_at' => $run->started_at,
            'finished_at' => $run->finished_at,
            'duration_ms' => $run->duration_ms,
            'counts' => $run->counts ?? $this->countsFromItems($items),
            'items' => $items,
            'source' => 'database',
        ];
    }

    /**
     * @param  array<string, mixed>  $cached
     * @return array<string, mixed>
     */
    private function ephemeralToView(array $cached): array
    {
        $items = $cached['items'] ?? [];
        $counts = $cached['counts'] ?? $this->countsFromItems($items);

        return [
            'uuid' => (string) ($cached['uuid'] ?? ''),
            'environment' => (string) ($cached['environment'] ?? ''),
            'trigger' => (string) ($cached['trigger'] ?? ''),
            'created_at' => null,
            'started_at' => isset($cached['started_at']) ? Carbon::createFromTimestamp((int) floor((float) $cached['started_at'])) : null,
            'finished_at' => isset($cached['finished_at']) ? Carbon::createFromTimestamp((int) floor((float) $cached['finished_at'])) : null,
            'duration_ms' => isset($cached['duration_ms']) ? (int) $cached['duration_ms'] : null,
            'counts' => $counts,
            'items' => $items,
            'source' => 'ephemeral',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, int>
     */
    private function countsFromItems(array $items): array
    {
        $out = ['2xx' => 0, '3xx' => 0, '4xx' => 0, '5xx' => 0, 'ignored' => 0];
        foreach ($items as $item) {
            $c = (string) ($item['classification'] ?? '');
            if (array_key_exists($c, $out)) {
                $out[$c]++;
            }
        }

        return $out;
    }
}
