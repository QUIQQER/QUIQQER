<?php

/**
 * This file contains QUI\CRUD\Factory
 */
namespace QUI\CRUD;

use QUI;

/**
 * Class Factory
 * Abstration factory for create-read-update-delete
 *
 * @package QUI\CRUD
 *
 * @event onCreateBegin
 * @event onCreateEnd
 */
abstract class Factory
{
    /**
     * @var QUI\Events\Event
     */
    protected $Events;

    /**
     * Factory constructor.
     */
    public function __construct()
    {
        $this->Events = new QUI\Events\Event();
    }

    /**
     * @return string
     */
    abstract public function getDataBaseTableName();

    /**
     * @return string
     */
    abstract public function getChildClass();

    /**
     * @return array
     */
    abstract public function getChildAttributes();

    /**
     * Create a new child
     *
     * @param array $data
     * @return QUI\CRUD\Child
     */
    public function createChild($data = array())
    {
        $this->Events->fireEvent('createBegin');

        $attributes = $this->getChildAttributes();
        $childData  = array();

        if (!is_array($data)) {
            $data = array();
        }

        foreach ($attributes as $attribute) {
            if ($attribute == 'id') {
                continue;
            }

            if (isset($data[$attribute])) {
                $childData[$attribute] = $data[$attribute];
            } else {
                $childData[$attribute] = '';
            }
        }

        QUI::getDataBase()->insert($this->getDataBaseTableName(), $childData);

        $Child = $this->getChild(
            QUI::getDataBase()->getPDO()->lastInsertId()
        );

        $this->Events->fireEvent('createEnd', array($Child));

        return $Child;
    }

    /**
     * Return a child
     *
     * @param integer $id
     * @return QUI\CRUD\Child
     * @throws QUI\Exception
     */
    public function getChild($id)
    {
        $childClass = $this->getChildClass();

        $result = QUI::getDataBase()->fetch(array(
            'from' => $this->getDataBaseTableName(),
            'where' => array(
                'id' => $id
            )
        ));

        if (!isset($result[0])) {
            throw new QUI\Exception(
                array(
                    'quiqqer/system',
                    'crud.child.not.found'
                ),
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
     * Return the children
     * If you want only the data, please use getChildrenData
     *
     * @param array $queryParams
     * @return array - [Child, Child, Child]
     */
    public function getChildren($queryParams = array())
    {
        $result = array();
        $data   = $this->getChildrenData($queryParams);

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
     * @param array $queryParams
     * @return array - [array, array, array]
     */
    public function getChildrenData($queryParams = array())
    {
        $query = array(
            'from' => $this->getDataBaseTableName()
        );

        if (!is_array($queryParams)) {
            $queryParams = array();
        }

        if (isset($queryParams['select'])) {
            $query['select'] = $queryParams['select'];
        }

        if (isset($queryParams['where'])) {
            $query['where'] = $queryParams['where'];
        }

        if (isset($queryParams['order'])) {
            $query['order'] = $queryParams['order'];
        }

        if (isset($queryParams['limit'])) {
            $query['limit'] = $queryParams['limit'];
        }

        return QUI::getDataBase()->fetch($query);
    }
}
