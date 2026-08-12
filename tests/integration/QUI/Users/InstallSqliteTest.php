<?php

namespace QUI\Users;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\StringType;
use PHPUnit\Framework\TestCase;
use QUI;
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

        Install::user();

        $MigratedTable = $SchemaManager->introspectTable($tableName);
        $AddressColumn = $MigratedTable->getColumn('address');

        $this->assertInstanceOf(StringType::class, $AddressColumn->getType());
        $this->assertSame(50, $AddressColumn->getLength());
        $this->assertFalse($AddressColumn->getNotnull());
        $this->assertNull($AddressColumn->getDefault());
        $this->assertTrue($MigratedTable->hasIndex('address'));
    }
}
