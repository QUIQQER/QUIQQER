<?php

namespace QUI\Projects;

use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ProjectSchemaTest extends TestCase
{
    public function testSiteIndexesUseDistinctGeneratedNamesAcrossTables(): void
    {
        $FirstTable = $this->createSitesTable('first_project_de_sites');
        $SecondTable = $this->createSitesTable('second_project_en_sites');

        $this->assertCount(8, $FirstTable->getIndexes());
        $this->assertCount(8, $SecondTable->getIndexes());
        $this->assertSame(
            [],
            array_intersect(array_keys($FirstTable->getIndexes()), array_keys($SecondTable->getIndexes()))
        );
        $this->assertFalse($FirstTable->hasIndex('name'));
        $this->assertFalse($SecondTable->hasIndex('name'));
    }

    public function testSiteIndexesRecognizeExistingIndexByColumn(): void
    {
        $Table = new Table('project_de_sites');
        $this->invokeAddSitesColumns($Table);
        $Table->addIndex(['name'], 'legacy_site_name_index');

        $this->invokeAddSitesIndexes($Table);

        $this->assertCount(8, $Table->getIndexes());
        $this->assertTrue($Table->hasIndex('legacy_site_name_index'));
    }

    private function invokeAddSitesColumns(Table $Table): void
    {
        $Reflection = new ReflectionClass(Project::class);
        $Method = $Reflection->getMethod('addSitesColumns');
        $Method->invoke(null, $Table);
    }

    private function invokeAddSitesIndexes(Table $Table): void
    {
        $Reflection = new ReflectionClass(Project::class);
        $Method = $Reflection->getMethod('addSitesIndexes');
        $Method->invoke(null, $Table);
    }

    private function createSitesTable(string $tableName): Table
    {
        $Table = new Table($tableName);
        $this->invokeAddSitesColumns($Table);
        $this->invokeAddSitesIndexes($Table);

        return $Table;
    }
}
