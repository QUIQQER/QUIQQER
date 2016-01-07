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
 */
abstract class Factory
{
    /**
     * @return string
     */
    abstract public function getTable();

    /**
     * @return string
     */
    abstract public function getChildClass();

    /**
     * @return array
     */
    abstract public function getChildAttributes();

    /**
     * @param array $data
     * @return QUI\CRUD\Child
     */
    public function createChild($data)
    {
        $attributes = $this->getChildAttributes();
        $childData  = array();

        foreach ($attributes as $attribute) {
            if (isset($data[$attribute])) {
                $childData[$attribute] = $data[$attribute];
            }
        }

        QUI::getDataBase()->insert($this->getTable(), $childData);

        return $this->getChild(
            QUI::getDataBase()->getPDO()->lastInsertId()
        );
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
            'from' => $this->getTable(),
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
            $Child = new $childClass($result[0]['id'], $this);

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
            'from' => $this->getTable()
        );

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
