<?php

declare(strict_types=1);

namespace QUI\System;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class TestCleanupTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCleanupIsNotRegisteredInIsolatedChildProcess(): void
    {
        $Registered = new ReflectionProperty(TestCleanup::class, 'registered');

        self::assertFalse($Registered->getValue());
    }
}
