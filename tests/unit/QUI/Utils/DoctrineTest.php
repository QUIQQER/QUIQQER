<?php

namespace QUI\Utils;

use PHPUnit\Framework\TestCase;
use QUI;

class DoctrineTest extends TestCase
{
    public function testSingleLimitValueSetsMaxResults(): void
    {
        $QueryBuilder = QUI::getQueryBuilder();

        Doctrine::parseDbArrayToQueryBuilder($QueryBuilder, [
            'limit' => 1
        ]);

        $this->assertSame(0, $QueryBuilder->getFirstResult());
        $this->assertSame(1, $QueryBuilder->getMaxResults());
    }

    public function testCommaSeparatedLimitValueSetsOffsetAndMaxResults(): void
    {
        $QueryBuilder = QUI::getQueryBuilder();

        Doctrine::parseDbArrayToQueryBuilder($QueryBuilder, [
            'limit' => '2,5'
        ]);

        $this->assertSame(2, $QueryBuilder->getFirstResult());
        $this->assertSame(5, $QueryBuilder->getMaxResults());
    }

    public function testArrayWhereConditionsAreCombinedAndSupportNot(): void
    {
        $QueryBuilder = QUI::getQueryBuilder()
            ->select('id')
            ->from('users');

        Doctrine::parseDbArrayToQueryBuilder($QueryBuilder, [
            'where' => [
                'su' => 1,
                'uuid' => [
                    'type' => 'NOT',
                    'value' => 'current-user-uuid'
                ]
            ]
        ]);

        $sql = $QueryBuilder->getSQL();

        $this->assertStringContainsString('su = :wp0', $sql);
        $this->assertStringContainsString('uuid <> :wp1', $sql);
        $this->assertSame(1, $QueryBuilder->getParameter('wp0'));
        $this->assertSame('current-user-uuid', $QueryBuilder->getParameter('wp1'));
    }
}
