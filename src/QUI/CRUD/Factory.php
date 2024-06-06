<?php

/**
 * This file contains QUI\CRUD\Factory
 */

namespace QUI\CRUD;

use QUI;

use function array_key_exists;
use function is_array;

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
     * @throws QUI\Database\Exception
     */
    public function countChildren(array $queryParams = []): int
    {
        $query = [
            'from' => $this->getDataBaseTableName(),
            'count' => [
                'select' => 'id',
                'as' => 'id'
            ]
        ];

        if (!is_array($queryParams)) {
            $queryParams = [];
        }

        if (isset($queryParams['where'])) {
            $query['where'] = $queryParams['where'];
        }

        if (isset($queryParams['where_or'])) {
            $query['where_or'] = $queryParams['where_or'];
        }

        $count = QUI::getDataBase()->fetch($query);

        if (isset($count[0]['id'])) {
            return (int)$count[0]['id'];
        }

        return 0;
    }

    abstract public function getDataBaseTableName(): string;

    /**
     * Create a new child
     *
     * @throws QUI\Exception
     */
    public function createChild(array $data = []): Child
    {
        $attributes = $this->getChildAttributes();
        $childData = [];

        if (!is_array($data)) {
            $data = [];
        }

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

        QUI::getDataBase()->insert($this->getDataBaseTableName(), $childData);

        $Child = $this->getChild(
            QUI::getDataBase()->getPDO()->lastInsertId()
        );

        $Child->setAttributes($data);

        $this->Events->fireEvent('createEnd', [$Child, $data]);

        return $Child;
    }

    abstract public function getChildAttributes(): array;

    /**
     * Return a child
     *
     * @throws QUI\Exception
     */
    public function getChild(int|string $id): Child
    {
        $childClass = $this->getChildClass();

        $result = QUI::getDataBase()->fetch([
            'from' => $this->getDataBaseTableName(),
            'where' => [
                'id' => $id
            ],
            'limit' => 1
        ]);

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

    abstract public function getChildClass(): string;

    /**
     * Return the children
     * If you want only the data, please use getChildrenData
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
     * @throws QUI\Database\Exception
     */
    public function getChildrenData(array $queryParams = []): array
    {
        $query = [
            'from' => $this->getDataBaseTableName()
        ];

        if (!is_array($queryParams)) {
            $queryParams = [];
        }

        if (isset($queryParams['select'])) {
            $query['select'] = $queryParams['select'];
        }

        if (isset($queryParams['where'])) {
            $query['where'] = $queryParams['where'];
        }

        if (isset($queryParams['where_or'])) {
            $query['where_or'] = $queryParams['where_or'];
        }

        if (isset($queryParams['order'])) {
            $query['order'] = $queryParams['order'];
        }

        if (isset($queryParams['limit'])) {
            $query['limit'] = $queryParams['limit'];
        }

        // @todo filter where and where_or and select with getChildAttributes

        return QUI::getDataBase()->fetch($query);
    }
}
