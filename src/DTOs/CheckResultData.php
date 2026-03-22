<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\DTOs;

use DynamicWeb\SanityCheck\Enums\OutcomeClassification;

/**
 * Normalized result for one route check, safe for dashboards and exports.
 */
final class CheckResultData
{
    /**
     * @param  array<string, string>|null  $resolvedParameters
     */
    public function __construct(
        public readonly ?string $routeName,
        public readonly string $uriTemplate,
        public readonly string $method,
        public readonly ?string $resolvedUri,
        public readonly ?int $statusCode,
        public readonly OutcomeClassification $classification,
        public readonly ?int $responseTimeMs,
        public readonly ?string $ignoredReason,
        public readonly ?string $safeErrorSummary,
        public readonly bool $wasExecuted,
        public readonly ?string $action,
        public readonly ?array $resolvedParameters,
    ) {
    }

    public function isIgnored(): bool
    {
        return $this->classification === OutcomeClassification::Ignored;
    }

    /**
     * @return array<string, mixed>
     */
    public function toLegacyRow(): array
    {
        return [
            'route_name' => $this->routeName,
            'uri' => $this->uriTemplate,
            'method' => $this->method,
            'resolved_uri' => $this->resolvedUri,
            'status_code' => $this->statusCode,
            'classification' => $this->classification->toLegacyBucket(),
            'response_time_ms' => $this->responseTimeMs,
            'ignored_reason' => $this->ignoredReason,
            'error_message' => $this->safeErrorSummary,
            'is_ignored' => $this->isIgnored(),
            'was_executed' => $this->wasExecuted,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toExportRow(): array
    {
        return array_merge($this->toLegacyRow(), [
            'classification_semantic' => $this->classification->value,
            'action' => $this->action,
            'parameters' => $this->resolvedParameters,
        ]);
    }
}
