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
    protected $rights = [];

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
            $cache = QUI\Cache\Manager::get('qui/groups/group/'.$this->getId());

            if (isset($cache['parentids'])) {
                $this->parentids = $cache['parentids'];
            }

            if (isset($cache['rights'])) {
                $this->rights = $cache['rights'];
            }

            if (isset($cache['attributes']) && \is_array($cache['attributes'])) {
                foreach ($cache['attributes'] as $key => $value) {
                    $this->setAttribute($key, $value);
                }
            }

            if (!empty($cache)) {
                return;
            }
        } catch (QUI\Cache\Exception $Exception) {
        }

        $result = QUI::getGroups()->getGroupData($id);

        if (!isset($result[0])) {
            throw new QUI\Exception(
                ['quiqqer/system', 'exception.lib.qui.group.doesnt.exist'],
                404
            );
        }

        foreach ($result[0] as $key => $value) {
            $this->setAttribute($key, $value);
        }

        // rechte setzen
        $this->rights = QUI::getPermissionManager()->getPermissions($this);

        // Extras are deprected - we need an api
        if (isset($result[0]['extra'])) {
            $extraList = $this->getListOfExtraAttributes();
            $extras    = [];
            $extraData = \json_decode($result[0]['extra'], true);

            if (!\is_array($extraData)) {
                $extraData = [];
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

        QUI::getEvents()->fireEvent('groupLoad', [$this]);
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
            throw new QUI\Exception([
                'quiqqer/system',
                'exception.lib.qui.group.root.delete'
            ]);
        }

        QUI::getEvents()->fireEvent('groupDelete', [$this]);

        /**
         * Delete the group id in all users
         *
         * @param int $groupId
         */
        $deleteGidInUsers = function ($groupId) {
            if (!\is_int($groupId)) {
                return;
            }

            $PDO   = QUI::getDataBase()->getPDO();
            $table = QUI\Users\Manager::table();

            $Statement = $PDO->prepare(
                "UPDATE {$table}
                SET usergroup = replace(usergroup, :search, :replace)
                WHERE usergroup LIKE :where"
            );

            $Statement->bindValue('where', '%,'.$groupId.',%');
            $Statement->bindValue('search', ','.$groupId.',');
            $Statement->bindValue('replace', ',');
            $Statement->execute();
        };


        // Rekursiv die Kinder bekommen
        $children = $this->getChildrenIds(true);

        // Kinder löschen
        foreach ($children as $child) {
            QUI::getDataBase()->exec([
                'delete' => true,
                'from'   => Manager::table(),
                'where'  => [
                    'id' => $child
                ]
            ]);

            $deleteGidInUsers($child);
        }

        $deleteGidInUsers($this->getId());

        // Sich selbst löschen
        QUI::getDataBase()->exec([
            'delete' => true,
            'from'   => Manager::table(),
            'where'  => [
                'id' => $this->getId()
            ]
        ]);

        QUI\Cache\Manager::clear('qui/groups/group/'.$this->getId());
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
     *
     * @throws QUI\Exception
     */
    protected function getListOfExtraAttributes()
    {
        try {
            return QUI\Cache\Manager::get('group/plugin-attribute-list');
        } catch (QUI\Exception $Exception) {
        }

        $list       = QUI::getPackageManager()->getInstalled();
        $attributes = [];

        foreach ($list as $entry) {
            $plugin  = $entry['name'];
            $userXml = OPT_DIR.$plugin.'/group.xml';

            if (!\file_exists($userXml)) {
                continue;
            }

            $attributes = \array_merge(
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
            return [];
        }

        /* @var $Attributes \DOMElement */
        $Attributes = $Attr->item(0);
        $list       = $Attributes->getElementsByTagName('attribute');

        if (!$list->length) {
            return [];
        }

        $attributes = [];

        for ($c = 0; $c < $list->length; $c++) {
            $Attribute = $list->item($c);

            if ($Attribute->nodeName == '#text') {
                continue;
            }

            $attributes[] = \trim($Attribute->nodeValue);
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
     *
     * @throws QUI\Exception
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
     *
     * @throws QUI\Exception
     */
    public function save()
    {
        $this->rights = QUI::getPermissionManager()->getRightParamsFromGroup($this);

        // Pluginerweiterungen
        $extra      = [];
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

        // check assigned toolbars
        $assignedToolbars = '';
        $toolbar          = '';

        if ($this->getAttribute('assigned_toolbar')) {
            $toolbars = \explode(',', $this->getAttribute('assigned_toolbar'));

            $assignedToolbars = \array_filter($toolbars, function ($toolbar) {
                return QUI\Editor\Manager::existsToolbar($toolbar);
            });

            $assignedToolbars = implode(',', $assignedToolbars);
        }

        if (QUI\Editor\Manager::existsToolbar($this->getAttribute('toolbar'))) {
            $toolbar = $this->getAttribute('toolbar');
        }

        // saving
        QUI::getEvents()->fireEvent('groupSaveBegin', [$this]);
        QUI::getEvents()->fireEvent('groupSave', [$this]);

        QUI::getDataBase()->update(
            Manager::table(),
            [
                'name'             => $this->getAttribute('name'),
                'rights'           => \json_encode($this->rights),
                'extra'            => \json_encode($extra),
                'avatar'           => $avatar,
                'assigned_toolbar' => $assignedToolbars,
                'toolbar'          => $toolbar
            ],
            ['id' => $this->getId()]
        );

        $this->createCache();

        QUI::getEvents()->fireEvent('groupSaveEnd', [$this]);
    }

    /**
     * Activate the group
     *
     * @throws QUI\Exception
     */
    public function activate()
    {
        QUI::getDataBase()->update(
            Manager::table(),
            ['active' => 1],
            ['id' => $this->getId()]
        );

        $this->setAttribute('active', 1);
        $this->createCache();

        QUI::getEvents()->fireEvent('groupActivate', [$this]);
    }

    /**
     * deactivate the group
     *
     * @throws QUI\Exception
     */
    public function deactivate()
    {
        QUI::getDataBase()->update(
            Manager::table(),
            ['active' => 0],
            ['id' => $this->getId()]
        );

        $this->setAttribute('active', 0);
        $this->createCache();

        QUI::getEvents()->fireEvent('groupDeactivate', [$this]);
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
     * @return boolean|string|array
     */
    public function hasPermission($permission)
    {
        return isset($this->rights[$permission]) ? $this->rights[$permission] : false;
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
    public function setRights($rights = [])
    {
        $User = QUI::getUserBySession();

        if (!$User->isSU()) {
            throw new QUI\Exception([
                'quiqqer/system',
                'exception.lib.qui.group.no.edit.permissions'
            ]);
        }

        foreach ($rights as $k => $v) {
            $this->rights[$k] = $v;
        }
    }

    /**
     * Set a new parent
     *
     * @param integer $parentId
     *
     * @throws QUI\Groups\Exception
     * @throws QUI\Exception
     */
    public function setParent($parentId)
    {
        if ($this->getParent()->getId() == $parentId
            || $this->getId() == $parentId
        ) {
            return;
        }

        // you cant set for the root group, everyoneor guest a parent group
        if ($this->getId() == QUI::conf('globals', 'root')
            || $this->getId() == Manager::EVERYONE_ID
            || $this->getId() == Manager::GUEST_ID
        ) {
            return;
        }

        if (empty($parentId)) {
            $parentId = QUI::conf('globals', 'root');
        }

        // exists the parent group
        $NewParent = QUI::getGroups()->get($parentId);

        // can can only set a parent id, if the parent id is not as a child
        $children = $this->getChildrenIds(true);

        if (!empty($children)) {
            $children = \array_flip($children);

            if (isset($children[$NewParent->getId()])) {
                throw new QUI\Groups\Exception(
                    [
                        'quiqqer/quiqqer',
                        'exception.group.set.parent.not.allowed'
                    ],
                    400,
                    [
                        'groupId'       => $this->getId(),
                        'newParent'     => $NewParent->getId(),
                        'currentParent' => $this->getParent()->getId()
                    ]
                );
            }
        }


        $this->setAttribute('parent', $NewParent->getId());

        QUI::getDataBase()->update(
            Manager::table(),
            ['parent' => $NewParent->getId()],
            ['id' => $this->getId()]
        );

        QUI::getEvents()->fireEvent('setParent', [$this, $NewParent]);
    }

    /**
     * Add a user to this group
     *
     * @param QUI\Users\User $User
     */
    public function addUser(QUI\Users\User $User)
    {
        $User->addToGroup($this->getId());
    }

    /**
     * Remove a user from this group
     *
     * @param QUI\Users\User $User
     */
    public function removeUser(QUI\Users\User $User)
    {
        $User->removeGroup($this);
    }

    /**
     * return the users from the group
     *
     * @param array $params - SQL Params
     *
     * @return array
     */
    public function getUsers($params = [])
    {
        $id = $this->getId();

        $params['from']  = QUI\Users\Manager::table();
        $params['where'] = "usergroup LIKE '%,{$id},%' OR usergroup = {$id}";

        return QUI::getDataBase()->fetch($params);
    }

    /**
     * Get IDs of all users in the groups
     *
     * @return array
     */
    public function getUserIds()
    {
        $userIds = [];
        $users   = $this->getUsers();

        foreach ($users as $row) {
            $userIds[] = $row['id'];
        }

        return $userIds;
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
        $result = QUI::getDataBase()->fetch([
            'select' => 'id',
            'from'   => QUI\Users\Manager::table(),
            'where'  => [
                'username'  => $username,
                'usergroup' => [
                    'type'  => '%LIKE%',
                    'value' => ','.$this->getId().','
                ]
            ],
            'limit'  => '1'
        ]);

        if (!isset($result[0])) {
            throw new QUI\Exception(
                [
                    'quiqqer/system',
                    'exception.lib.qui.group.user.not.found'
                ],
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
    public function countUser($params = [])
    {
        $_params = [
            'count' => [
                'select' => 'id',
                'as'     => 'count'
            ],
            'from'  => QUI\Users\Manager::table(),
            'where' => [
                'usergroup' => [
                    'type'  => '%LIKE%',
                    'value' => ",".$this->getId().","
                ]
            ]
        ];

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
     *
     * @throws QUI\Exception
     */
    public function isParent($id, $recursiv = false)
    {
        if ($recursiv) {
            if (\in_array($id, $this->parentids)) {
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

        $this->parentids = [];

        $result = QUI::getDataBase()->fetch([
            'select' => 'id, parent',
            'from'   => Manager::table(),
            'where'  => [
                'id' => $this->getId()
            ],
            'limit'  => 1
        ]);

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
        $result = QUI::getDataBase()->fetch([
            'select' => 'id, parent',
            'from'   => Manager::table(),
            'where'  => [
                'id' => (int)$id
            ],
            'limit'  => 1
        ]);

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
        return \count($this->getChildren());
    }

    /**
     * Returns the sub groups
     *
     * @param array $params - Where Parameter
     *
     * @return array
     */
    public function getChildren($params = [])
    {
        $ids      = $this->getChildrenIds(false, $params);
        $children = [];
        $Groups   = QUI::getGroups();

        foreach ($ids as $id) {
            try {
                $Child = $Groups->get($id);

                $children[] = \array_merge(
                    $Child->getAttributes(),
                    ['hasChildren' => $Child->hasChildren()]
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
    public function getChildrenIds($recursiv = false, $params = [])
    {
        if ($this->childrenids) {
            return $this->childrenids;
        }


        $this->childrenids = [];

        $_params = [
            'select' => 'id',
            'from'   => Manager::table(),
            'where'  => [
                'parent' => $this->getId()
            ]
        ];

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
        $result = QUI::getDataBase()->fetch([
            'select' => 'id',
            'from'   => Manager::table(),
            'where'  => [
                'parent' => $id
            ]
        ]);

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
        $newId  = false;

        while ($create) {
            \mt_srand(\microtime(true) * 1000000);
            $newId = \mt_rand(10, 1000000000);

            $result = QUI::getDataBase()->fetch([
                'select' => 'id',
                'from'   => Manager::table(),
                'where'  => [
                    'id' => $newId
                ]
            ]);

            if (!isset($result[0]) || !$result[0]['id']) {
                $create = false;
            }
        }

        if (!$newId) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.group.create.id.creation.error'
                )
            );
        }

        QUI::getDataBase()->insert(Manager::table(), [
            'id'     => $newId,
            'name'   => $name,
            'parent' => $this->getId(),
            'active' => 0
        ]);

        $Group = QUI::getGroups()->get($newId);

        // set standard permissions
        QUI::getPermissionManager()->importPermissionsForGroup($Group, $ParentUser);

        QUI::getEvents()->fireEvent('groupCreate', [$Group]);

        return $Group;
    }

    /**
     * creates the group cache
     *
     * @ignore
     * @throws QUI\Exception
     */
    protected function createCache()
    {
        // Cache aufbauen
        QUI\Cache\Manager::set('qui/groups/group/'.$this->getId(), [
            'parentids'  => $this->getParentIds(),
            'attributes' => $this->getAttributes(),
            'rights'     => $this->rights
        ]);
    }
}
