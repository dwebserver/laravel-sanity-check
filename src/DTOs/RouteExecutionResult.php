<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\DTOs;

use DynamicWeb\SanityCheck\Contracts\RouteTesterInterface;

/**
 * Raw outcome from {@see RouteTesterInterface} before classification.
 */
final class RouteExecutionResult
{
    public function __construct(
        public readonly ?string $resolvedPath,
        public readonly ?int $statusCode,
        public readonly ?int $responseTimeMs,
        public readonly ?string $rawErrorMessage,
    ) {
    }
}
