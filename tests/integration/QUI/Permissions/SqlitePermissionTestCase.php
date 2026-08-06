<?php

namespace QUI\Permissions;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use QUI;
use ReflectionProperty;

abstract class SqlitePermissionTestCase extends TestCase
{
    protected Connection $Connection;

    private ?Connection $previousConnection;
    private ?Manager $previousPermissionManager;

    protected function setUp(): void
    {
        $ConnectionProperty = new ReflectionProperty(QUI::class, 'QueryBuilder');

        $this->previousConnection = $ConnectionProperty->getValue();
        $this->previousPermissionManager = QUI::$Rights;
        $this->Connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true
        ]);

        $ConnectionProperty->setValue(null, $this->Connection);
        QUI::$Rights = null;
    }

    protected function tearDown(): void
    {
        $ConnectionProperty = new ReflectionProperty(QUI::class, 'QueryBuilder');
        $ConnectionProperty->setValue(null, $this->previousConnection);

        QUI::$Rights = $this->previousPermissionManager;
        $this->Connection->close();
    }

    protected function createPermissionSchema(): void
    {
        Manager::setup();
    }
}
