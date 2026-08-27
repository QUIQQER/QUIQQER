<?php

declare(strict_types=1);

namespace QUI\MCP;

use PHPUnit\Framework\TestCase;
use QUI\AI\MCP\Server;

class McpServerAvailabilityTest extends TestCase
{
    public function testMcpServerContractIsAvailableWithoutOptionalPackage(): void
    {
        self::assertTrue(class_exists(Server::class));
        self::assertTrue(method_exists(Server::class, 'getRequestUser'));
        self::assertTrue(property_exists(Server::class, 'RequestUser'));
    }
}
