<?php

/**
 * This file contains QUI\Groups\Group
 */

namespace QUI\Groups;

use QUI;

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

            if (isset($cache['parentids'])) {
                $this->parentids = $cache['parentids'];
            }

            if (isset($cache['rights'])) {
                $this->rights = $cache['rights'];
            }

            if (isset($cache['attributes']) && is_array($cache['attributes'])) {
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
            'from'  => Manager::table(),
            'where' => array(
                'id' => $this->getId()
            ),
            'limit' => '1'
        ));

        if (!isset($result[0])) {
            throw new QUI\Exception(
                array(
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

        // Extras are deprected - we need an api
        if (isset($result[0]['extra'])) {
            $extraList = $this->getListOfExtraAttributes();
            $extras    = array();
            $extraData = json_decode($result[0]['extra'], true);

            if (!is_array($extraData)) {
                $extraData = array();
            }

            foreach ($extraList as $attribute) {
                $extras[$attribute] = true;
            }

            foreach ($extraData as $attribute => $value) {
                if (isset($extras[$attribute])) {
                    $this->setAttribute($attribute, $extraData[$attribute]);
                }
            }
        }

        $this->createCache();

        QUI::getEvents()->fireEvent('groupLoad', array($this));
    }

    /**
     * Deletes the group and sub-groups
     *
     * @todo alle Beziehungen in den Seiten müssen neu gesetzt werden
     * @throws QUI\Exception
     */
    public function delete()
    {
        // Rootgruppe kann nicht gelöscht werden
        if ((int)QUI::conf('globals', 'root') === $this->getId()) {
            throw new QUI\Exception(array(
                'quiqqer/system',
                'exception.lib.qui.group.root.delete'
            ));
        }

        QUI::getEvents()->fireEvent('groupDelete', array($this));

        /**
         * Delete the group id in all users
         *
         * @param int $groupId
         */
        $deleteGidInUsers = function ($groupId) {
            if (!is_int($groupId)) {
                return;
            }

            $PDO   = QUI::getDataBase()->getPDO();
            $table = QUI\Users\Manager::table();

            $Statement = $PDO->prepare(
                "UPDATE {$table}
                SET usergroup = replace(usergroup, :search, :replace)
                WHERE usergroup LIKE :where"
            );

            $Statement->bindValue('where', '%,' . $groupId . ',%');
            $Statement->bindValue('search', ',' . $groupId . ',');
            $Statement->bindValue('replace', ',');
            $Statement->execute();
        };


        // Rekursiv die Kinder bekommen
        $children = $this->getChildrenIds(true);

        // Kinder löschen
        foreach ($children as $child) {
            QUI::getDataBase()->exec(array(
                'delete' => true,
                'from'   => Manager::table(),
                'where'  => array(
                    'id' => $child
                )
            ));

            $deleteGidInUsers($child);
        }

        $deleteGidInUsers($this->getId());

        // Sich selbst löschen
        QUI::getDataBase()->exec(array(
            'delete' => true,
            'from'   => Manager::table(),
            'where'  => array(
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
     * @return Group
     */
    public function setAttribute($key, $value)
    {
        if ($key != 'id') {
            parent::setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Return the list which extra attributes exist
     * Plugins could extend the group attributes
     *
     * look at
     * - https://dev.quiqqer.com/quiqqer/quiqqer/wikis/User-Xml
     * - https://dev.quiqqer.com/quiqqer/quiqqer/wikis/Group-Xml
     *
     * @return array
     */
    protected function getListOfExtraAttributes()
    {
        try {
            return QUI\Cache\Manager::get('group/plugin-attribute-list');
        } catch (QUI\Exception $Exception) {
        }

        $list       = QUI::getPackageManager()->getInstalled();
        $attributes = array();

        foreach ($list as $entry) {
            $plugin  = $entry['name'];
            $userXml = OPT_DIR . $plugin . '/group.xml';

            if (!file_exists($userXml)) {
                continue;
            }

            $attributes = array_merge(
                $attributes,
                $this->readAttributesFromGroupXML($userXml)
            );
        }

        QUI\Cache\Manager::set('group/plugin-attribute-list', $attributes);

        return $attributes;
    }

    /**
     * Read an user.xml and return the attributes,
     * if some extra attributes defined
     *
     * @param string $file
     *
     * @return array
     */
    protected function readAttributesFromGroupXML($file)
    {
        $Dom  = QUI\Utils\Text\XML::getDomFromXml($file);
        $Attr = $Dom->getElementsByTagName('attributes');

        if (!$Attr->length) {
            return array();
        }

        /* @var $Attributes \DOMElement */
        $Attributes = $Attr->item(0);
        $list       = $Attributes->getElementsByTagName('attribute');

        if (!$list->length) {
            return array();
        }

        $attributes = array();

        for ($c = 0; $c < $list->length; $c++) {
            $Attribute = $list->item($c);

            if ($Attribute->nodeName == '#text') {
                continue;
            }

            $attributes[] = trim($Attribute->nodeValue);
        }

        return $attributes;
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
     * Return the group name
     *
     * @return string
     */
    public function getName()
    {
        return $this->getAttribute('name');
    }

    /**
     * Return the group avatar
     *
     * @return QUI\Projects\Media\Image|false
     */
    public function getAvatar()
    {
        $avatar = $this->getAttribute('avatar');

        if (!QUI\Projects\Media\Utils::isMediaUrl($avatar)) {
            $Project = QUI::getProjectManager()->getStandard();
            $Media   = $Project->getMedia();

            return $Media->getPlaceholderImage();
        }

        try {
            return QUI\Projects\Media\Utils::getImageByUrl($avatar);
        } catch (QUI\Exception $Exception) {
        }

        $Project = QUI::getProjectManager()->getStandard();
        $Media   = $Project->getMedia();

        return $Media->getPlaceholderImage();
    }

    /**
     * saves the group
     * All attributes are set in the database
     */
    public function save()
    {
        $this->rights = QUI::getPermissionManager()->getRightParamsFromGroup($this);

        // Pluginerweiterungen
        $extra      = array();
        $attributes = $this->getListOfExtraAttributes();

        foreach ($attributes as $attribute) {
            $extra[$attribute] = $this->getAttribute($attribute);
        }

        // avatar
        $avatar = '';

        if ($this->getAttribute('avatar')
            && QUI\Projects\Media\Utils::isMediaUrl($this->getAttribute('avatar'))
        ) {
            $avatar = $this->getAttribute('avatar');
        }

        // saving
        QUI::getEvents()->fireEvent('groupSaveBegin', array($this));
        QUI::getEvents()->fireEvent('groupSave', array($this));

        QUI::getDataBase()->update(
            Manager::table(),
            array(
                'name'    => $this->getAttribute('name'),
                'toolbar' => $this->getAttribute('toolbar'),
                'rights'  => json_encode($this->rights),
                'extra'   => json_encode($extra),
                'avatar'  => $avatar
            ),
            array('id' => $this->getId())
        );

        $this->createCache();

        QUI::getEvents()->fireEvent('groupSaveEnd', array($this));
    }

    /**
     * Activate the group
     */
    public function activate()
    {
        QUI::getDataBase()->update(
            Manager::table(),
            array('active' => 1),
            array('id' => $this->getId())
        );

        $this->setAttribute('active', 1);
        $this->createCache();

        QUI::getEvents()->fireEvent('groupActivate', array($this));
    }

    /**
     * deactivate the group
     */
    public function deactivate()
    {
        QUI::getDataBase()->update(
            Manager::table(),
            array('active' => 0),
            array('id' => $this->getId())
        );

        $this->setAttribute('active', 0);
        $this->createCache();

        QUI::getEvents()->fireEvent('groupDeactivate', array($this));
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
            throw new QUI\Exception(array(
                'quiqqer/system',
                'exception.lib.qui.group.no.edit.permissions'
            ));
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

        $params['from']  = QUI\Users\Manager::table();
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
     */
    public function getUserByName($username)
    {
        $result = QUI::getDataBase()->fetch(array(
            'select' => 'id',
            'from'   => QUI\Users\Manager::table(),
            'where'  => array(
                'username'  => $username,
                'usergroup' => array(
                    'type'  => '%LIKE%',
                    'value' => ',' . $this->getId() . ','
                )
            ),
            'limit'  => '1'
        ));

        if (!isset($result[0])) {
            throw new QUI\Exception(
                array(
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
                'as'     => 'count'
            ),
            'from'  => QUI\Users\Manager::table(),
            'where' => array(
                'usergroup' => array(
                    'type'  => 'LIKE',
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

        if (isset($result[0]) && isset($result[0]['count'])) {
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
            'from'   => Manager::table(),
            'where'  => array(
                'id' => $this->getId()
            ),
            'limit'  => 1
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
            'from'   => Manager::table(),
            'where'  => array(
                'id' => (int)$id
            ),
            'limit'  => 1
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
            'from'   => Manager::table(),
            'where'  => array(
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
            'from'   => Manager::table(),
            'where'  => array(
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
     * @param QUI\Interfaces\Users\User $ParentUser - (optional), Parent User, which create the user
     *
     * @return QUI\Groups\Group
     * @throws QUI\Exception
     */
    public function createChild($name, $ParentUser = null)
    {
        // check, is the user allowed to create new users
        QUI\Permissions\Permission::checkPermission(
            'quiqqer.admin.groups.create',
            $ParentUser
        );

        $create = true;
        $newid  = false;

        while ($create) {
            srand(microtime() * 1000000);
            $newid = rand(10, 1000000000);

            $result = QUI::getDataBase()->fetch(array(
                'select' => 'id',
                'from'   => Manager::table(),
                'where'  => array(
                    'id' => $newid
                )
            ));

            if (!isset($result[0]) || !$result[0]['id']) {
                $create = false;
            }
        }

        // #locale
        if (!$newid) {
            throw new QUI\Exception('Could not create new group');
        }

        QUI::getDataBase()->insert(
            Manager::table(),
            array(
                'id'     => $newid,
                'name'   => $name,
                'parent' => $this->getId(),
                'admin'  => 0,
                'active' => 0
            )
        );

        $Group = QUI::getGroups()->get($newid);

        // set standard permissions
        QUI::getPermissionManager()->importPermissionsForGroup($Group, $ParentUser);

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
            'parentids'  => $this->getParentIds(),
            'attributes' => $this->getAttributes(),
            'rights'     => $this->rights
        ));
    }
}
