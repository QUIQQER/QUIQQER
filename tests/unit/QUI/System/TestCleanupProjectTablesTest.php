<?php

declare(strict_types=1);

namespace QUI\System;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class TestCleanupProjectTablesTest extends TestCase
{
    public function testCleanupKeepsTablesOfOtherProjectsWithTheSamePrefix(): void
    {
        $tables = array_map(static fn(string $name): string => QUI_DB_PRFX . $name, [
            'phpunit_de_sites',
            'phpunit_media',
            'phpunit_contact_custom',
            'phpunit_45_de_sites',
            'phpunit_45_media',
            'phpunit_45_archive_de_sites',
            'phpunit45_de_sites',
            'other_de_sites'
        ]);

        self::assertSame(array_slice($tables, 0, 3), (new ReflectionMethod(TestCleanup::class, 'projectTables'))->invoke(
            null,
            'phpunit',
            $tables,
            ['phpunit', 'phpunit_45', 'phpunit_45_archive', 'phpunit45', 'other']
        ));
    }

    public function testCleanupOfNumberedProjectRequiresTheCompleteProjectPrefix(): void
    {
        $tables = array_map(static fn(string $name): string => QUI_DB_PRFX . $name, [
            'phpunit_4_de_sites',
            'phpunit_4_media',
            'phpunit_45_de_sites',
            'phpunit_4_nested_de_sites',
            'phpunit_de_sites'
        ]);

        // The target has already been removed from projects.ini.php when tables are selected.
        self::assertSame(array_slice($tables, 0, 2), (new ReflectionMethod(TestCleanup::class, 'projectTables'))->invoke(
            null,
            'phpunit_4',
            $tables,
            ['phpunit', 'phpunit_45', 'phpunit_4_nested']
        ));
    }
}
