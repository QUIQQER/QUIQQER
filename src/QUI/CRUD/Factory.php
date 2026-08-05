<?php

/**
 * This file contains QUI\CRUD\Factory
 */

namespace QUI\CRUD;

use Doctrine\DBAL\Query\QueryBuilder;
use QUI;

use function array_key_exists;
use function count;
use function explode;
use function is_array;
use function is_string;
use function strtoupper;

/**
 * Class Factory
 * Abstraction factory for create-read-update-delete
 *
 * @event onCreateBegin
 * @event onCreateEnd
 */
abstract class Factory extends QUI\Utils\Singleton
{
    protected QUI\Events\Event $Events;

    /**
     * Factory constructor.
     */
    public function __construct()
    {
        $this->Events = new QUI\Events\Event();
    }

    /**
     * Return the number of the children
     *
     * @param array<string, mixed> $queryParams
     *
     * @throws QUI\Database\Exception
     */
    public function countChildren(array $queryParams = []): int
    {
        $QueryBuilder = $this->createQueryBuilder();
        $QueryBuilder->select('COUNT(id)');

        $this->applyQueryParameters($QueryBuilder, $queryParams, false);

        return (int)$QueryBuilder->executeQuery()->fetchOne();
    }

    abstract public function getDataBaseTableName(): string;

    /**
     * Create a new child
     *
     * @param array<string, mixed> $data
     *
     * @throws QUI\Exception
     */
    public function createChild(array $data = []): Child
    {
        $attributes = $this->getChildAttributes();
        $childData = [];

        foreach ($attributes as $attribute) {
            if ($attribute == 'id') {
                continue;
            }

            if (array_key_exists($attribute, $data)) {
                $childData[$attribute] = $data[$attribute];
            } else {
                $childData[$attribute] = '';
            }
        }

        $this->Events->fireEvent('createBegin', [&$childData]);

        $Connection = QUI::getDataBaseConnection();
        $Connection->insert(QUI\Utils\Doctrine::quoteIdentifier($this->getDataBaseTableName()), $childData);

        $Child = $this->getChild($Connection->lastInsertId());

        $Child->setAttributes($data);

        $this->Events->fireEvent('createEnd', [$Child, $data]);

        return $Child;
    }

    /**
     * @return array<int, string>
     */
    abstract public function getChildAttributes(): array;

    /**
     * Return a child
     *
     * @throws QUI\Exception
     */
    public function getChild(int | string $id): Child
    {
        $childClass = $this->getChildClass();

        $QueryBuilder = $this->createQueryBuilder();
        $result = $QueryBuilder
            ->select('*')
            ->where($QueryBuilder->expr()->eq('id', ':id'))
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAllAssociative();

        if (!isset($result[0])) {
            throw new QUI\Exception(
                ['quiqqer/core', 'exception.crud.child.not.found'],
                404
            );
        }

        $Child = new $childClass($result[0]['id'], $this);

        if ($Child instanceof QUI\CRUD\Child) {
            $Child->setAttributes($result[0]);
        }

        return $Child;
    }

    /**
     * @return class-string<Child>
     */
    abstract public function getChildClass(): string;

    protected function createQueryBuilder(): QueryBuilder
    {
        return QUI::getQueryBuilder()
            ->from(QUI\Utils\Doctrine::quoteIdentifier($this->getDataBaseTableName()));
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    protected function applyQueryParameters(
        QueryBuilder $QueryBuilder,
        array $queryParams,
        bool $applyOrderAndLimit = true
    ): void {
        if (isset($queryParams['where']) && is_array($queryParams['where'])) {
            $this->applyWhere($QueryBuilder, $queryParams['where']);
        }

        if (isset($queryParams['where_or']) && is_array($queryParams['where_or'])) {
            $this->applyWhere($QueryBuilder, $queryParams['where_or'], true);
        }

        if (!$applyOrderAndLimit) {
            return;
        }

        if (isset($queryParams['order']) && is_string($queryParams['order'])) {
            $order = explode(' ', $queryParams['order']);
            $QueryBuilder->orderBy($order[0], $order[1] ?? null);
        }

        if (isset($queryParams['limit'])) {
            $limit = explode(',', (string)$queryParams['limit'], 2);

            if (isset($limit[1])) {
                $QueryBuilder->setFirstResult((int)$limit[0]);
                $QueryBuilder->setMaxResults((int)$limit[1]);
            } else {
                $QueryBuilder->setMaxResults((int)$limit[0]);
            }
        }
    }

    /**
     * @param array<string, mixed> $where
     */
    protected function applyWhere(QueryBuilder $QueryBuilder, array $where, bool $or = false): void
    {
        $expressions = [];
        $index = count($QueryBuilder->getParameters());

        foreach ($where as $field => $value) {
            $parameter = 'where' . $index;
            $index++;

            if (is_array($value) && isset($value['type'], $value['value'])) {
                $type = strtoupper((string)$value['type']);
                $value = $value['value'];

                if ($type === 'NOT') {
                    $expressions[] = $QueryBuilder->expr()->neq($field, ':' . $parameter);
                    $QueryBuilder->setParameter($parameter, $value);
                    continue;
                }

                if ($type === 'IN' && is_array($value)) {
                    $placeholders = [];

                    foreach ($value as $entry) {
                        $entryParameter = 'where' . $index;
                        $index++;
                        $placeholders[] = ':' . $entryParameter;
                        $QueryBuilder->setParameter($entryParameter, $entry);
                    }

                    $expressions[] = $QueryBuilder->expr()->in($field, $placeholders);
                    continue;
                }

                $expressions[] = $field . ' ' . $type . ' :' . $parameter;
                $QueryBuilder->setParameter($parameter, $value);
                continue;
            }

            $expressions[] = $QueryBuilder->expr()->eq($field, ':' . $parameter);
            $QueryBuilder->setParameter($parameter, $value);
        }

        if (empty($expressions)) {
            return;
        }

        if ($or) {
            $QueryBuilder->andWhere($QueryBuilder->expr()->or(...$expressions));
            return;
        }

        foreach ($expressions as $expression) {
            $QueryBuilder->andWhere($expression);
        }
    }

    /**
     * Return the children
     * If you want only the data, please use getChildrenData
     *
     * @param array<string, mixed> $queryParams
     *
     * @return array<int, Child>
     *
     * @throws QUI\Database\Exception
     */
    public function getChildren(array $queryParams = []): array
    {
        $result = [];

        $data = $this->getChildrenData($queryParams);
        $childClass = $this->getChildClass();

        foreach ($data as $entry) {
            $Child = new $childClass($entry['id'], $this);

            if ($Child instanceof QUI\CRUD\Child) {
                $Child->setAttributes($entry);
            }

            $result[] = $Child;
        }

        return $result;
    }

    /**
     * Return the children data
     *
     * @param array<string, mixed> $queryParams
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws QUI\Database\Exception
     */
    public function getChildrenData(array $queryParams = []): array
    {
        $QueryBuilder = $this->createQueryBuilder();
        $select = $queryParams['select'] ?? ['*'];

        if (is_string($select)) {
            $select = [$select];
        }

        $QueryBuilder->select(...$select);
        $this->applyQueryParameters($QueryBuilder, $queryParams);

        // @todo filter where and where_or and select with getChildAttributes

        return $QueryBuilder->executeQuery()->fetchAllAssociative();
    }
}
