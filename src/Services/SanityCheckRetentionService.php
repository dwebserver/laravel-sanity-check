<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Services;

use DynamicWeb\SanityCheck\Models\SanityCheckRun;
use Illuminate\Support\Carbon;

/**
 * Deletes stored runs according to age and maximum row count.
 */
final class SanityCheckRetentionService
{
    public function __construct(
        private readonly int $retentionDays,
        private readonly int $maxSavedRuns,
    ) {
    }

    public function prune(): void
    {
        if ($this->retentionDays > 0) {
            $cutoff = Carbon::now()->subDays($this->retentionDays);
            SanityCheckRun::query()->where('created_at', '<', $cutoff)->delete();
        }

        if ($this->maxSavedRuns <= 0) {
            return;
        }

        $keepIds = SanityCheckRun::query()
            ->orderByDesc('id')
            ->limit($this->maxSavedRuns)
            ->pluck('id')
            ->all();

        if ($keepIds === []) {
            return;
        }

        SanityCheckRun::query()->whereNotIn('id', $keepIds)->delete();
    }
}
