<?php

/**
 * This file contains \QUI\Groups\Manager
 */

namespace QUI\Groups;

use DOMElement;
use QUI;
use QUI\Exception;
use QUI\Update;
use QUI\Utils\Security\Orthos;

use function array_filter;
use function array_merge;
use function file_exists;
use function in_array;
use function is_array;
use function is_numeric;
use function is_object;
use function trim;

/**
 * Group Manager
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Manager extends QUI\QDOM
{
    const GUEST_ID = 0;
    const EVERYONE_ID = 1;
    protected static ?array $getListOfExtraAttributes = null;

    /**
     * @var Everyone|null
     */
    protected ?Everyone $Everyone = null;

    /**
     * @var Guest|null
     */
    protected ?Guest $Guest = null;

    /**
     * internal group cache
     *
     * @var array
     */
    protected array $groups;

    /**
     * @var array
     */
    protected array $data = [];

    /**
     * @return array|bool|object|string|null
     */
    public static function getListOfExtraAttributes(): object|bool|array|string|null
    {
        if (self::$getListOfExtraAttributes !== null) {
            return self::$getListOfExtraAttributes;
        }

        $cache = 'quiqqer/groups/plugin-attribute-list';

        try {
            self::$getListOfExtraAttributes = QUI\Cache\Manager::get($cache);

            return self::$getListOfExtraAttributes;
        } catch (QUI\Exception) {
        }

        $list = QUI::getPackageManager()->getInstalled();
        $attributes = [];

        foreach ($list as $entry) {
            $plugin = $entry['name'];
            $userXml = OPT_DIR . $plugin . '/group.xml';

            if (!file_exists($userXml)) {
                continue;
            }

            $attributes = array_merge(
                $attributes,
                self::readAttributesFromGroupXML($userXml)
            );
        }

        self::$getListOfExtraAttributes = $attributes;

        QUI\Cache\Manager::set($cache, $attributes);

        return $attributes;
    }

    /**
     * Return a group by ID
     *
     * @param integer|string $id - ID of the Group
     * @return Group|Everyone|Guest
     *
     * @throws Exception
     */
    public function get(int|string $id): Group|Everyone|Guest
    {
        if (is_numeric($id)) {
            $id = (int)$id;
        }

        if ($id === Manager::EVERYONE_ID) {
            if ($this->Everyone === null) {
                $this->Everyone = new Everyone();
            }

            return $this->Everyone;
        }

        if ($id === Manager::GUEST_ID) {
            if ($this->Guest === null) {
                $this->Guest = new Guest();
            }

            return new Guest();
        }

        if (!$id) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.lib.qui.manager.no.groupid'
                )
            );
        }

        if (isset($this->groups[$id])) {
            return $this->groups[$id];
        }

        $Group = new Group($id);
        $id = $Group->getUUID();

        $this->groups[$id] = $Group;

        return $this->groups[$id];
    }

    /**
     * Read a user.xml and return the attributes,
     * if some extra attributes defined
     *
     * @param string $file
     *
     * @return array
     */
    protected static function readAttributesFromGroupXML(string $file): array
    {
        $Dom = QUI\Utils\Text\XML::getDomFromXml($file);
        $Attr = $Dom->getElementsByTagName('attributes');

        if (!$Attr->length) {
            return [];
        }

        /* @var $Attributes DOMElement */
        $Attributes = $Attr->item(0);
        $list = $Attributes->getElementsByTagName('attribute');

        if (!$list->length) {
            return [];
        }

        $attributes = [];

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
     * Setup for groups
     */
    public function setup(): void
    {
        // moved to migration v2 script
    }

    /**
     * Return the db table for the groups
     *
     * @return string
     */
    public static function table(): string
    {
        return QUI::getDBTableName('groups');
    }

    /**
     * Returns the first group
     *
     * @return QUI\Groups\Group
     * @throws Exception
     */
    public function firstChild(): Group
    {
        return $this->get(QUI::conf('globals', 'root'));
    }

    /**
     * Return the db data of a group
     *
     * @param integer|string $groupId
     * @return array
     */
    public function getGroupData(int|string $groupId): array
    {
        if (isset($this->data[$groupId])) {
            return $this->data[$groupId];
        }

        try {
            if (is_numeric($groupId)) {
                $result = QUI::getDataBase()->fetch([
                    'from' => self::table(),
                    'where' => [
                        'id' => $groupId
                    ],
                    'limit' => 1
                ]);
            } else {
                $result = QUI::getDataBase()->fetch([
                    'from' => self::table(),
                    'where' => [
                        'uuid' => $groupId
                    ],
                    'limit' => 1
                ]);
            }
        } catch (QUI\Exception) {
        }

        if (!isset($result[0])) {
            return [];
        }

        $uuid = $result[0]['uuid'];
        $this->data[$uuid] = $result;

        return $result;
    }

    /**
     * Return the name of a group
     *
     * @param integer|string $id - ID of the Group
     *
     * @return string
     * @throws Exception
     */
    public function getGroupNameById(int|string $id): string
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
    public function getAllGroups(bool $objects = false): array
    {
        if (!$objects) {
            return QUI::getDataBase()->fetch([
                'from' => self::table(),
                'order' => 'name'
            ]);
        }

        $result = [];
        $ids = $this->getAllGroupIds();

        foreach ($ids as $id) {
            try {
                $result[] = $this->get($id['id']);
            } catch (QUI\Exception) {
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
    public function getAllGroupIds(): array
    {
        return QUI::getDataBase()->fetch([
            'select' => 'id, uuid',
            'from' => self::table(),
            'order' => 'name'
        ]);
    }

    /**
     * Search groups
     *
     * @param array $params - QUI\Database\DB params
     *
     * @return array
     */
    public function search(array $params = []): array
    {
        return $this->searchHelper($params);
    }

    /**
     * Internal search helper
     *
     * @param array $params
     *
     * @return array
     * @ignore
     */
    protected function searchHelper(array $params): array
    {
        $DataBase = QUI::getDataBase();
        $params = Orthos::clearArray($params);

        $allowOrderFields = [
            'id',
            'name',
            'parent',
            'active'
        ];

        $allowSearchFields = [
            'id' => true,
            'name' => true,
            'parent' => true,
            'active' => true
        ];

        $max = 10;
        $start = 0;

        $_fields = [
            'from' => self::table()
        ];

        if (isset($params['count'])) {
            $_fields['count'] = [
                'select' => 'id',
                'as' => 'count'
            ];
        }

        if (
            isset($params['limit'])
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

        if (
            isset($params['order'])
            && isset($params['field'])
            && $params['field']
            && in_array($params['field'], $allowOrderFields)
        ) {
            $_fields['order'] = $params['field'] . ' ' . $params['order'];
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
                    'type' => '%LIKE%',
                    'value' => $params['search']
                ]
            ];
        } elseif (
            isset($params['search'])
            && isset($params['searchSettings'])
            && is_array($params['searchSettings'])
        ) {
            foreach ($params['searchSettings'] as $field) {
                if (!isset($allowSearchFields[$field])) {
                    continue;
                }

                $_fields['where_or'][$field] = [
                    'type' => '%LIKE%',
                    'value' => $params['search']
                ];
            }
        }

        return $DataBase->fetch($_fields);
    }

    /**
     * Is the Object a Group?
     *
     * @param mixed $Group
     *
     * @return boolean
     */
    public function isGroup(mixed $Group): bool
    {
        if (!is_object($Group)) {
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
    public function count(array $params = []): int
    {
        $params['count'] = true;

        unset($params['limit']);
        unset($params['start']);

        $result = $this->searchHelper($params);

        if (isset($result[0]['count'])) {
            return (int)$result[0]['count'];
        }

        return 0;
    }
}
