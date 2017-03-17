<?php

namespace QUI\Workspace\Search\Provider;

use QUI;
use QUI\Workspace\Search\ProviderInterface;
use QUI\Permissions\Permission;

/**
 * Class UsersAndGroups
 *
 * Search QUIQQER users and groups
 *
 * @package QUI\Workspace\Search\Provider
 */
class UsersAndGroups implements ProviderInterface
{
    const FILTER_USERS_GROUPS = 'usersGroups';

    /**
     * Build the cache
     *
     * @return mixed
     */
    public function buildCache()
    {

    }

    /**
     * Execute a search
     *
     * @param string $search
     * @param array $params
     * @return mixed
     */
    public function search($search, $params = array())
    {
        if (isset($params['filterGroups'])
            && is_array($params['filterGroups'])
            && !in_array(self::FILTER_USERS_GROUPS, $params['filterGroups'])
        ) {
            return array();
        }

        $results = array();
        $PDO     = QUI::getDataBase()->getPDO();
        $Locale  = QUI::getLocale();

        // users
        if (Permission::hasPermission('quiqqer.admin.users.edit')) {
            $Users = QUI::getUsers();

            $sql = "SELECT users.id, users.username FROM ";
            $sql .= " `" . $Users->table() . "`, `" . $Users->tableAddress() . "` address";

            $where = array();

            // users table
            $where[] = "users.`id` LIKE :search";
            $where[] = "users.`username` LIKE :search";
            $where[] = "users.`firstname` LIKE :search";
            $where[] = "users.`lastname` LIKE :search";
            $where[] = "users.`email` LIKE :search";

            // users_address table
            $where[] = "address.`firstname` LIKE :search";
            $where[] = "address.`lastname` LIKE :search";
            $where[] = "address.`mail` LIKE :search";
            $where[] = "address.`company` LIKE :search";
            $where[] = "address.`street_no` LIKE :search";
            $where[] = "address.`zip` LIKE :search";
            $where[] = "address.`city` LIKE :search";

            $sql .= " WHERE " . implode(" OR ", $where);

            if (isset($params['limit'])) {
                $sql .= " LIMIT " . (int)$params['limit'];
            }

            $Stmt = $PDO->prepare($sql);

            // bind
            $Stmt->bindValue(':search', '%' . $search . '%', \PDO::PARAM_STR);
            $error = false;

            try {
                $Stmt->execute();
                $result = $Stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Exception $Exception) {
                QUI\System\Log::addError(
                    self::class . ' :: search (users) -> ' . $Exception->getMessage()
                );

                $error = true;
            }

            if (!$error) {
                foreach ($result as $row) {
                    $results[] = array(
                        'id'         => 'u' . $row['id'],
                        'title'      => $row['username'],
                        'icon'       => 'fa fa-user',
                        'group'      => 'users',
                        'groupLabel' => $Locale->get(
                            'quiqqer/quiqqer',
                            'search.builder.group.label.users'
                        )
                    );
                }
            }
        }

        // groups
        if (!Permission::hasPermission('quiqqer.admin.groups.edit')) {
            return $results;
        }

        try {
            $result = QUI::getDataBase()->fetch(array(
                'select'   => array(
                    'id',
                    'name'
                ),
                'from'     => QUI::getGroups()->table(),
                'where_or' => array(
                    'id'   => array(
                        'type'  => '%LIKE%',
                        'value' => $search
                    ),
                    'name' => array(
                        'type'  => '%LIKE%',
                        'value' => $search
                    )
                )
            ));
        } catch (\Exception $Exception) {
            QUI\System\Log::addError(
                self::class . ' :: search (groups) -> ' . $Exception->getMessage()
            );

            return $results;
        }

        foreach ($result as $row) {
            $results[] = array(
                'id'         => 'g' . $row['id'],
                'title'      => $row['name'],
                'icon'       => 'fa fa-users',
                'group'      => 'groups',
                'groupLabel' => $Locale->get(
                    'quiqqer/quiqqer',
                    'search.builder.group.label.groups'
                )
            );
        }

        return $results;
    }

    /**
     * Return a search entry
     *
     * @param integer $id
     * @return mixed
     */
    public function getEntry($id)
    {
        $type = mb_strtolower(mb_substr($id, 0, 1));

        return array(
            'searchdata' => array(
                'require' => 'package/quiqqer/quiqqer/bin/QUI/controls/workspace/search/provider/UsersAndGroups',
                'params'  => array(
                    'id'   => mb_substr($id, 1),
                    'type' => $type === 'u' ? 'user' : 'group'
                )
            )
        );
    }

    /**
     * Get all available search groups of this provider.
     * Search results can be filtered by these search groups.
     *
     * @return array
     */
    public function getFilterGroups()
    {
        $filterGroups = array();

        // add filters depending on permissions to edit users and/or groups
        if (Permission::hasPermission('quiqqer.admin.users.edit')
            || Permission::hasPermission('quiqqer.admin.groups.edit')
        ) {
            $filterGroups[] = array(
                'group' => self::FILTER_USERS_GROUPS,
                'label' => array(
                    'quiqqer/quiqqer',
                    'search.builder.filter.label.groups'
                )
            );
        }

        return $filterGroups;
    }
}
