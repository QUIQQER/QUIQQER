<?php

declare(strict_types=1);

namespace QUI\Cache;

use PHPUnit\Framework\TestCase;

final class LongTermCacheInvalidationTest extends TestCase
{
    public function testClearingAParentInvalidatesLoadedChildrenButKeepsSiblingPrefixes(): void
    {
        $prefix = 'phpunit/longterm/' . bin2hex(random_bytes(8));

        try {
            LongTermCache::set($prefix . '/packages/types', 'old-list');
            LongTermCache::set($prefix . '/packages-extra/types', 'unrelated-list');
            self::assertSame('old-list', LongTermCache::get($prefix . '/packages/types'));
            self::assertSame('unrelated-list', LongTermCache::get($prefix . '/packages-extra/types'));

            LongTermCache::clear($prefix . '/packages');

            self::assertSame('unrelated-list', LongTermCache::get($prefix . '/packages-extra/types'));
            $this->expectException(MissException::class);
            LongTermCache::get($prefix . '/packages/types');
        } finally {
            LongTermCache::clear($prefix);
        }
    }
}
