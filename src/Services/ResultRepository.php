<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Services;

use DynamicWeb\SanityCheck\Contracts\ResultRepositoryInterface;
use DynamicWeb\SanityCheck\DTOs\CheckResultData;
use DynamicWeb\SanityCheck\DTOs\RunSummaryData;
use DynamicWeb\SanityCheck\Enums\OutcomeClassification;
use DynamicWeb\SanityCheck\Models\SanityCheckItem;
use DynamicWeb\SanityCheck\Models\SanityCheckRun;
use DynamicWeb\SanityCheck\Support\EphemeralRunCache;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class ResultRepository implements ResultRepositoryInterface
{
    public function __construct(
        private readonly CacheRepository $cache,
        private readonly SanityCheckRetentionService $retention,
    ) {
    }

    public function persist(RunSummaryData $summary, array $items): void
    {
        DB::transaction(function () use ($summary, $items): void {
            $run = SanityCheckRun::query()->create([
                'uuid' => $summary->uuid,
                'started_at' => Carbon::createFromTimestamp((int) floor($summary->startedAt)),
                'finished_at' => Carbon::createFromTimestamp((int) floor($summary->finishedAt)),
                'duration_ms' => $summary->durationMs,
                'executed_by_id' => $summary->executedById,
                'executed_by_type' => $summary->executedByType,
                'total_routes' => $summary->totalRoutes,
                'tested_routes' => $summary->testedRoutes,
                'ignored_routes' => $summary->ignoredRoutes,
                'success_count' => $summary->successCount,
                'redirect_count' => $summary->redirectCount,
                'client_error_count' => $summary->clientErrorCount,
                'server_error_count' => $summary->serverErrorCount,
                'success_rate' => $summary->successRate,
                'config_snapshot' => $this->configSnapshot(),
                'meta' => [
                    'environment' => $summary->environment,
                    'trigger' => $summary->trigger,
                    'triggered_by_user_id' => $summary->triggeredByUserId,
                    'counts' => $summary->counts,
                    'rates_percent' => $summary->ratesPercent,
                ],
            ]);

            foreach ($items as $item) {
                $this->insertItem($run->id, $item);
            }
        });
    }

    public function applyRetention(): void
    {
        $this->retention->prune();
    }

    /**
     * @param  list<CheckResultData>  $items
     */
    public function storeEphemeral(string $uuid, RunSummaryData $summary, array $items): void
    {
        $this->cache->put(
            EphemeralRunCache::key($uuid),
            [
                'uuid' => $summary->uuid,
                'environment' => $summary->environment,
                'trigger' => $summary->trigger,
                'triggered_by_user_id' => $summary->triggeredByUserId,
                'executed_by_id' => $summary->executedById,
                'executed_by_type' => $summary->executedByType,
                'started_at' => $summary->startedAt,
                'finished_at' => $summary->finishedAt,
                'duration_ms' => $summary->durationMs,
                'total_routes' => $summary->totalRoutes,
                'tested_routes' => $summary->testedRoutes,
                'ignored_routes' => $summary->ignoredRoutes,
                'success_count' => $summary->successCount,
                'redirect_count' => $summary->redirectCount,
                'client_error_count' => $summary->clientErrorCount,
                'server_error_count' => $summary->serverErrorCount,
                'success_rate' => $summary->successRate,
                'counts' => $summary->counts,
                'rates_percent' => $summary->ratesPercent,
                'items' => array_map(
                    static fn (CheckResultData $i) => $i->toExportRow(),
                    $items
                ),
            ],
            now()->addHour()
        );
    }

    private function insertItem(int $runId, CheckResultData $item): void
    {
        SanityCheckItem::query()->create([
            'run_id' => $runId,
            'route_name' => $item->routeName,
            'uri' => $item->uriTemplate,
            'method' => $item->method,
            'resolved_uri' => $item->resolvedUri,
            'action' => $item->action,
            'status_code' => $item->statusCode,
            'classification' => $item->classification->value,
            'response_time_ms' => $item->responseTimeMs,
            'note' => $this->composeNote($item),
            'is_ignored' => $item->classification === OutcomeClassification::Ignored,
            'parameters' => $item->resolvedParameters,
            'meta' => array_filter([
                'ignored_reason' => $item->ignoredReason,
                'error_message' => $item->safeErrorSummary,
            ], static fn (mixed $v): bool => $v !== null && $v !== ''),
        ]);
    }

    private function composeNote(CheckResultData $item): ?string
    {
        $parts = array_filter([$item->ignoredReason, $item->safeErrorSummary]);
        if ($parts === []) {
            return null;
        }

        return implode(' · ', $parts);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function configSnapshot(): ?array
    {
        $cfg = config('sanity-check');

        return is_array($cfg) ? $cfg : null;
    }
}
