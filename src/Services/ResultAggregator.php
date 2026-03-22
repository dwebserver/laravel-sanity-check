<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Services;

use DynamicWeb\SanityCheck\DTOs\CheckResultData;
use DynamicWeb\SanityCheck\DTOs\RunContextData;
use DynamicWeb\SanityCheck\DTOs\RunSummaryData;
use DynamicWeb\SanityCheck\Enums\OutcomeClassification;

/**
 * Builds aggregate counters, legacy UI buckets, and persistence-oriented totals.
 */
final class ResultAggregator
{
    /**
     * @param  list<CheckResultData>  $items
     */
    public function summarize(
        string $uuid,
        RunContextData $context,
        float $startedAt,
        float $finishedAt,
        array $items,
    ): RunSummaryData {
        $total = count($items);
        $durationMs = (int) max(0, round(($finishedAt - $startedAt) * 1000));

        $counts = [
            '2xx' => 0,
            '3xx' => 0,
            '4xx' => 0,
            '5xx' => 0,
            'ignored' => 0,
        ];

        $successCount = 0;
        $redirectCount = 0;
        $clientErrorCount = 0;
        $serverErrorCount = 0;
        $ignoredCount = 0;
        $testedRoutes = 0;

        foreach ($items as $item) {
            $bucket = $item->classification->toLegacyBucket();
            $counts[$bucket]++;

            if ($item->wasExecuted) {
                $testedRoutes++;
            }

            match ($item->classification) {
                OutcomeClassification::Success => $successCount++,
                OutcomeClassification::Redirect => $redirectCount++,
                OutcomeClassification::ClientError => $clientErrorCount++,
                OutcomeClassification::ServerError => $serverErrorCount++,
                OutcomeClassification::Ignored => $ignoredCount++,
            };
        }

        $rates = [];
        foreach ($counts as $key => $value) {
            $rates[$key] = $total > 0 ? round($value / $total * 100, 2) : 0.0;
        }

        $successRate = $testedRoutes > 0
            ? round($successCount / $testedRoutes * 100, 2)
            : 0.0;

        $executedById = $context->executedById ?? ($context->triggeredByUserId !== null ? (string) $context->triggeredByUserId : null);

        return new RunSummaryData(
            uuid: $uuid,
            environment: $context->environment,
            trigger: $context->trigger,
            triggeredByUserId: $context->triggeredByUserId,
            executedById: $executedById,
            executedByType: $context->executedByType,
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            durationMs: $durationMs,
            totalRoutes: $total,
            testedRoutes: $testedRoutes,
            ignoredRoutes: $ignoredCount,
            successCount: $successCount,
            redirectCount: $redirectCount,
            clientErrorCount: $clientErrorCount,
            serverErrorCount: $serverErrorCount,
            successRate: $successRate,
            counts: $counts,
            ratesPercent: $rates,
        );
    }
}
