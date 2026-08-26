<?php

namespace QUI\Projects;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class MediaSchemaTest extends TestCase
{
    public function testExternalMediaColumnIsQuotedForMysqlMariaDbAndPostgresql(): void
    {
        foreach ($this->schemaPlatforms() as $Platform) {
            $Table = new Table('QUIQQER_media');
            $this->invokeAddMediaColumns($Table);

            $sql = implode("\n", $Platform->getCreateTableSQL($Table));

            $this->assertStringContainsString(
                $Platform->quoteSingleIdentifier('external') . ' ',
                $sql,
                $Platform::class
            );
            $this->assertStringNotContainsString(' external ', $sql, $Platform::class);
            $this->assertTrue($Table->hasColumn('external'), $Platform::class);
        }
    }

    public function testMediaIndexesUseDistinctGeneratedNamesAcrossTables(): void
    {
        $FirstTable = $this->createMediaTable('first_project_media');
        $SecondTable = $this->createMediaTable('second_project_media');

        $this->assertCount(9, $FirstTable->getIndexes());
        $this->assertCount(9, $SecondTable->getIndexes());
        $this->assertSame(
            [],
            array_intersect(array_keys($FirstTable->getIndexes()), array_keys($SecondTable->getIndexes()))
        );
        $this->assertFalse($FirstTable->hasIndex('name'));
        $this->assertFalse($SecondTable->hasIndex('name'));
    }

    public function testMediaIndexesRecognizeExistingIndexByColumn(): void
    {
        $Table = new Table('project_media');
        $this->invokeAddMediaColumns($Table);
        $Table->addIndex(['name'], 'legacy_media_name_index');

        $this->invokeAddMediaIndexes($Table);

        $this->assertCount(9, $Table->getIndexes());
        $this->assertTrue($Table->hasIndex('legacy_media_name_index'));
    }

    /**
     * @return list<AbstractPlatform>
     */
    private function schemaPlatforms(): array
    {
        return [
            new MySQLPlatform(),
            new MariaDBPlatform(),
            new PostgreSQLPlatform()
        ];
    }

    private function invokeAddMediaColumns(Table $Table): void
    {
        $Reflection = new ReflectionClass(Media::class);
        $Method = $Reflection->getMethod('addMediaColumns');
        $Method->invoke(null, $Table);
    }

    private function invokeAddMediaIndexes(Table $Table): void
    {
        $Reflection = new ReflectionClass(Media::class);
        $Method = $Reflection->getMethod('addMediaIndexes');
        $Method->invoke(null, $Table);
    }

    private function createMediaTable(string $tableName): Table
    {
        $Table = new Table($tableName);
        $this->invokeAddMediaColumns($Table);
        $this->invokeAddMediaIndexes($Table);

        return $Table;
    }
}
