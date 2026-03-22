<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\DTOs;

/**
 * Aggregate metadata and counters for a completed sanity run.
 *
 * @phpstan-type LegacyCounts array{'2xx': int, '3xx': int, '4xx': int, '5xx': int, ignored: int}
 * @phpstan-type LegacyRates array{'2xx': float, '3xx': float, '4xx': float, '5xx': float, ignored: float}
 */
final class RunSummaryData
{
    /**
     * @param  array{'2xx': int, '3xx': int, '4xx': int, '5xx': int, ignored: int}  $counts
     * @param  array{'2xx': float, '3xx': float, '4xx': float, '5xx': float, ignored: float}  $ratesPercent
     */
    public function __construct(
        public readonly string $uuid,
        public readonly string $environment,
        public readonly string $trigger,
        public readonly ?int $triggeredByUserId,
        public readonly ?string $executedById,
        public readonly ?string $executedByType,
        public readonly float $startedAt,
        public readonly float $finishedAt,
        public readonly int $durationMs,
        public readonly int $totalRoutes,
        public readonly int $testedRoutes,
        public readonly int $ignoredRoutes,
        public readonly int $successCount,
        public readonly int $redirectCount,
        public readonly int $clientErrorCount,
        public readonly int $serverErrorCount,
        public readonly float $successRate,
        public readonly array $counts,
        public readonly array $ratesPercent,
    ) {
    }

    /**
     * @return array{'2xx': int, '3xx': int, '4xx': int, '5xx': int, ignored: int}
     */
    public function legacyCountSummary(): array
    {
        return $this->counts;
    }

    /**
     * Alias retained for callers that still refer to “total checks”.
     */
    public function totalChecks(): int
    {
        return $this->totalRoutes;
    }
}
