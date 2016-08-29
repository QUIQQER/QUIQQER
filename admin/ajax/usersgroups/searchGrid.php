<?php

/**
 * Search groups
 *
 * @param string $params
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_usersgroups_searchGrid',
    function ($search, $fields, $params) {
        $fields = json_decode($fields, true);
        $params = json_decode($params, true);
        $limit  = 20; // default
        $page   = 1;

        if (!is_array($params)) {
            $params = array();
        }

        if (!is_array($fields)) {
            $fields = array(
                'name'      => true,
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
            );
        }

        $searchParams = array(
            'searchUsers'  => true,
            'searchGroups' => true,
            'users'        => array('select' => $fields),
            'groups'       => array('select' => $fields)
        );

        if (isset($params['limit'])) {
            $limit = (int)$params['limit'];
        }

        if (isset($params['page'])) {
            $page = (int)$params['page'];
        }

        $searchResult = QUI\UsersGroups\Search::search($search, $searchParams);

        $Grid = new QUI\Utils\Grid(array(
            'max'  => $limit,
            'page' => $page
        ));

        return $Grid->getResult(
            array_merge($searchResult['groups'], $searchResult['users']),
            $page,
            $limit
        );


//        $dir    = dirname(dirname(__FILE__));
//        $params = json_decode($params, true);
//
//        require_once $dir . '/users/search.php';
//        require_once $dir . '/groups/search.php';
//
//        // users
//        $users = QUI::$Ajax->callRequestFunction('ajax_users_search', array(
//            'params' => json_encode($params)
//        ));
//
//        $users = $users['result'];
//
//
//        // groups
//        if (isset($params['searchSettings'])
//            && isset($params['searchSettings']['userSearchString'])
//        ) {
//            $params['search']         = $params['searchSettings']['userSearchString'];
//            $params['searchSettings'] = array('id', 'name');
//        }
//
//        $groups = QUI::$Ajax->callRequestFunction('ajax_groups_search', array(
//            'fields' => json_encode(array()),
//            'params' => json_encode($params)
//        ));
//
//        $groups = $groups['result'];
//
//
//        // combine results
//        $result = array(
//            'page'  => $users['page'],
//            'total' => $users['total'] + $groups['total'],
//            'data'  => array_merge($users['data'], $groups['data'])
//        );
//
//        return $result;
    },
    array('search', 'fields', 'params'),
    'Permission::checkAdminUser'
);
