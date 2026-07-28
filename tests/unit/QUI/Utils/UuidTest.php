<?php

namespace QUI\Utils;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid as RamseyUuid;

class UuidTest extends TestCase
{
    public function testGetReturnsVersionOneUuid(): void
    {
        $Uuid = RamseyUuid::fromString(Uuid::get());

        $this->assertSame(1, $Uuid->getFields()->getVersion());
    }
}
