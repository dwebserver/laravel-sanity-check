<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Support;

/**
 * Prevents exception details from leaking into operator UIs unless debug mode is on.
 */
final class ErrorMessageSanitizer
{
    private const GENERIC = 'Request failed due to an unexpected error.';

    public static function summarize(?string $rawMessage, bool $debugEnabled): ?string
    {
        if ($rawMessage === null || $rawMessage === '') {
            return null;
        }

        return $debugEnabled ? $rawMessage : self::GENERIC;
    }
}
