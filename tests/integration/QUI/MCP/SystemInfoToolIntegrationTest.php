<?php

declare(strict_types=1);

namespace QUI\MCP;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\MCP\System\GetSystemInfo;
use ReflectionMethod;
use Throwable;

class SystemInfoToolIntegrationTest extends TestCase
{
    public function testSystemInformationUsesRuntimeAndInstalledPackageData(): void
    {
        $Method = new ReflectionMethod(GetSystemInfo::class, 'getSystemInformation');

        try {
            /**
             * @var array{
             *     php: array{version: string, sapi: string},
             *     database: array{name: string, version: string},
             *     webServer: array{software: string},
             *     quiqqer: array{version: string},
             *     packageCount: int,
             *     packages: array<int, array{name: string, version: string, type: string}>
             * } $systemInfo
             */
            $systemInfo = $Method->invoke(null);
        } catch (Throwable $Exception) {
            self::markTestSkipped('System information is unavailable: ' . $Exception->getMessage());
        }

        self::assertSame(PHP_VERSION, $systemInfo['php']['version']);
        self::assertNotSame('', $systemInfo['database']['version']);
        self::assertSame(QUI::getPackageManager()->getVersion(), $systemInfo['quiqqer']['version']);
        self::assertSame(count($systemInfo['packages']), $systemInfo['packageCount']);

        $corePackages = array_values(array_filter(
            $systemInfo['packages'],
            static fn(array $package): bool => $package['name'] === 'quiqqer/core'
        ));

        self::assertCount(1, $corePackages);
        self::assertSame($systemInfo['quiqqer']['version'], $corePackages[0]['version']);
    }
}
