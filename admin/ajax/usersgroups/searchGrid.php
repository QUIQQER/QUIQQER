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
    },
    array('search', 'fields', 'params'),
    'Permission::checkAdminUser'
);
