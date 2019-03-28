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

    const GUEST_ID = 0;
    const EVERYONE_ID = 1;

    /**
     * @var Everyone
     */
    protected $Everyone = null;

    /**
     * @var Guest
     */
    protected $Guest = null;

    /**
     * internal group cache
     *
     * @var array
     */
    protected $groups;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * Files that are to be loaded in the admin area
     *
     * @var array
     */
    protected $adminjsfiles = [];

    /**
     * Return the db table for the groups
     *
     * @return string
     */
    public static function table()
    {
        return QUI::getDBTableName('groups');
    }

    /**
     * Setup for groups
     */
    public function setup()
    {
        $DataBase = QUI::getDataBase();
        $Table    = $DataBase->table();

        $Table->setPrimaryKey(self::table(), 'id');
        $Table->setIndex(self::table(), 'parent');


        // Guest
        $result = QUI::getDataBase()->fetch([
            'from'  => $this->table(),
            'where' => [
                'id' => 0
            ]
        ]);

        if (!isset($result[0])) {
            QUI\System\Log::addNotice('Guest Group does not exist.');

            QUI::getDataBase()->insert($this->table(), [
                'id'   => 0,
                'name' => 'Guest'
            ]);

            QUI\System\Log::addNotice('Guest Group was created.');
        } else {
            QUI::getDataBase()->update($this->table(), [
                'name' => 'Guest'
            ], [
                'id' => 0
            ]);

            QUI\System\Log::addNotice('Guest exists only updated');
        }


        // Everyone
        $result = QUI::getDataBase()->fetch([
            'from'  => $this->table(),
            'where' => [
                'id' => 1
            ]
        ]);

        if (!isset($result[0])) {
            QUI\System\Log::addNotice('Everyone Group does not exist...');

            QUI::getDataBase()->insert($this->table(), [
                'id'   => 1,
                'name' => 'Everyone'
            ]);

            QUI\System\Log::addNotice('Everyone Group was created.');
        } else {
            QUI::getDataBase()->update($this->table(), [
                'name' => 'Everyone'
            ], [
                'id' => 1
            ]);

            QUI\System\Log::addNotice('Everyone exists');
        }

        $this->get(0)->save();
        $this->get(1)->save();
    }

    /**
     * Returns the first group
     *
     * @return QUI\Groups\Group
     */
    public function firstChild()
    {
        return $this->get(QUI::conf('globals', 'root'));
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
            if ($this->Everyone === null) {
                $this->Everyone = new Everyone();
            }

            return $this->Everyone;
        }

        if ($id === 0) {
            if ($this->Guest === null) {
                $this->Guest = new Guest();
            }

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
     * Return the db data of a group
     *
     * @param integer|string $groupId
     * @return array
     */
    public function getGroupData($groupId)
    {
        if (isset($this->data[$groupId])) {
            return $this->data[$groupId];
        }

        $groupId = (int)$groupId;

        $result = QUI::getDataBase()->fetch([
            'from'  => self::table(),
            'where' => [
                'id' => $groupId
            ],
            'limit' => 1
        ]);

        if ($groupId === 1 || $groupId === 0) {
            $this->data[$groupId] = $result;
        }

        return $result;
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
     * Get all groups
     *
     * @param boolean $objects - as objects=true, as array=false
     *
     * @return array
     */
    public function getAllGroups($objects = false)
    {
        if ($objects == false) {
            return QUI::getDataBase()->fetch([
                'from'  => self::table(),
                'order' => 'name'
            ]);
        }

        $result = [];
        $ids    = $this->getAllGroupIds();

        foreach ($ids as $id) {
            try {
                $result[] = $this->get((int)$id['id']);
            } catch (QUI\Exception $Exception) {
                // nothing
            }
        }

        return $result;
    }

    /**
     * Returns all group ids
     *
     * @return array
     */
    public function getAllGroupIds()
    {
        $result = QUI::getDataBase()->fetch([
            'select' => 'id',
            'from'   => self::table(),
            'order'  => 'name'
        ]);

        return $result;
    }

    /**
     * Search / Scanns the groups
     *
     * @param array $params - QUI\Database\DB params
     *
     * @return array
     */
    public function search($params = [])
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
        if (!\is_object($Group)) {
            return false;
        }

        return $Group instanceof Group;
    }

    /**
     * Count the groups
     *
     * @param array $params - QUI\Database\DB params
     *
     * @return integer
     */
    public function count($params = [])
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

        $allowOrderFields = [
            'id',
            'name',
            'parent',
            'active'
        ];

        $allowSearchFields = [
            'id'     => true,
            'name'   => true,
            'parent' => true,
            'active' => true
        ];

        $max   = 10;
        $start = 0;

        $_fields = [
            'from' => self::table()
        ];

        if (isset($params['count'])) {
            $_fields['count'] = [
                'select' => 'id',
                'as'     => 'count'
            ];
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

            $_fields['limit'] = $start.', '.$max;
        }

        if (isset($params['order'])
            && isset($params['field'])
            && $params['field']
            && \in_array($params['field'], $allowOrderFields)
        ) {
            $_fields['order'] = $params['field'].' '.$params['order'];
        }

        if (isset($params['where'])) {
            $_fields['where'] = $params['where'];
        }

        if (isset($params['where_or'])) {
            $_fields['where_or'] = $params['where_or'];
        }

        if (isset($params['search']) && !isset($params['searchSettings'])) {
            $_fields['where'] = [
                'name' => [
                    'type'  => '%LIKE%',
                    'value' => $params['search']
                ]
            ];
        } elseif (isset($params['search'])
                  && isset($params['searchSettings'])
                  && \is_array($params['searchSettings'])
        ) {
            foreach ($params['searchSettings'] as $field) {
                if (!isset($allowSearchFields[$field])) {
                    continue;
                }

                $_fields['where_or'][$field] = [
                    'type'  => '%LIKE%',
                    'value' => $params['search']
                ];
            }
        }

        return $DataBase->fetch($_fields);
    }
}
