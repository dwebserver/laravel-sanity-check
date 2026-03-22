<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\DTOs;

/**
 * Optional overrides for a single orchestrated run (e.g. Artisan CLI flags).
 */
final class RunOrchestratorOptions
{
    /**
     * @param  list<string>  $onlyPatterns  When non-empty, each candidate must match at least one pattern (Str::is on name or URI).
     * @param  list<string>  $exceptPatterns  Routes matching any pattern are removed after config filtering.
     */
    public function __construct(
        public readonly ?bool $persist = null,
        public readonly array $onlyPatterns = [],
        public readonly array $exceptPatterns = [],
        public readonly ?int $limit = null,
    ) {
    }
}
