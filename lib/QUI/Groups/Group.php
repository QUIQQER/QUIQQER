<?php

/**
 * This file contains QUI\Groups\Group
 */

namespace QUI\Groups;

use QUI;

use function array_filter;
use function array_flip;
use function array_merge;
use function count;
use function explode;
use function in_array;
use function is_array;
use function is_int;
use function is_numeric;
use function json_decode;
use function json_encode;
use function microtime;
use function mt_rand;
use function mt_srand;

/**
 * A group
 *
 * @author  www.pcsg.de (Henning Leutz)
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
     * Group id
     *
     * @var int|null
     */
    protected ?int $id = null;

    /**
     * Group uuid
     *
     * @var string|null
     */
    protected ?string $uuid = null;

    /**
     * internal right cache
     *
     * @var array
     */
    protected $rights = [];

    /**
     * internal children id cache
     *
     * @var array|null
     */
    protected ?array $childrenIds = null;

    /**
     * internal parent id cache
     *
     * @var array
     */
    protected $parentids = null;

    /**
     * constructor
     *
     * @param integer|string $id - Group ID
     *
     * @throws QUI\Exception
     */
    public function __construct(int|string $id)
    {
        $this->rootId = QUI::conf('globals', 'root');

        if (is_numeric($id)) {
            $this->id = (int)$id;
        } else {
            $this->uuid = $id;
        }

        // exists groups cache
        try {
            if ($this->uuid) {
                $cache = QUI\Cache\Manager::get('qui/groups/group/' . $this->uuid);
            } else {
                $cache = QUI\Cache\Manager::get('qui/groups/group/' . $this->id);
            }


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
        } catch (QUI\Cache\Exception) {
        }


        $result = QUI::getGroups()->getGroupData($id);

        if (!isset($result[0]) && $id === Manager::EVERYONE_ID) {
            QUI::getDataBase()->insert(Manager::table(), [
                'id' => Manager::EVERYONE_ID,
                'name' => 'Everyone'
            ]);

            $result = QUI::getGroups()->getGroupData($id);
        } elseif (!isset($result[0]) && $id === Manager::GUEST_ID) {
            QUI::getDataBase()->insert(Manager::table(), [
                'id' => Manager::GUEST_ID,
                'name' => 'Guest'
            ]);

            $result = QUI::getGroups()->getGroupData($id);
        }

        if (!isset($result[0])) {
            throw new QUI\Exception(
                ['quiqqer/quiqqer', 'exception.lib.qui.group.doesnt.exist'],
                404
            );
        }

        foreach ($result[0] as $key => $value) {
            $this->setAttribute($key, $value);
        }

        $this->rights = QUI::getPermissionManager()->getPermissions($this);

        // Extras are deprecated - we need an api
        if (isset($result[0]['extra'])) {
            $extraList = $this->getListOfExtraAttributes();
            $extras = [];
            $extraData = json_decode($result[0]['extra'], true);

            if (!is_array($extraData)) {
                $extraData = [];
            }

            foreach ($extraList as $attribute) {
                $extras[$attribute] = true;
            }

            foreach ($extraData as $attribute => $value) {
                if (isset($extras[$attribute])) {
                    $this->setAttribute($attribute, $value);
                }
            }
        }

        $this->createCache();

        QUI::getEvents()->fireEvent('groupLoad', [$this]);
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
     * Returns the Group-ID
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUUID(): string
    {
        return $this->uuid;
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
        return Manager::getListOfExtraAttributes();
    }

    /**
     * creates the group cache
     *
     * @ignore
     */
    protected function createCache()
    {
        QUI\Cache\Manager::set('quiqqer/groups/group/' . $this->getUUID(), [
            'parentids' => $this->getParentIds(),
            'attributes' => $this->getAttributes(),
            'rights' => $this->rights
        ]);
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
            'from' => Manager::table(),
            'where' => [
                'id' => $this->getId()
            ],
            'limit' => 1
        ]);

        $this->parentids[] = $result[0]['parent'];

        if (!empty($result[0]['parent'])) {
            $this->getParentIdsHelper($result[0]['parent']);
        }

        return $this->parentids;
    }

    /**
     * Helper method for get parents
     *
     * @param int|string $id
     *
     * @ignore
     */
    private function getParentIdsHelper(int|string $id): void
    {
        $result = QUI::getDataBase()->fetch([
            'select' => 'id, parent',
            'from' => Manager::table(),
            'where_or' => [
                'id' => (int)$id,
                'uuid' => $id
            ],
            'limit' => 1
        ]);

        if (empty($result[0]['parent'])) {
            return;
        }

        $this->parentids[] = $result[0]['parent'];

        $this->getParentIdsHelper($result[0]['parent']);
    }

    /**
     * Deletes the group and subgroups
     *
     * @throws QUI\Exception
     */
    public function delete()
    {
        // Rootgruppe kann nicht gelöscht werden
        if ((int)QUI::conf('globals', 'root') === $this->getId()) {
            throw new QUI\Exception([
                'quiqqer/quiqqer',
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
            if (!is_int($groupId)) {
                return;
            }

            $PDO = QUI::getDataBase()->getPDO();
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


        $children = $this->getChildrenIds(true);

        foreach ($children as $child) {
            QUI::getDataBase()->exec([
                'delete' => true,
                'from' => Manager::table(),
                'where' => [
                    'id' => $child
                ]
            ]);

            $deleteGidInUsers($child);
        }

        $deleteGidInUsers($this->getUUID());

        QUI::getDataBase()->exec([
            'delete' => true,
            'from' => Manager::table(),
            'where' => [
                'id' => $this->getId()
            ]
        ]);

        QUI\Cache\Manager::clear('qui/groups/group/' . $this->getUUID());
    }

    /**
     * return the subgroup ids
     *
     * @param boolean $recursive - recursive true / false
     * @param array $params - SQL Params (limit, order)
     *
     * @return array|null
     * @throws Exception
     */
    public function getChildrenIds(bool $recursive = false, array $params = []): ?array
    {
        if ($this->childrenIds) {
            return $this->childrenIds;
        }


        $this->childrenIds = [];

        $_params = [
            'select' => 'id',
            'from' => Manager::table(),
            'where' => [
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

        if (!isset($result[0])) {
            return $this->childrenIds;
        }

        foreach ($result as $entry) {
            if (isset($entry['id'])) {
                $this->childrenIds[] = $entry['id'];

                if ($recursive) {
                    $this->getChildrenIdsHelper($entry['id']);
                }
            }
        }

        return $this->childrenIds;
    }

    /**
     * Helper method for the recursive
     *
     * @param integer $id
     * @throws QUI\Database\Exception
     */
    private function getChildrenIdsHelper(int $id)
    {
        $result = QUI::getDataBase()->fetch([
            'select' => 'id',
            'from' => Manager::table(),
            'where' => [
                'parent' => $id
            ]
        ]);

        foreach ($result as $entry) {
            if (isset($entry['id'])) {
                $this->childrenIds[] = $entry['id'];

                $this->getChildrenIdsHelper($entry['id']);
            }
        }
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
            $Media = $Project->getMedia();

            return $Media->getPlaceholderImage();
        }

        try {
            return QUI\Projects\Media\Utils::getImageByUrl($avatar);
        } catch (QUI\Exception) {
        }

        $Project = QUI::getProjectManager()->getStandard();
        $Media = $Project->getMedia();

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

        $extra = [];
        $attributes = $this->getListOfExtraAttributes();

        foreach ($attributes as $attribute) {
            $extra[$attribute] = $this->getAttribute($attribute);
        }

        // avatar
        $avatar = '';

        if (
            $this->getAttribute('avatar')
            && QUI\Projects\Media\Utils::isMediaUrl($this->getAttribute('avatar'))
        ) {
            $avatar = $this->getAttribute('avatar');
        }

        // check assigned toolbars
        $assignedToolbars = '';
        $toolbar = '';

        if ($this->getAttribute('assigned_toolbar')) {
            $toolbars = explode(',', $this->getAttribute('assigned_toolbar'));

            $assignedToolbars = array_filter($toolbars, function ($toolbar) {
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
                'name' => $this->getAttribute('name'),
                'rights' => json_encode($this->rights),
                'extra' => json_encode($extra),
                'avatar' => $avatar,
                'assigned_toolbar' => $assignedToolbars,
                'toolbar' => $toolbar
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
        return (bool)$this->getAttribute('active');
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
        return $this->rights[$permission] ?? false;
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
                'quiqqer/quiqqer',
                'exception.lib.qui.group.no.edit.permissions'
            ]);
        }

        foreach ($rights as $k => $v) {
            $this->rights[$k] = $v;
        }
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
     * Set a new parent
     *
     * @param integer $parentId
     *
     * @throws QUI\Groups\Exception
     * @throws QUI\Exception
     */
    public function setParent($parentId)
    {
        if (
            $this->getParent() && $this->getParent()->getId() == $parentId
            || $this->getId() == $parentId
        ) {
            return;
        }

        // you cant set for the root group, everyoneor guest a parent group
        if (
            $this->getId() == QUI::conf('globals', 'root')
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
            $children = array_flip($children);

            if (isset($children[$NewParent->getId()])) {
                throw new QUI\Groups\Exception(
                    [
                        'quiqqer/quiqqer',
                        'exception.group.set.parent.not.allowed'
                    ],
                    400,
                    [
                        'groupId' => $this->getId(),
                        'newParent' => $NewParent->getId(),
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

        if ($obj) {
            return QUI::getGroups()->get($ids[0]);
        }

        return $ids[0];
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
     * Get IDs of all users in the groups
     *
     * @return array
     */
    public function getUserIds()
    {
        $userIds = [];
        $users = $this->getUsers();

        foreach ($users as $row) {
            $userIds[] = $row['id'];
        }

        return $userIds;
    }

    /**
     * return the users from the group
     *
     * @param array $params - SQL Params
     * @return array
     */
    public function getUsers($params = [])
    {
        $uuid = $this->getUUID();

        $params['from'] = QUI\Users\Manager::table();
        $params['where'] = trim(
            "usergroup LIKE '%,$uuid,%' OR usergroup = $uuid"
        );

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
        $result = QUI::getDataBase()->fetch([
            'select' => 'id',
            'from' => QUI\Users\Manager::table(),
            'where' => [
                'username' => $username,
                'usergroup' => [
                    'type' => '%LIKE%',
                    'value' => ',' . $this->getId() . ','
                ]
            ],
            'limit' => '1'
        ]);

        if (!isset($result[0])) {
            throw new QUI\Exception(
                [
                    'quiqqer/quiqqer',
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
                'as' => 'count'
            ],
            'from' => QUI\Users\Manager::table(),
            'where' => [
                'usergroup' => [
                    'type' => '%LIKE%',
                    'value' => "," . $this->getId() . ","
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

        if (isset($result[0]['count'])) {
            return $result[0]['count'];
        }

        return 0;
    }

    /**
     * Checks if the ID is from a parent group
     *
     * @param int|string $id - ID from parent
     * @param boolean $recursive - checks recursive or not
     *
     * @return boolean
     *
     * @throws QUI\Exception
     */
    public function isParent(int|string $id, bool $recursive = false): bool
    {
        if ($recursive) {
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
     * Have the group subgroups?
     *
     * @return integer
     */
    public function hasChildren()
    {
        return count($this->getChildren());
    }

    /**
     * Returns the subgroups
     *
     * @param array $params - Where Parameter
     *
     * @return array
     */
    public function getChildren($params = [])
    {
        $ids = $this->getChildrenIds(false, $params);
        $children = [];
        $Groups = QUI::getGroups();

        foreach ($ids as $id) {
            try {
                $Child = $Groups->get($id);

                $children[] = array_merge(
                    $Child->getAttributes(),
                    ['hasChildren' => $Child->hasChildren()]
                );
            } catch (QUI\Exception) {
                // nothing
            }
        }

        return $children;
    }

    /**
     * Create a subgroup
     *
     * @param string $name - name of the subgroup
     * @param QUI\Interfaces\Users\User|null $ParentUser - (optional), Parent User, which create the user
     *
     * @return QUI\Groups\Group
     * @throws QUI\Exception
     */
    public function createChild(string $name, ?QUI\Interfaces\Users\User $ParentUser = null): Group
    {
        // check, is the user allowed to create new users
        QUI\Permissions\Permission::checkPermission(
            'quiqqer.admin.groups.create',
            $ParentUser
        );

        $create = true;
        $newId = false;

        while ($create) {
            $rand = (int)(microtime(true) * 1_000_000);
            mt_srand($rand);
            $newId = mt_rand(10, 1_000_000_000);

            $result = QUI::getDataBase()->fetch([
                'select' => 'id',
                'from' => Manager::table(),
                'where' => [
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
            'id' => $newId,
            'name' => $name,
            'parent' => $this->getId(),
            'active' => 0
        ]);

        $Group = QUI::getGroups()->get($newId);

        // set standard permissions
        QUI::getPermissionManager()->importPermissionsForGroup($Group, $ParentUser);

        QUI::getEvents()->fireEvent('groupCreate', [$Group]);

        return $Group;
    }
}
