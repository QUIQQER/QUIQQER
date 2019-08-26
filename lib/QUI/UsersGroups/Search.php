<?php

/**
 * This file contains \QUI\UsersGroups\Search
 */

namespace QUI\UsersGroups;

use QUI;
use QUI\Utils\Security\Orthos;

/**
 * Search for users and groups
 *
 * @author  www.pcsg.de (Patrick Müller)
 * @licence For copyright and license information, please view the /README.md
 * @package quiqqer/quiqqer
 */
class Search
{
    const DEFAULT_LIMIT_USERS = 20;
    const DEFAULT_LIMIT_GROUPS = 20;

    /**
     * Search users and groups
     *
     * Returns full details
     *
     * @param string $searchTerm - search term
     * @param array $searchParams - search parameters
     * @param bool $count (optional) - return count only
     *
     * @return array|integer
     *
     * @throws QUI\Exception
     */
    public static function search($searchTerm, $searchParams = [], $count = false)
    {
        $searchUsers  = false;
        $searchGroups = false;

        if ($count) {
            $searchResult = [
                'users'  => 0,
                'groups' => 0
            ];
        } else {
            $searchResult = [
                'users'  => [],
                'groups' => []
            ];
        }

        // search in user table
        if (isset($searchParams['searchUsers']) && $searchParams['searchUsers']) {
            $searchUsers = true;
        }

        // search in group table
        if (isset($searchParams['searchGroups']) && $searchParams['searchGroups']) {
            $searchGroups = true;
        }

        if ($searchUsers === false && $searchGroups === false) {
            $searchUsers = true;
        }

        if ($searchUsers) {
            if (!isset($searchParams['users']) || empty($searchParams['users'])) {
                throw new QUI\Exception([
                    'quiqqer/quiqqer',
                    'exception.usergroups.search.cannot.search.users.without.parameters'
                ]);
            }

            $resultUsers = self::searchUsers($searchTerm, $searchParams['users'], $count);

            if ($count) {
                $searchResult['users'] = $resultUsers;
            } else {
                if (!empty($resultUsers)) {
                    $selectFieldsAvailable = [
                        'username'  => true,
                        'usergroup' => true,
                        'email'     => true,
                        'active'    => true,
                        'regdate'   => true,
                        'su'        => true,
                        'expire'    => true,
                        'lastedit'  => true,
                        'firstname' => true,
                        'lastname'  => true,
                        'usertitle' => true,
                        'birthday'  => true,
                        'avatar'    => true,
                        'lang'      => true,
                        'company'   => true
                    ];

                    $selectFields = [];

                    if (isset($searchParams['users']['select'])
                        && !empty($searchParams['users']['select'])
                        && \is_array($searchParams['users']['select'])
                    ) {
                        foreach ($searchParams['users']['select'] as $field => $select) {
                            if (isset($selectFieldsAvailable[$field]) && $select) {
                                $selectFields[] = $field;
                            }
                        }
                    }

                    // always get id
                    $selectFields[] = 'id';

                    $result = QUI::getDataBase()->fetch([
                        'select' => $selectFields,
                        'from'   => QUI\Users\Manager::table(),
                        'where'  => [
                            'id' => [
                                'type'  => 'IN',
                                'value' => $resultUsers
                            ]
                        ]
                    ]);

                    foreach ($result as $row) {
                        $row['type'] = 'user';

                        $searchResult['users'][] = $row;
                    }
                }
            }
        }

        if ($searchGroups) {
            if (!isset($searchParams['groups']) || empty($searchParams['groups'])) {
                throw new QUI\Exception([
                    'quiqqer/quiqqer',
                    'exception.usergroups.search.cannot.search.groups.without.parameters'
                ]);
            }

            $resultGroups = self::searchGroups($searchTerm, $searchParams, $count);

            if ($count) {
                $searchResult['groups'] = $resultGroups;
            } else {
                if (!empty($resultGroups)) {
                    $selectFieldsAvailable = [
                        'name'   => true,
                        'parent' => true,
                        'active' => true
                    ];

                    $selectFields = [];

                    if (isset($searchParams['groups']['select'])
                        && !empty($searchParams['groups']['select'])
                        && \is_array($searchParams['groups']['select'])
                    ) {
                        foreach ($searchParams['groups']['select'] as $field => $select) {
                            if (isset($selectFieldsAvailable[$field]) && $select) {
                                $selectFields[] = $field;
                            }
                        }
                    }

                    // always get id
                    $selectFields[] = 'id';

                    $result = QUI::getDataBase()->fetch([
                        'select' => $selectFields,
                        'from'   => QUI\Groups\Manager::table(),
                        'where'  => [
                            'id' => [
                                'type'  => 'IN',
                                'value' => $resultGroups
                            ]
                        ]
                    ]);

                    foreach ($result as $row) {
                        $row['type'] = 'group';

                        $searchResult['groups'][] = $row;
                    }
                }
            }
        }

        return $searchResult;
    }

    /**
     * Search in user table
     *
     * @param string $searchTerm - search term
     * @param array $searchParams - search parameters
     * @param bool $count (optional) - return count only
     *
     * @return array|int - user ids or count of user ids
     */
    protected static function searchUsers($searchTerm, $searchParams, $count = false)
    {
        if ($count) {
            $sql = "SELECT COUNT(*)";
        } else {
            $sql = 'SELECT id';
        }

        $sql .= ' FROM '.QUI\Users\Manager::table();

        // build WHERE
        $where = [];
        $binds = [];

        // fields where searchTerm is searched
        $searchFieldsAvailable = [
            'id'        => true,
            'username'  => true,
            'email'     => true,
            'firstname' => true,
            'lastname'  => true
        ];

        $searchFields = [];

        if (isset($searchParams['searchFields'])
            && !empty($searchParams['searchFields'])
            && \is_array($searchParams['searchFields'])
        ) {
            foreach ($searchParams['searchFields'] as $field => $search) {
                if (isset($searchFieldsAvailable[$field]) && $search) {
                    $searchFields[] = $field;
                }
            }
        }

        // fallback
        if (empty($searchFields)) {
            $searchFields = [
                'id',
                'username'
            ];
        }

        $whereOR = [];
        $i       = 0;

        foreach ($searchFields as $field) {
            $whereOR[]          = '`'.$field.'` LIKE :search'.$i;
            $binds['search'.$i] = [
                'value' => '%'.$searchTerm.'%',
                'type'  => \PDO::PARAM_STR
            ];

            $i++;
        }

        if (!empty($whereOR)) {
            $where[] = '('.\implode(' OR ', $whereOR).')';
        }

        // search filter
        if (isset($searchParams['filter'])
            && !empty($searchParams['filter'])
            && \is_array($searchParams['filter'])
        ) {
            foreach ($searchParams['filter'] as $filter => $value) {
                switch ($filter) {
                    case 'status':
                        switch ($value) {
                            case 1:
                            case 0:
                            case -1:
                                $where[]         = '`active` = :active';
                                $binds['active'] = [
                                    'value' => $value,
                                    'type'  => \PDO::PARAM_INT
                                ];
                                break;
                        }
                        break;

                    case 'groups':
                        $groupIds = \explode(',', \trim($value, ','));
                        $whereOR  = [];
                        $i        = 0;

                        foreach ($groupIds as $groupId) {
                            $whereOR[]         = '`usergroup` LIKE :group'.$i;
                            $binds['group'.$i] = [
                                'value' => '%,'.$groupId.',%',
                                'type'  => \PDO::PARAM_STR
                            ];

                            $i++;
                        }

                        $where[] = '('.\implode(' OR ', $whereOR).')';
                        break;

                    case 'regDateFrom':
                        $where[]              = '`regdate` >= :regDateFrom';
                        $binds['regDateFrom'] = [
                            'value' => QUI\Utils\Convert::convertMySqlDatetime(
                                $value.' 00:00:00'
                            ),
                            'type'  => \PDO::PARAM_STR
                        ];
                        break;

                    case 'regDateTo':
                        $where[]            = '`regdate` <= :regDateTo';
                        $binds['regDateTo'] = [
                            'value' => QUI\Utils\Convert::convertMySqlDatetime(
                                $value.' 00:00:00'
                            ),
                            'type'  => \PDO::PARAM_STR
                        ];
                        break;
                }
            }
        }

        if (!empty($where)) {
            $sql .= " WHERE ".\implode(" AND ", $where);
        }

        if (isset($searchParams['sortOn']) && !empty($searchParams['sortOn'])) {
            $order = "ORDER BY ".Orthos::clear($searchParams['sortOn']);

            if (isset($searchParams['sortBy']) && !empty($searchParams['sortBy'])) {
                $order .= " ".Orthos::clear($searchParams['sortBy']);
            } else {
                $order .= " ASC";
            }

            $sql .= " ".$order;
        }

        if (isset($searchParams['limit'])
            && !empty($searchParams['limit'])
            && !$count
        ) {
            $sql .= " LIMIT ".$searchParams['limit'];
        } else {
            if (!$count) {
                $sql .= " LIMIT ".self::DEFAULT_LIMIT_USERS;
            }
        }

        $PDO  = QUI::getDataBase()->getPDO();
        $Stmt = $PDO->prepare($sql);

        // bind search values
        foreach ($binds as $var => $bind) {
            $Stmt->bindValue(':'.$var, $bind['value'], $bind['type']);
        }

        // fetch information for all corresponding passwords
        try {
            $Stmt->execute();
            $result = $Stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $Exception) {
            QUI\System\Log::addError(
                '\QUI\UsersGrouüs\Search searchUsers() Database error :: '
                .$Exception->getMessage()
            );

            return [];
        }

        if ($count) {
            return (int)\current(\current($result));
        }

        $ids = [];

        foreach ($result as $row) {
            $ids[] = $row['id'];
        }

        return $ids;
    }

    /**
     * Search in group table
     *
     * @param string $searchTerm - search term
     * @param array $searchParams - search parameters
     * @param bool $count (optional) - return count only
     *
     * @return array|int - group ids or count of group ids
     */
    protected static function searchGroups($searchTerm, $searchParams, $count = false)
    {
        if ($count) {
            $sql = "SELECT COUNT(*)";
        } else {
            $sql = 'SELECT id';
        }

        $sql .= ' FROM '.QUI\Groups\Manager::table();

        // build WHERE
        $where = [];
        $binds = [];

        // fields where searchTerm is searched
        $searchFieldsAvailable = [
            'id'   => true,
            'name' => true
        ];

        $searchFields = [];

        if (isset($searchParams['searchFields'])
            && !empty($searchParams['searchFields'])
            && \is_array($searchParams['searchFields'])
        ) {
            foreach ($searchParams['searchFields'] as $field => $search) {
                if (isset($searchFieldsAvailable[$field]) && $search) {
                    $searchFields[] = $field;
                }
            }
        }

        // fallback
        if (empty($searchFields)) {
            $searchFields = [
                'id',
                'name'
            ];
        }

        $whereOR = [];
        $i       = 0;

        foreach ($searchFields as $field) {
            $whereOR[]          = '`'.$field.'` LIKE :search'.$i;
            $binds['search'.$i] = [
                'value' => '%'.$searchTerm.'%',
                'type'  => \PDO::PARAM_STR
            ];

            $i++;
        }

        if (!empty($whereOR)) {
            $where[] = '('.\implode(' OR ', $whereOR).')';
        }

        // search filter
        if (isset($searchParams['filter'])
            && !empty($searchParams['filter'])
            && \is_array($searchParams['filter'])
        ) {
            foreach ($searchParams['filter'] as $filter => $value) {
                switch ($filter) {
                    case 'status':
                        switch ($value) {
                            case 1:
                            case 0:
                                $where[]         = '`active` = :active';
                                $binds['active'] = [
                                    'value' => $value,
                                    'type'  => \PDO::PARAM_INT
                                ];
                                break;
                        }
                        break;
                }
            }
        }

        if (!empty($where)) {
            $sql .= " WHERE ".\implode(" AND ", $where);
        }

        if (isset($searchParams['sortOn']) && !empty($searchParams['sortOn'])) {
            $order = "ORDER BY ".Orthos::clear($searchParams['sortOn']);

            if (isset($searchParams['sortBy']) && !empty($searchParams['sortBy'])) {
                $order .= " ".Orthos::clear($searchParams['sortBy']);
            } else {
                $order .= " ASC";
            }

            $sql .= " ".$order;
        }

        if (isset($searchParams['limit'])
            && !empty($searchParams['limit'])
            && !$count
        ) {
            $sql .= " LIMIT ".$searchParams['limit'];
        } else {
            if (!$count) {
                $sql .= " LIMIT ".self::DEFAULT_LIMIT_GROUPS;
            }
        }

        $PDO  = QUI::getDataBase()->getPDO();
        $Stmt = $PDO->prepare($sql);

        // bind search values
        foreach ($binds as $var => $bind) {
            $Stmt->bindValue(':'.$var, $bind['value'], $bind['type']);
        }

        // fetch information for all corresponding passwords
        try {
            $Stmt->execute();
            $result = $Stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $Exception) {
            QUI\System\Log::addError(
                '\QUI\UsersGrouüs\Search searchUsers() Database error :: '
                .$Exception->getMessage()
            );

            return [];
        }

        if ($count) {
            return (int)\current(\current($result));
        }

        $ids = [];

        foreach ($result as $row) {
            $ids[] = $row['id'];
        }

        return $ids;
    }

    /**
     * Search users and groups
     *
     * Returns only username, user ID and user Avatar
     *
     * @param string $searchTerm - search term
     * @param array $searchParams - search parameters
     *
     * @return array
     *
     * @throws QUI\Exception
     */
    public static function suggestSearch($searchTerm, $searchParams)
    {
        $searchUsers  = false;
        $searchGroups = false;
        $searchResult = [];

        // search in user table
        if (isset($searchParams['searchUsers']) && $searchParams['searchUsers']) {
            $searchUsers = true;
        }

        // search in group table
        if (isset($searchParams['searchGroups']) && $searchParams['searchGroups']) {
            $searchGroups = true;
        }

        if ($searchUsers === false && $searchGroups === false) {
            $searchUsers = true;
        }

        if ($searchUsers) {
            if (!isset($searchParams['users']) || empty($searchParams['users'])) {
                throw new QUI\Exception([
                    'quiqqer/quiqqer',
                    'exception.usergroups.search.cannot.search.users.without.parameters'
                ]);
            }

            $resultUsers = self::searchUsers($searchTerm, $searchParams['users']);

            if (!empty($resultUsers)) {
                $selectFields = [
                    'id',
                    'username'
                ];

                $result = QUI::getDataBase()->fetch([
                    'select' => $selectFields,
                    'from'   => QUI\Users\Manager::table(),
                    'where'  => [
                        'id' => [
                            'type'  => 'IN',
                            'value' => $resultUsers
                        ]
                    ]
                ]);

                foreach ($result as $row) {
                    $searchResult[] = [
                        'id'   => 'u'.$row['id'],
                        'name' => $row['username']
                    ];
                }
            }
        }

        if ($searchGroups) {
            if (!isset($searchParams['groups']) || empty($searchParams['groups'])) {
                throw new QUI\Exception([
                    'quiqqer/quiqqer',
                    'exception.usergroups.search.cannot.search.groups.without.parameters'
                ]);
            }

            $resultGroups = self::searchGroups($searchTerm, $searchParams);

            if (!empty($resultGroups)) {
                $selectFields = [
                    'id',
                    'name'
                ];

                $result = QUI::getDataBase()->fetch([
                    'select' => $selectFields,
                    'from'   => QUI\Groups\Manager::table(),
                    'where'  => [
                        'id' => [
                            'type'  => 'IN',
                            'value' => $resultGroups
                        ]
                    ]
                ]);

                foreach ($result as $row) {
                    $searchResult[] = [
                        'id'   => 'g'.$row['id'],
                        'name' => $row['name']
                    ];
                }
            }
        }

        return $searchResult;
    }
}
