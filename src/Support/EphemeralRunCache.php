<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Support;

/**
 * Cache key helper for short-lived run payloads when persistence is disabled.
 */
final class EphemeralRunCache
{
    public static function key(string $uuid): string
    {
        return 'sanity-check:ephemeral:'.$uuid;
    }
}
