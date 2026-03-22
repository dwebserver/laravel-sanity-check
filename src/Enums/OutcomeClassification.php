<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Enums;

/**
 * Semantic outcome for a single route check. Stored in persistence as the enum
 * value (e.g. `success`). Legacy UI buckets (2xx, 3xx, …) are derived via
 * {@see self::toLegacyBucket()}.
 */
enum OutcomeClassification: string
{
    case Success = 'success';
    case Redirect = 'redirect';
    case ClientError = 'client_error';
    case ServerError = 'server_error';
    case Ignored = 'ignored';

    /**
     * Legacy aggregate bucket keys used by migrations and the Blade UI (2xx, 3xx, …).
     */
    public function toLegacyBucket(): string
    {
        return match ($this) {
            self::Success => '2xx',
            self::Redirect => '3xx',
            self::ClientError => '4xx',
            self::ServerError => '5xx',
            self::Ignored => 'ignored',
        };
    }
}
