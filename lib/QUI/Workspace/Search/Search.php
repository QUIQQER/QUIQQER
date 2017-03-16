<?php

/**
 * This file contains QUI\Workspace\Search\Search
 */
namespace QUI\Workspace\Search;

use QUI;

/**
 * Class Search
 *
 * @package QUI\Workspace
 */
class Search
{
    /**
     * @var null
     */
    protected static $Instance = null;

    /**
     * @return Search
     */
    public static function getInstance()
    {
        if (is_null(self::$Instance)) {
            self::$Instance = new self();
        }

        return self::$Instance;
    }

    /**
     * Execute the search
     *
     * @param string $string - search string
     * @param array $params - search query params
     *
     * @return array
     */
    public function search($string, $params = array())
    {
        $DesktopSearch = Builder::getInstance();
        $groupFilter   = false;

//        $DesktopSearch->setup();
//        return array();

        $sql   = "SELECT * FROM " . $DesktopSearch->getTable();
        $where = array(
            '`search` LIKE :search',
            '`lang` = \'' . QUI::getUserBySession()->getLang() . '\''
        );
        $binds = array(
            'search' => array(
                'value' => '%' . $string . '%',
                'type'  => \PDO::PARAM_STR
            )
        );

        $where = array_merge($where, $DesktopSearch->getWhereConstraint($params['filterGroups']));

        if (isset($params['group'])
            && !empty($params['group'])
        ) {
            $where[]        = '`group` = :group';
            $binds['group'] = array(
                'value' => $params['group'],
                'type'  => \PDO::PARAM_STR
            );

            $groupFilter = true;
        }

        if (isset($params['filterGroups'])
            && !empty($params['filterGroups'])
            && is_array($params['filterGroups'])
        ) {
            $where[] = '`filterGroup` IN (\'' . implode("','", $params['filterGroups']) . '\')';
        }

        $sql .= " WHERE " . implode(' AND ', $where);

        if (isset($params['limit'])
            && !empty($params['limit'])
        ) {
            $sql .= " LIMIT " . (int)$params['limit'] * 3;
        } else {
            $limit           = (int)QUI::getConfig('etc/search.ini.php')->get('general', 'maxResultsPerGroup');
            $params['limit'] = $limit;  // set limit parameter for provider search

            $sql .= " LIMIT " . $limit;
        }

        $PDO  = QUI::getDataBase()->getPDO();
        $Stmt = $PDO->prepare($sql);

        foreach ($binds as $var => $bind) {
            $Stmt->bindValue(':' . $var, $bind['value'], $bind['type']);
        }

        try {
            $Stmt->execute();
            $result = $Stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $Exception) {
            QUI\System\Log::addError(
                self::class . ' :: search -> ' . $Exception->getMessage()
            );

            return array();
        }

        // get group counts
//        $countResult = QUI::getDataBase()->fetch(array(
//            'select' => array(
//                'group',
//                'COUNT(`group`)'
//            ),
//            'from'   => $DesktopSearch->getTable(),
//            'group'  => 'group'
//        ));
//
//        $groupCounts = array();
//
//        foreach ($countResult as $row) {
//            $groupCounts[$row['group']] = $row['COUNT(`group`)'];
//        }

        /* @var $Provider ProviderInterface */
        foreach ($DesktopSearch->getProvider() as $Provider) {
            try {
                $providerResult = $Provider->search($string, $params);
            } catch (\Exception $Exception) {
                QUI\System\Log::addError(
                    self::class . ' :: search -> ' . $Exception->getMessage()
                );

                continue;
            }

            if (!is_array($providerResult)) {
                continue;
            }

            foreach ($providerResult as $key => $product) {
                $product['provider']  = get_class($Provider);
                $providerResult[$key] = $product;
            }

            $result = array_merge($result, $providerResult);
        }

        return $result;

//        $groups = array();
//
//        foreach ($result as $row) {
//            $group = $row['group'];
//
//            if (!isset($groups[$group])) {
//                $groups[$group] = array(
//                    'count' => 0
//                );
//            }
//
//            $groups[$group]['count']++;
//        }
//
//        $searchResult = array(
//            'entries' => $result,
//            'groups'  => $groups
//        );

        // if specific group was requested -> do not limit results
//        if ($groupFilter) {
//            return $searchResult;
//        }

//        // max limit per group
//        $groupCount = array();
//        $groupLimit = (int)QUI::getConfig('etc/search.ini.php')->get('general', 'maxResultsPerGroup');
//
//        foreach ($result as $k => $row) {
//            $group = $row['group'];
//
//            if (!isset($groupCount[$group])) {
//                $groupCount[$group] = 0;
//            }
//
//            if ($groupCount[$group] >= $groupLimit) {
//                unset($result[$k]);
//            }
//
//            $groupCount[$group]++;
//        }
//
//        return $result;
    }

    /**
     * Return one search cache entry
     *
     * @param string $id
     * @return array
     */
    public function getEntry($id)
    {
        $result = QUI::getDataBase()->fetch(array(
            'from'  => Builder::getInstance()->getTable(),
            'where' => array(
                'id' => $id
            ),
            'limit' => 1
        ));

        return isset($result[0]) ? $result[0] : array();
    }
}
