<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\DTOs;

use DynamicWeb\SanityCheck\Services\RunOrchestrator;

/**
 * Complete output of {@see RunOrchestrator}.
 */
final class RunExecutionBundle
{
    /**
     * @param  list<CheckResultData>  $items
     */
    public function __construct(
        public readonly RunSummaryData $summary,
        public readonly array $items,
    ) {
    }
}
