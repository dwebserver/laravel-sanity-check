<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\DTOs;

/**
 * Per-run flags derived from framework and package configuration.
 */
final class RunContextData
{
    public function __construct(
        public readonly string $environment,
        public readonly string $trigger,
        public readonly ?int $triggeredByUserId,
        public readonly ?string $executedById,
        public readonly ?string $executedByType,
        public readonly bool $saveRuns,
        public readonly bool $ignoreUnresolvableRoutes,
        public readonly bool $debug,
    ) {
    }
}
