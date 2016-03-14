<?php

/**
 * Search groups
 *
 * @param string $params
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_usersgroups_search',
    function ($params) {
        $dir    = dirname(dirname(__FILE__));
        $params = json_decode($params, true);

        require_once $dir . '/users/search.php';
        require_once $dir . '/groups/search.php';

        // users
        $users = QUI::$Ajax->callRequestFunction('ajax_users_search', array(
            'params' => json_encode($params)
        ));

        $users = $users['result'];


        // groups
        if (isset($params['searchSettings'])
            && isset($params['searchSettings']['userSearchString'])
        ) {
            $params['search']         = $params['searchSettings']['userSearchString'];
            $params['searchSettings'] = array('id', 'name');
        }

        $groups = QUI::$Ajax->callRequestFunction('ajax_groups_search', array(
            'params' => json_encode($params)
        ));

        $groups = $groups['result'];


        // combine results
        $result = array(
            'page' => $users['page'],
            'total' => $users['total'] + $groups['total'],
            'data' => array_merge(
                $users['data'],
                $groups['data']
            )
        );

        return $result;
    },
    array('params'),
    'Permission::checkAdminUser'
);
