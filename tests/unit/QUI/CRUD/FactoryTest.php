<?php

namespace QUI\CRUD;

use PHPUnit\Framework\TestCase;
use QUI;

require_once __DIR__ . '/FactoryTestAccessibleFactory.php';

class FactoryTest extends TestCase
{
    public function testSingleLimitValueSetsMaxResults(): void
    {
        $QueryBuilder = QUI::getQueryBuilder();

        $Factory = new FactoryTestAccessibleFactory();
        $Factory->applyTestQueryParameters($QueryBuilder, [
            'limit' => 10
        ]);

        $this->assertSame(0, $QueryBuilder->getFirstResult());
        $this->assertSame(10, $QueryBuilder->getMaxResults());
    }

    public function testCommaSeparatedLimitValueSetsOffsetAndMaxResults(): void
    {
        $QueryBuilder = QUI::getQueryBuilder();

        $Factory = new FactoryTestAccessibleFactory();
        $Factory->applyTestQueryParameters($QueryBuilder, [
            'limit' => '20,10'
        ]);

        $this->assertSame(20, $QueryBuilder->getFirstResult());
        $this->assertSame(10, $QueryBuilder->getMaxResults());
    }
}
