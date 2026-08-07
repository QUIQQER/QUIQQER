<?php

declare(strict_types=1);

namespace QUI\MCP;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\MCP\System\GetSystemInfo;
use ReflectionMethod;

class SystemInfoToolTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function databaseProvider(): iterable
    {
        yield 'MariaDB' => [
            'Doctrine\\DBAL\\Platforms\\MariaDBPlatform',
            '10.11.14-MariaDB',
            'MariaDB'
        ];
        yield 'MySQL' => [
            'Doctrine\\DBAL\\Platforms\\MySQL84Platform',
            '8.4.6',
            'MySQL'
        ];
        yield 'PostgreSQL' => [
            'Doctrine\\DBAL\\Platforms\\PostgreSQLPlatform',
            '17.4',
            'PostgreSQL'
        ];
        yield 'unknown' => ['CustomPlatform', '1.0', 'unknown'];
    }

    #[DataProvider('databaseProvider')]
    public function testDatabaseNameIsDetected(
        string $platformClass,
        string $serverVersion,
        string $expected
    ): void {
        $Method = new ReflectionMethod(GetSystemInfo::class, 'detectDatabaseName');

        self::assertSame($expected, $Method->invoke(null, $platformClass, $serverVersion));
    }

    public function testPackagesAreReducedToProtectedMetadataAndSorted(): void
    {
        $Method = new ReflectionMethod(GetSystemInfo::class, 'normalizePackages');
        $packages = $Method->invoke(null, [
            [
                'name' => 'vendor/z-package',
                'version' => '2.0.0',
                'type' => 'quiqqer-module',
                'description' => 'Must not be exposed',
                'image' => '/internal/path/image.png'
            ],
            [
                'name' => 'vendor/a-package',
                'version' => '1.0.0',
                'type' => 'quiqqer-template'
            ],
            ['version' => '3.0.0']
        ]);

        self::assertSame([
            [
                'name' => 'vendor/a-package',
                'version' => '1.0.0',
                'type' => 'quiqqer-template'
            ],
            [
                'name' => 'vendor/z-package',
                'version' => '2.0.0',
                'type' => 'quiqqer-module'
            ]
        ], $packages);
    }
}
