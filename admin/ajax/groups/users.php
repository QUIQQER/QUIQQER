<?php

/**
 * Benutzer bekommen welche in der Gruppe sind
 *
 * @param integer $gid - Group-ID
 * @param string $params - json array, search params
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_groups_users',
    function ($gid, $params) {
        $params = json_decode($params, true);

        $start = 0;
        $page  = 1;
        $limit = 20;

        $params['start'] = 0;

        if (isset($params['limit'])) {
            $limit = $params['limit'];
        }

        if (isset($params['page'])) {
            $page            = (int)$params['page'];
            $params['start'] = ($page - 1) * $limit;
        }

        if (isset($params['limit']) || isset($params['start'])) {
            if (isset($params['limit'])) {
                $limit = (int)$params['limit'];
            }

            if (isset($params['start'])) {
                $start = (int)$params['start'];
            }

            $params['limit'] = $start . ', ' . $limit;
        }

        // order
        if (isset($params['field'])) {
            switch ($params['field']) {
                case 'id':
                case 'username':
                case 'firstname':
                case 'lasttname':
                case 'email':
                case 'regdate':
                    $params['order'] = $params['field'] . ' ' . $params['order'];
                    break;

                default:
                    $params['order'] = 'username ' . $params['order'];
                    break;
            }
        }

        if (!isset($params['field'])) {
            $params['order'] = '';
        }

        // search users
        $users  = QUI::getGroups()->get($gid)->getUsers($params);
        $result = array();

        foreach ($users as $user) {
            $result[] = array(
                'id' => $user['id'],
                'active' => $user['active'],
                'username' => $user['username'],
                'email' => $user['email'],
                'firstname' => $user['firstname'],
                'lastname' => $user['lastname'],
                'regdate' => $user['regdate']
            );
        }

        // count users
        $params['count'] = 'cu';
        $params['limit'] = false;

        $count = QUI::getGroups()->get($gid)->getUsers($params);

        return array(
            'total' => isset($count[0]['cu']) ? $count[0]['cu'] : 0,
            'page' => $page,
            'data' => $result
        );

    },
    array('gid', 'params'),
    'Permission::checkSU'
);
