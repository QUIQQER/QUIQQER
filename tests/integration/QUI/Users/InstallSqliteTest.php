<?php

namespace QUI\Users;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\StringType;
use PHPUnit\Framework\TestCase;
use QUI;
use ReflectionClass;
use ReflectionProperty;

class InstallSqliteTest extends TestCase
{
    private Connection $Connection;

    private ?Connection $previousConnection;

    protected function setUp(): void
    {
        $ConnectionProperty = new ReflectionProperty(QUI::class, 'QueryBuilder');

        $this->previousConnection = $ConnectionProperty->getValue();
        $this->Connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true
        ]);

        $ConnectionProperty->setValue(null, $this->Connection);
    }

    protected function tearDown(): void
    {
        $ConnectionProperty = new ReflectionProperty(QUI::class, 'QueryBuilder');
        $ConnectionProperty->setValue(null, $this->previousConnection);

        $this->Connection->close();
    }

    public function testUserColumnSetupMigratesLegacyIndexedAddressColumn(): void
    {
        $SchemaManager = $this->Connection->createSchemaManager();
        $tableName = Manager::table();
        $LegacyTable = new Table($tableName);

        $LegacyTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $LegacyTable->addColumn('address', 'integer', ['notnull' => true]);
        $LegacyTable->setPrimaryKey(['id']);
        $LegacyTable->addIndex(['address'], 'address');
        $SchemaManager->createTable($LegacyTable);

        (new Manager())->setup();

        $MigratedTable = $SchemaManager->introspectTable($tableName);
        $AddressColumn = $MigratedTable->getColumn('address');

        $this->assertInstanceOf(StringType::class, $AddressColumn->getType());
        $this->assertSame(50, $AddressColumn->getLength());
        $this->assertFalse($AddressColumn->getNotnull());
        $this->assertNull($AddressColumn->getDefault());
        $this->assertTrue($MigratedTable->hasIndex('address'));
    }

    public function testGroupParentIndexUsesGeneratedNameAndRecognizesExistingColumnIndex(): void
    {
        $SchemaManager = $this->Connection->createSchemaManager();
        $OtherTable = new Table('other_parents');
        $OtherTable->addColumn('parent', 'string');
        $OtherTable->addIndex(['parent'], 'parent');
        $SchemaManager->createTable($OtherTable);

        $GroupTable = new Table('test_groups');
        $GroupTable->addColumn('parent', 'string');
        $SchemaManager->createTable($GroupTable);

        $this->invokeEnsureIndex('test_groups', 'parent');

        $IndexedGroupTable = $SchemaManager->introspectTable('test_groups');
        $this->assertCount(1, $IndexedGroupTable->getIndexes());
        $this->assertFalse($IndexedGroupTable->hasIndex('parent'));

        $this->invokeEnsureIndex('test_groups', 'parent');

        $this->assertCount(
            1,
            $SchemaManager->introspectTable('test_groups')->getIndexes()
        );
    }

    private function invokeEnsureIndex(string $tableName, string $columnName): void
    {
        $Reflection = new ReflectionClass(Install::class);
        $Method = $Reflection->getMethod('ensureIndex');
        $Method->invoke(null, $this->Connection, $tableName, $columnName);
    }
}
