<?php

/**
 * Search groups
 *
 * @param String $params
 * @return Array
 */
function ajax_usersgroups_search($params)
{
    $dir = dirname(dirname(__FILE__));

    require_once $dir.'/users/search.php';
    require_once $dir.'/groups/search.php';

    // users
    $users = ajax_users_search($params);

    // groups
    $params = json_decode($params, true);

    if (isset($params['searchSettings'])
        && isset($params['searchSettings']['userSearchString'])
    ) {
        $params['search'] = $params['searchSettings']['userSearchString'];
    }

    $groups = ajax_groups_search(
        json_encode($params)
    );

    // combine results
    $result = array(
        'page'  => $users['page'],
        'total' => $users['total'] + $groups['total'],
        'data'  => array_merge(
            $users['data'],
            $groups['data']
        )
    );

    return $result;
}

QUI::$Ajax->register(
    'ajax_usersgroups_search',
    array('params'),
    'Permission::checkAdminUser'
);
