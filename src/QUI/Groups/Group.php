<?php

/**
 * This file contains QUI\Groups\Group
 */

namespace QUI\Groups;

use QUI;
use QUI\Database\Exception;
use Throwable;

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
    protected array $settings;

    protected string|int|bool|array $rootId;

    protected ?int $id = null;

    protected ?string $uuid = null;

    /**
     * internal right cache
     *
     * @var array
     */
    protected mixed $rights = [];

    protected ?array $childrenIds = null;

    /**
     * internal parent id cache
     *
     * @var array
     */
    protected mixed $parentIds = null;

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
                $this->parentIds = $cache['parentids'];
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

        if (!isset($result[0])) {
            if ($id == Manager::EVERYONE_ID || $id == Manager::GUEST_ID) {
                $this->id = (int)$id;
                $this->uuid = $id;
                return;
            }

            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.lib.qui.manager.no.groupid'
                )
            );
        }

        $id = (int)$result[0]['id'];

        // create uuid for group. if not exists
        if (empty($result[0]['uuid']) && $result[0]['uuid'] !== "0") {
            $uuid = QUI\Utils\Uuid::get();

            if ($id === 0) {
                $uuid = 0;
            }

            if ($id === 1) {
                $uuid = 1;
            }

            $result[0]['uuid'] = $uuid;
            $this->uuid = $result[0]['uuid'];

            QUI::getDatabase()->update(
                Manager::table(),
                ['uuid' => $result[0]['uuid']],
                ['id' => $result[0]['id']]
            );
        }

        // @todo remove it in quiqqer/core v3
        // merge parent id to uuid
        if (is_numeric($result[0]['parent']) && (int)$result[0]['parent'] !== 0) {
            $parentId = (int)$result[0]['parent'];
            $Group = QUI::getGroups()->get($parentId);

            QUI::getDatabase()->update(
                Manager::table(),
                ['parent' => $Group->getUUID()],
                ['id' => $result[0]['id']]
            );
        } elseif (is_numeric($result[0]['parent']) && (int)$result[0]['parent'] === 0) {
            QUI::getDatabase()->update(
                Manager::table(),
                ['parent' => 0],
                ['id' => $result[0]['id']]
            );
        }


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
                ['quiqqer/core', 'exception.lib.qui.group.doesnt.exist'],
                404
            );
        }

        foreach ($result[0] as $key => $value) {
            $this->setAttribute($key, $value);
        }

        $this->id = (int)$result[0]['id'];
        $this->uuid = $result[0]['uuid'];
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
     * @param string $name - Attribute name
     * @param mixed $value - value
     */
    public function setAttribute(string $name, mixed $value): void
    {
        if ($name !== 'id') {
            parent::setAttribute($name, $value);
        }
    }

    /**
     * @deprecated
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function getUUID(): string
    {
        return $this->uuid;
    }

    /**
     * Return the list which extra attributes exist
     * Plugins could extend the group attributes
     *
     * look at
     * - https://dev.quiqqer.com/quiqqer/core/wikis/User-Xml
     * - https://dev.quiqqer.com/quiqqer/core/wikis/Group-Xml
     */
    protected function getListOfExtraAttributes(): array
    {
        return Manager::getListOfExtraAttributes();
    }

    /**
     * creates the group cache
     *
     * @ignore
     */
    protected function createCache(): void
    {
        QUI\Cache\Manager::set('quiqqer/groups/group/' . $this->getUUID(), [
            'parentids' => $this->getParentIds(),
            'attributes' => $this->getAttributes(),
            'rights' => $this->rights
        ]);
    }

    public function getParentIds(): array
    {
        if ($this->parentIds) {
            return $this->parentIds;
        }

        $this->parentIds = [];

        try {
            $result = QUI::getDataBase()->fetch([
                'select' => 'id,parent',
                'from' => Manager::table(),
                'where' => [
                    'uuid' => $this->getUUID()
                ],
                'limit' => 1
            ]);
        } catch (\Exception $exception) {
            QUI\System\Log::addError($exception->getMessage());
            return [];
        }

        $this->parentIds[] = $result[0]['parent'];

        if (!empty($result[0]['parent'])) {
            $this->getParentIdsHelper($result[0]['parent']);
        }

        return $this->parentIds;
    }

    private function getParentIdsHelper(int|string $id): void
    {
        $result = QUI::getDataBase()->fetch([
            'select' => 'id, parent',
            'from' => Manager::table(),
            'where' => [
                'uuid' => $id
            ],
            'limit' => 1
        ]);

        if (empty($result[0]['parent'])) {
            return;
        }

        $this->parentIds[] = $result[0]['parent'];

        $this->getParentIdsHelper($result[0]['parent']);
    }

    /**
     * Deletes the group and subgroups
     *
     * @throws QUI\Exception
     */
    public function delete(): void
    {
        // @todo remove getId in QUIQQER V3
        if (
            (int)QUI::conf('globals', 'root') === $this->getId()
            || QUI::conf('globals', 'root') === $this->getUUID()
        ) {
            throw new QUI\Exception([
                'quiqqer/core',
                'exception.lib.qui.group.root.delete'
            ]);
        }

        QUI::getEvents()->fireEvent('groupDelete', [$this]);

        /**
         * Delete the group id in all users
         *
         * @param int|string $groupId
         */
        $deleteGidInUsers = static function (int|string $groupId): void {
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
                    'uuid' => $child
                ]
            ]);

            $deleteGidInUsers($child);
        }

        $deleteGidInUsers($this->getUUID());

        QUI::getDataBase()->exec([
            'delete' => true,
            'from' => Manager::table(),
            'where' => [
                'uuid' => $this->getUUID()
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
     *
     * @throws Exception
     */
    public function getChildrenIds(bool $recursive = false, array $params = []): ?array
    {
        if ($this->childrenIds) {
            return $this->childrenIds;
        }


        $this->childrenIds = [];

        $_params = [
            'select' => 'uuid,parent',
            'from' => Manager::table(),
            'where' => [
                'parent' => $this->getUUID()
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
            if (isset($entry['uuid'])) {
                $this->childrenIds[] = $entry['uuid'];

                if ($recursive) {
                    $this->getChildrenIdsHelper($entry['uuid']);
                }
            }
        }

        return $this->childrenIds;
    }

    /**
     * @throws QUI\Database\Exception
     */
    private function getChildrenIdsHelper(int|string $id): void
    {
        $result = QUI::getDataBase()->fetch([
            'select' => 'id,uuid',
            'from' => Manager::table(),
            'where' => [
                'parent' => $id
            ]
        ]);

        foreach ($result as $entry) {
            $this->childrenIds[] = $entry['uuid'];
            $this->getChildrenIdsHelper($entry['uuid']);
        }
    }

    public function getName(): string
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
    public function getAvatar(): QUI\Projects\Media\Image|bool
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
    public function save(): void
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

            $assignedToolbars = array_filter($toolbars, static function ($toolbar): bool {
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
            ['uuid' => $this->getUUID()]
        );

        $this->createCache();

        QUI::getEvents()->fireEvent('groupSaveEnd', [$this]);
    }

    /**
     * Activate the group
     *
     * @throws QUI\Exception
     */
    public function activate(): void
    {
        QUI::getDataBase()->update(
            Manager::table(),
            ['active' => 1],
            ['uuid' => $this->getUUID()]
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
    public function deactivate(): void
    {
        QUI::getDataBase()->update(
            Manager::table(),
            ['active' => 0],
            ['uuid' => $this->getUUID()]
        );

        $this->setAttribute('active', 0);
        $this->createCache();

        QUI::getEvents()->fireEvent('groupDeactivate', [$this]);
    }

    public function isActive(): bool
    {
        return (bool)$this->getAttribute('active');
    }

    /**
     * @deprecated
     */
    public function hasRight(string $right): bool|array|string
    {
        return $this->hasPermission($right);
    }

    public function hasPermission(string $permission): bool|array|string
    {
        return $this->rights[$permission] ?? false;
    }

    public function getRights(): array
    {
        return $this->rights;
    }

    /**
     * @throws QUI\Exception
     */
    public function setRights(array $rights = []): void
    {
        $User = QUI::getUserBySession();

        if (!$User->isSU()) {
            throw new QUI\Exception([
                'quiqqer/core',
                'exception.lib.qui.group.no.edit.permissions'
            ]);
        }

        foreach ($rights as $k => $v) {
            $this->rights[$k] = $v;
        }
    }

    public function existsRight(string $right): bool
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
     * @throws QUI\Groups\Exception
     * @throws QUI\Exception
     */
    public function setParent(int|string $parentId): void
    {
        // @todo remove getId in QUIQQER V3
        // you can't set for the root group, everyone or guest a parent group
        if (
            $this->getId() == QUI::conf('globals', 'root')
            || $this->getId() == Manager::EVERYONE_ID
            || $this->getId() == Manager::GUEST_ID
            || $this->getUUID() == QUI::conf('globals', 'root')
            || $this->getUUID() == Manager::EVERYONE_ID
            || $this->getUUID() == Manager::GUEST_ID
        ) {
            return;
        }

        $NewParent = QUI::getGroups()->get($parentId);
        $children = $this->getChildrenIds(true);

        if (!empty($children)) {
            $children = array_flip($children);

            if (isset($children[$NewParent->getId()])) {
                throw new QUI\Groups\Exception(
                    [
                        'quiqqer/core',
                        'exception.group.set.parent.not.allowed'
                    ],
                    400,
                    [
                        'groupId' => $this->getUUID(),
                        'newParent' => $NewParent->getUUID(),
                        'currentParent' => $this->getParent()?->getUUID()
                    ]
                );
            }
        }


        $this->setAttribute('parent', $NewParent->getId());

        QUI::getDataBase()->update(
            Manager::table(),
            ['parent' => $NewParent->getUUID()],
            ['uuid' => $this->getUUID()]
        );

        QUI::getEvents()->fireEvent('setParent', [$this, $NewParent]);
    }

    /**
     * @throws QUI\Exception
     */
    public function getParent(): Group|Everyone|Guest|null
    {
        if ($this->getAttribute('parent') === null) {
            return null;
        }

        if ($this->getAttribute('parent')) {
            return QUI::getGroups()->get($this->getAttribute('parent'));
        }

        $ids = $this->getParentIds();

        if (isset($ids[0])) {
            return QUI::getGroups()->get($ids[0]);
        }

        return null;
    }

    /**
     * @throws QUI\Exception
     */
    public function addUser(QUI\Users\User $User): void
    {
        $User->addToGroup($this->getUUID());
    }

    /**
     * @throws QUI\Exception
     */
    public function removeUser(QUI\Users\User $User): void
    {
        $User->removeGroup($this);
    }

    public function getUserIds(): array
    {
        $userIds = [];
        $users = $this->getUsers();

        foreach ($users as $row) {
            $userIds[] = $row['uuid'];
        }

        return $userIds;
    }

    /**
     * return the users from the group
     *
     * @param array $params - SQL Params
     *
     * @return array
     */
    public function getUsers(array $params = []): array
    {
        $uuid = $this->getUUID();

        try {
            $query = QUI::getQueryBuilder()
                ->select('*')
                ->from(QUI\Users\Manager::table())
                ->where('usergroup LIKE :groupLike OR usergroup = :groupEqual')
                ->setParameter('groupLike', '%,' . $uuid . ',%')
                ->setParameter('groupEqual', $uuid);

            QUI\Utils\Doctrine::parseDbArrayToQueryBuilder($query, $params);

            return $query->executeQuery()->fetchAllAssociative();
        } catch (Throwable $e) {
            QUI\System\Log::addError($e->getMessage());
            return [];
        }
    }

    /**
     * @throws QUI\Exception
     */
    public function getUserByName(string $username): QUI\Interfaces\Users\User
    {
        $result = QUI::getDataBase()->fetch([
            'select' => 'id,uuid',
            'from' => QUI\Users\Manager::table(),
            'where' => [
                'username' => $username,
                'usergroup' => [
                    'type' => '%LIKE%',
                    'value' => ',' . $this->getUUID() . ','
                ]
            ],
            'limit' => 1
        ]);

        if (!isset($result[0])) {
            throw new QUI\Exception(
                [
                    'quiqqer/core',
                    'exception.lib.qui.group.user.not.found'
                ],
                404
            );
        }

        return QUI::getUsers()->get($result[0]['uuid']);
    }

    /**
     * @param array $params - SQL Params
     *
     * @return integer
     *
     * @throws Exception
     */
    public function countUser(array $params = []): int
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
                    'value' => "," . $this->getUUID() . ","
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
     * @throws QUI\Exception
     */
    public function isParent(int|string $id, bool $recursive = false): bool
    {
        if ($recursive) {
            if (in_array($id, $this->parentIds)) {
                return true;
            }

            return false;
        }

        if ($this->getParent() == $id) {
            return true;
        }

        return false;
    }

    public function hasChildren(): int
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
    public function getChildren(array $params = []): array
    {
        try {
            $ids = $this->getChildrenIds(false, $params);
        } catch (\Exception $exception) {
            QUI\System\Log::addError($exception->getMessage());
            return [];
        }

        $children = [];
        $Groups = QUI::getGroups();

        foreach ($ids as $id) {
            if (
                is_numeric($id)
                && ((int)$id === Manager::GUEST_ID || (int)$id === Manager::EVERYONE_ID)
            ) {
                continue;
            }

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

        // @todo IMPORTANT!!! wird wahrscheinlich nicht mehr benötigt, da wir uuids nutzen?
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

        QUI::getDataBase()->insert(Manager::table(), [
            'id' => $newId,
            'uuid' => QUI\Utils\Uuid::get(),
            'name' => $name,
            'parent' => $this->getUUID(),
            'active' => 0
        ]);

        $Group = QUI::getGroups()->get($newId);

        // set standard permissions
        QUI::getPermissionManager()->importPermissionsForGroup($Group, $ParentUser);

        QUI::getEvents()->fireEvent('groupCreate', [$Group]);

        return $Group;
    }
}
