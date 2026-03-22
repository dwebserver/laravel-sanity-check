<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Unit;

use DynamicWeb\SanityCheck\Support\EphemeralRunCache;
use PHPUnit\Framework\TestCase;

final class EphemeralRunCacheTest extends TestCase
{
    public function test_key_is_deterministic_per_uuid(): void
    {
        $uuid = 'aaaaaaaa-bbbb-4ccc-dddd-eeeeeeeeeeee';
        $this->assertSame(
            'sanity-check:ephemeral:'.$uuid,
            EphemeralRunCache::key($uuid)
        );
    }
}
