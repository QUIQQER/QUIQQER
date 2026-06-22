<?php

namespace QUI\CRUD;

use Doctrine\DBAL\Query\QueryBuilder;

class FactoryTestAccessibleFactory extends Factory
{
    public function getDataBaseTableName(): string
    {
        return 'test_table';
    }

    public function getChildAttributes(): array
    {
        return [];
    }

    public function getChildClass(): string
    {
        return Child::class;
    }

    public function applyTestQueryParameters(QueryBuilder $QueryBuilder, array $queryParams): void
    {
        $this->applyQueryParameters($QueryBuilder, $queryParams);
    }
}
