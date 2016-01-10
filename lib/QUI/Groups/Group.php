<?php

/**
 * This file contains QUI\Groups\Group
 */

namespace QUI\Groups;

use QUI;
use QUI\Utils\Security\Orthos;

/**
 * A group
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.groups
 * @licence For copyright and license information, please view the /README.md
 */
class Group extends QUI\QDOM
{
    /**
     * Settings of the group
     *
     * @var array
     */
    protected $settings;

    /**
     * The group root id
     *
     * @var integer
     */
    protected $rootid;

    /**
     * internal right cache
     *
     * @var array
     */
    protected $rights = array();

    /**
     * internal children id cache
     *
     * @var array
     */
    protected $childrenids = null;

    /**
     * internal parentid cache
     *
     * @var array
     */
    protected $parentids = null;

    /**
     * constructor
     *
     * @param integer $id - Group ID
     *
     * @throws QUI\Exception
     */
    public function __construct($id)
    {
        $this->rootid = QUI::conf('globals', 'root');
        parent::setAttribute('id', (int)$id);

        try {
            // falls cache vorhanden ist
            $cache = QUI\Cache\Manager::get('qui/groups/group/' . $this->getId());

            $this->parentids = $cache['parentids'];
            $this->rights    = $cache['rights'];

            if (is_array($cache['attributes'])) {
                foreach ($cache['attributes'] as $key => $value) {
                    $this->setAttribute($key, $value);
                }
            }

            if (!empty($cache)) {
                return;
            }

        } catch (QUI\Cache\Exception $Exception) {
        }

        $result = QUI::getDataBase()->fetch(array(
            'from' => Manager::Table(),
            'where' => array(
                'id' => $this->getId()
            ),
            'limit' => '1'
        ));

        if (!isset($result[0])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.qui.group.doesnt.exist'
                ),
                404
            );
        }

        foreach ($result[0] as $key => $value) {
            $this->setAttribute($key, $value);
        }

        // rechte setzen
        if ($this->getAttribute('rights')) {
            $this->rights = json_decode($this->getAttribute('rights'), true);
        }

        $this->createCache();
    }

    /**
     * Deletes the group and sub-groups
     *
     * @todo alle Beziehungen in den Seiten müssen neu gesetzt werden
     * @return boolean
     * @throws QUI\Exception
     */
    public function delete()
    {
        // Rootgruppe kann nicht gelöscht werden
        if ((int)QUI::conf('globals', 'root') === $this->getId()) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.qui.group.root.delete'
                )
            );
        }

        // Rekursiv die Kinder bekommen
        $children = $this->getChildrenIds(true);

        // Kinder löschen
        foreach ($children as $child) {
            QUI::getDataBase()->exec(array(
                'delete' => true,
                'from' => Manager::Table(),
                'where' => array(
                    'id' => $child
                )
            ));
        }

        // Sich selbst löschen
        QUI::getDataBase()->exec(array(
            'delete' => true,
            'from' => Manager::Table(),
            'where' => array(
                'id' => $this->getId()
            )
        ));

        QUI\Cache\Manager::clear('qui/groups/group/' . $this->getId());
    }

    /**
     * set a group attribute
     * ID cannot be set
     *
     * @param string $key - Attribute name
     * @param string|boolean|integer|array $value - value
     *
     * @return boolean
     */
    public function setAttribute($key, $value)
    {
        if ($key == 'id') {
            return false;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Returns the Group-ID
     *
     * @return integer
     */
    public function getId()
    {
        return $this->getAttribute('id');
    }

    /**
     * saves the group
     * All attributes are set in the database
     */
    public function save()
    {
        $this->rights = QUI::getPermissionManager()
            ->getRightParamsFromGroup($this);

        // Felder bekommen
        QUI::getDataBase()->update(
            Manager::Table(),
            array(
                'name' => $this->getAttribute('name'),
                'toolbar' => $this->getAttribute('toolbar'),
                'admin' => $this->rootid == $this->getId() ? 1
                    : (int)$this->getAttribute('admin'),
                'rights' => json_encode($this->rights)
            ),
            array('id' => $this->getId())
        );

        $this->createCache();
    }

    /**
     * Activate the group
     */
    public function activate()
    {
        QUI::getDataBase()->update(
            Manager::Table(),
            array('active' => 1),
            array('id' => $this->getId())
        );

        $this->setAttribute('active', 1);
        $this->createCache();
    }

    /**
     * deactivate the group
     */
    public function deactivate()
    {
        QUI::getDataBase()->update(
            Manager::Table(),
            array('active' => 0),
            array('id' => $this->getId())
        );

        $this->setAttribute('active', 0);
        $this->createCache();
    }

    /**
     * Is the group active?
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->getAttribute('active') ? true : false;
    }

    /**
     * Has the group the right?
     *
     * @param string $right
     *
     * @return boolean|string
     * @deprecated
     */
    public function hasRight($right)
    {
        return $this->hasPermission($right);
    }

    /**
     * Has the group the permission?
     *
     * @param string $permission
     *
     * @return boolean|string
     */
    public function hasPermission($permission)
    {
        $list = QUI::getPermissionManager()->getRightParamsFromGroup($this);

        return isset($list[$permission]) ? $list[$permission] : false;
    }

    /**
     * return all rights
     *
     * @return array
     */
    public function getRights()
    {
        return $this->rights;
    }

    /**
     * Exist the right in the group?
     *
     * @param string $right
     *
     * @return boolean
     */
    public function existsRight($right)
    {
        if ($this->existsAttribute($right)) {
            return true;
        }

        if (isset($this->rights[$right])) {
            return true;
        }

        return false;
    }

    /**
     * set a right to the group
     *
     * @param array $rights
     *
     * @throws QUI\Exception
     */
    public function setRights($rights = array())
    {
        $User = QUI::getUserBySession();

        if (!$User->isSU()) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.qui.group.no.edit.permissions'
                )
            );
        }

        foreach ($rights as $k => $v) {
            $this->rights[$k] = $v;
        }
    }

    /**
     * return the users from the group
     *
     * @param array $params - SQL Params
     *
     * @return array
     */
    public function getUsers($params = array())
    {
        $id = $this->getId();

        $params['from']  = QUI\Users\Manager::Table();
        $params['where'] = "usergroup LIKE '%,{$id},%' OR usergroup = {$id}";

        return QUI::getDataBase()->fetch($params);
    }

    /**
     * search a user by name
     *
     * @param string $username
     *
     * @return QUI\Users\User
     * @throws QUI\Exception
     * @todo rewrite -> where as array
     */
    public function getUserByName($username)
    {
        $result = QUI::getDataBase()->fetch(array(
            'select' => 'id',
            'from' => QUI\Users\Manager::Table(),
            'where' => 'username = \'' . Orthos::clearMySQL($username)
                       . '\' AND usergroup LIKE \'%,' . $this->getId() . ',%\'',
            'limit' => '1'
        ));

        if (!isset($result[0])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.qui.group.user.not.found'
                ),
                404
            );
        }

        return QUI::getUsers()->get($result[0]['id']);
    }

    /**
     * returns the user count
     *
     * @param array $params - SQL Params
     *
     * @return integer
     */
    public function countUser($params = array())
    {
        $_params = array(
            'count' => array(
                'select' => 'id',
                'as' => 'count'
            ),
            'from' => QUI\Users\Manager::Table(),
            'where' => array(
                'usergroup' => array(
                    'type' => 'LIKE',
                    'value' => ",'" . $this->getId() . "',"
                )
            )
        );

        if (isset($params['order'])) {
            $_params['order'] = $params['order'];
        }

        if (isset($params['limit'])) {
            $_params['limit'] = $params['limit'];
        }

        $result = QUI::getDataBase()->fetch($_params);

        if (isset($result[0])
            && isset($result[0]['count'])
        ) {
            return $result[0]['count'];
        }

        return 0;
    }

    /**
     * Checks if the ID is from a parent group
     *
     * @param integer $id - ID from parent
     * @param boolean $recursiv - checks recursive or not
     *
     * @return boolean
     */
    public function isParent($id, $recursiv = false)
    {
        if ($recursiv) {
            if (in_array($id, $this->parentids)) {
                return true;
            }

            return false;
        }

        if ($this->getParent() == $id) {
            return true;
        }

        return false;
    }

    /**
     * return the parent group
     *
     * @param boolean $obj - Parent Objekt (true) oder Parent-ID (false) -> (optional = true)
     *
     * @return object|integer|false
     * @throws QUI\Exception
     */
    public function getParent($obj = true)
    {
        $ids = $this->getParentIds();

        if (!isset($ids[0])) {
            return false;
        }

        if ($obj == true) {
            return QUI::getGroups()->get($ids[0]);
        }

        return $ids[0];
    }

    /**
     * Get all parent ids
     *
     * @return array
     */
    public function getParentIds()
    {
        if ($this->parentids) {
            return $this->parentids;
        }

        $this->parentids = array();

        $result = QUI::getDataBase()->fetch(array(
            'select' => 'id, parent',
            'from' => Manager::Table(),
            'where' => array(
                'id' => $this->getId()
            ),
            'limit' => 1
        ));

        $this->parentids[] = $result[0]['parent'];

        if (!empty($result[0]['parent'])) {
            $this->getParentIdsHelper($result[0]['parent']);
        }

        return $this->parentids;
    }

    /**
     * Helper method for getparents
     *
     * @param integer $id
     *
     * @ignore
     */
    private function getParentIdsHelper($id)
    {
        $result = QUI::getDataBase()->fetch(array(
            'select' => 'id, parent',
            'from' => Manager::Table(),
            'where' => array(
                'id' => (int)$id
            ),
            'limit' => 1
        ));

        if (!isset($result[0])
            || !isset($result[0]['parent'])
            || empty($result[0]['parent'])
        ) {
            return;
        }

        $this->parentids[] = $result[0]['parent'];

        $this->getParentIdsHelper($result[0]['parent']);
    }

    /**
     * Have the group subgroups?
     *
     * @return integer
     */
    public function hasChildren()
    {
        return count($this->getChildren());
    }

    /**
     * Returns the sub groups
     *
     * @param array $params - Where Parameter
     *
     * @return array
     */
    public function getChildren($params = array())
    {
        $ids      = $this->getChildrenIds(false, $params);
        $children = array();
        $Groups   = QUI::getGroups();

        foreach ($ids as $id) {
            try {
                $Child = $Groups->get($id);

                $children[] = array_merge(
                    $Child->getAttributes(),
                    array('hasChildren' => $Child->hasChildren())
                );

            } catch (QUI\Exception $Exception) {
                // nothing
            }
        }

        return $children;
    }

    /**
     * return the subgroup ids
     *
     * @param boolean $recursiv - recursiv true / false
     * @param      $params - SQL Params (limit, order)
     *
     * @return array
     */
    public function getChildrenIds($recursiv = false, $params = array())
    {
        if ($this->childrenids) {
            return $this->childrenids;
        }


        $this->childrenids = array();

        $_params = array(
            'select' => 'id',
            'from' => Manager::Table(),
            'where' => array(
                'parent' => $this->getId()
            )
        );

        if (isset($params['order'])) {
            $_params['order'] = $params['order'];
        }

        if (isset($params['limit'])) {
            $_params['limit'] = $params['limit'];
        }

        $result = QUI::getDataBase()->fetch($_params);

        if (!isset($result) || !isset($result[0])) {
            return $this->childrenids;
        }

        foreach ($result as $entry) {
            if (isset($entry['id'])) {
                $this->childrenids[] = $entry['id'];

                if ($recursiv == true) {
                    $this->getChildrenIdsHelper($entry['id']);
                }
            }
        }

        return $this->childrenids;
    }

    /**
     * Helper method for the recursiveness
     *
     * @param integer $id
     */
    private function getChildrenIdsHelper($id)
    {
        $result = QUI::getDataBase()->fetch(array(
            'select' => 'id',
            'from' => Manager::Table(),
            'where' => array(
                'parent' => $id
            )
        ));

        foreach ($result as $entry) {
            if (isset($entry['id'])) {
                $this->childrenids[] = $entry['id'];

                $this->getChildrenIdsHelper($entry['id']);
            }
        }
    }

    /**
     * Create a subgroup
     *
     * @param string $name - name of the subgroup
     *
     * @return QUI\Groups\Group
     * @throws QUI\Exception
     */
    public function createChild($name)
    {
        $create = true;
        $newid  = false;

        while ($create) {
            srand(microtime() * 1000000);
            $newid = rand(10, 1000000000);

            $result = QUI::getDataBase()->fetch(array(
                'select' => 'id',
                'from' => Manager::Table(),
                'where' => array(
                    'id' => $newid
                )
            ));

            if (!isset($result[0]) || !$result[0]['id']) {
                $create = false;
            }
        }

        if (!$newid) {
            throw new QUI\Exception('Could not create new group');
        }

        QUI::getDataBase()->insert(
            Manager::Table(),
            array(
                'id' => $newid,
                'name' => $name,
                'parent' => $this->getId(),
                'admin' => 0,
                'active' => 0
            )
        );

        $Group = QUI::getGroups()->get($newid);

        // set standard permissions
        QUI::getPermissionManager()->importPermissionsForGroup($Group);

        return $Group;
    }

    /**
     * creates the group cache
     *
     * @ignore
     */
    protected function createCache()
    {
        // Cache aufbauen
        QUI\Cache\Manager::set('qui/groups/group/' . $this->getId(), array(
            'parentids' => $this->getParentIds(),
            'attributes' => $this->getAttributes(),
            'rights' => $this->rights
        ));
    }
}
