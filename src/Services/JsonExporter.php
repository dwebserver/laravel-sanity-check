<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Services;

use DynamicWeb\SanityCheck\DTOs\CheckResultData;
use DynamicWeb\SanityCheck\DTOs\RunSummaryData;

/**
 * Normalizes run output for JSON APIs and downloads.
 */
final class JsonExporter
{
    /**
     * @param  list<CheckResultData>  $items
     * @return array<string, mixed>
     */
    public function build(RunSummaryData $summary, array $items): array
    {
        return [
            'uuid' => $summary->uuid,
            'environment' => $summary->environment,
            'trigger' => $summary->trigger,
            'triggered_by_user_id' => $summary->triggeredByUserId,
            'executed_by_id' => $summary->executedById,
            'executed_by_type' => $summary->executedByType,
            'counts' => $summary->counts,
            'rates_percent' => $summary->ratesPercent,
            'total_checks' => $summary->totalChecks(),
            'duration_ms' => $summary->durationMs,
            'total_routes' => $summary->totalRoutes,
            'tested_routes' => $summary->testedRoutes,
            'ignored_routes' => $summary->ignoredRoutes,
            'success_count' => $summary->successCount,
            'redirect_count' => $summary->redirectCount,
            'client_error_count' => $summary->clientErrorCount,
            'server_error_count' => $summary->serverErrorCount,
            'success_rate' => $summary->successRate,
            'started_at' => $summary->startedAt,
            'finished_at' => $summary->finishedAt,
            'items' => array_map(
                static fn (CheckResultData $row) => $row->toExportRow(),
                $items
            ),
        ];
    }
}
