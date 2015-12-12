<?php

/**
 * This file contains \QUI\Groups\Manager
 */

namespace QUI\Groups;

use QUI;
use QUI\Utils\Security\Orthos;

/**
 * Group Manager
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.groups
 * @licence For copyright and license information, please view the /README.md
 */
class Manager extends QUI\QDOM
{
    const TYPE_BOOL = 1;
    const TYPE_TEXT = 2;
    const TYPE_INT = 3;
    const TYPE_VARCHAR = 4;

    /**
     * internal group cache
     *
     * @var array
     */
    protected $groups;

    /**
     * Files that are to be loaded in the admin area
     *
     * @var array
     */
    protected $adminjsfiles = array();

    /**
     * Return the db table for the groups
     *
     * @return string
     */
    public static function TABLE()
    {
        return QUI_DB_PRFX . 'groups';
    }

    /**
     * Setup for groups
     */
    public function setup()
    {
        $DataBase = QUI::getDataBase();
        $Table    = $DataBase->Table();

        $Table->appendFields(self::TABLE(), array(
            'id' => 'int(11) NOT NULL',
            'name' => 'varchar(50) NOT NULL',
            'admin' => 'tinyint(2) NOT NULL',
            'parent' => 'int(11) NOT NULL',
            'active' => 'tinyint(1) NOT NULL',
            'toolbar' => 'varchar(128) NULL',
            'rights' => 'text'
        ));

        $Table->setPrimaryKey(self::TABLE(), 'id');
        $Table->setIndex(self::TABLE(), 'parent');
    }

    /**
     * Returns the first group
     *
     * @return QUI\Groups\Group
     */
    public function firstChild()
    {
        return $this->get(
            QUI::conf('globals', 'root')
        );
    }

    /**
     * Return a group by ID
     *
     * @param integer $id - ID of the Group
     *
     * @return QUI\Groups\Group
     *
     * @throws QUI\Exception
     */
    public function get($id)
    {
        $id = (int)$id;

        if ($id === 1) {
            return new Everyone();
        }

        if ($id === 0) {
            return new Guest();
        }

        if (!$id) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.qui.manager.no.groupid'
                )
            );
        }

        if (isset($this->groups[$id])) {
            return $this->groups[$id];
        }

        $this->groups[$id] = new Group($id);

        return $this->groups[$id];
    }

    /**
     * Return the name of a group
     *
     * @param integer $id - ID of the Group
     *
     * @return string
     */
    public function getGroupNameById($id)
    {
        return $this->get($id)->getAttribute('name');
    }

    /**
     * Search / Scanns the groups
     *
     * @param array $params - QUI\Database\DB params
     *
     * @return array
     */
    public function search($params = array())
    {
        return $this->searchHelper($params);
    }

    /**
     * Is the Object a Group?
     *
     * @param mixed $Group
     *
     * @return boolean
     */
    public function isGroup($Group)
    {
        if (!is_object($Group)) {
            return false;
        }

        if (get_class($Group) === 'QUI\\Groups\\Group') {
            return true;
        }

        return false;
    }

    /**
     * Count the groups
     *
     * @param array $params - QUI\Database\DB params
     *
     * @return integer
     */
    public function count($params)
    {
        $params['count'] = true;

        unset($params['limit']);
        unset($params['start']);

        $result = $this->searchHelper($params);

        if (isset($result[0]) && isset($result[0]['count'])) {
            return (int)$result[0]['count'];
        }

        return 0;
    }

    /**
     * Internal search helper
     *
     * @param array $params
     *
     * @return array
     * @ignore
     */
    protected function searchHelper($params)
    {
        $DataBase = QUI::getDataBase();
        $params   = Orthos::clearArray($params);

        $allowOrderFields = array(
            'id',
            'name',
            'admin',
            'parent',
            'active'
        );

        $allowSearchFields = array(
            'id' => true,
            'name' => true,
            'admin' => true,
            'parent' => true,
            'active' => true
        );

        $max   = 10;
        $start = 0;

        $_fields = array(
            'from' => self::TABLE()
        );

        if (isset($params['count'])) {
            $_fields['count'] = array(
                'select' => 'id',
                'as' => 'count'
            );
        }

        if (isset($params['limit'])
            || isset($params['start'])
        ) {
            if (isset($params['limit'])) {
                $max = (int)$params['limit'];
            }

            if (isset($params['start'])) {
                $start = (int)$params['start'];
            }

            $_fields['limit'] = $start . ', ' . $max;
        }

        if (isset($params['order'])
            && isset($params['field'])
            && $params['field']
            && in_array($params['field'], $allowOrderFields)
        ) {
            $_fields['order'] = $params['field'] . ' ' . $params['order'];
        }

        if (isset($params['where'])) {
            $_fields['where'] = $params['where'];
        }

        if (isset($params['search']) && !isset($params['searchSettings'])) {
            $_fields['where'] = array(
                'name' => array(
                    'type' => '%LIKE%',
                    'value' => $params['search']
                )
            );

        } elseif (isset($params['search'])
                  && isset($params['searchSettings'])
                  && is_array($params['searchSettings'])
        ) {
            foreach ($params['searchSettings'] as $field) {
                if (!isset($allowSearchFields[$field])) {
                    continue;
                }

                $_fields['where_or'][$field] = array(
                    'type' => '%LIKE%',
                    'value' => $params['search']
                );
            }
        }

        return $DataBase->fetch($_fields);
    }
}
