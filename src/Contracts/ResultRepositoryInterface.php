<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Contracts;

use DynamicWeb\SanityCheck\DTOs\CheckResultData;
use DynamicWeb\SanityCheck\DTOs\RunSummaryData;

interface ResultRepositoryInterface
{
    /**
     * Persist a completed run and its line items to durable storage.
     *
     * @param  list<CheckResultData>  $items
     */
    public function persist(RunSummaryData $summary, array $items): void;

    /**
     * Apply configured retention rules to stored runs.
     */
    public function applyRetention(): void;

    /**
     * Cache a non-persisted run payload for short-lived review (e.g. save_runs=false).
     *
     * @param  list<CheckResultData>  $items
     */
    public function storeEphemeral(string $uuid, RunSummaryData $summary, array $items): void;
}
