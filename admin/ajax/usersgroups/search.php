<?php

/**
 * Search groups
 *
 * @param string $params
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_usersgroups_search',
    function ($search, $fields, $params) {
        $fields = json_decode($fields, true);
        $params = json_decode($params, true);

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
            'groups'       => array('select' => $fields),
        );

        if (isset($params['limit'])) {
            $searchParams['limit'] = (int)$params['limit'];
        }

        return QUI\UsersGroups\Search::search($search, $searchParams);
    },
    array('search', 'fields', 'params'),
    'Permission::checkAdminUser'
);
