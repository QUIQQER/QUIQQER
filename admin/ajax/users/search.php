<?php

/**
 * Search users
 *
 * @param string $params - JSON Array
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_users_search',
    function ($params) {
        $params = \json_decode($params, true);

        $Groups = QUI::getGroups();
        $Users  = QUI::getUsers();
        $page   = 1;
        $limit  = 10;

        $params['start'] = 0;

        if (isset($params['limit'])) {
            $limit = $params['limit'];
        }

        if (isset($params['field']) && $params['field'] == 'activebtn') {
            $params['field'] = 'active';
        }

        if (isset($params['page'])) {
            $page = (int)$params['page'];

            $params['start'] = ($page - 1) * $limit;
        }

        $search = $Users->search($params);
        $result = [];

        foreach ($search as $user) {
            if (!isset($user['usergroup'])) {
                $result[] = $user;
                continue;
            }

            $usergroups = \explode(',', \trim($user['usergroup'], ','));
            $groupnames = '';

            foreach ($usergroups as $gid) {
                if (!$gid) {
                    continue;
                }

                try {
                    $groupnames .= $Groups->getGroupNameById($gid).',';
                } catch (QUI\Exception $Exception) {
                    $groupnames .= $gid.',';
                }
            }

            $user['usergroup'] = \trim($groupnames, ',');

            if ($user['regdate'] != 0) {
                $user['regdate'] = \date('d.m.Y H:i:s', $user['regdate']);
            }

            $result[] = $user;
        }

        $Grid = new QUI\Utils\Grid();
        $Grid->setAttribute('page', $page);

        return $Grid->parseResult($result, $Users->count($params));
    },
    ['params'],
    'Permission::checkAdminUser'
);
