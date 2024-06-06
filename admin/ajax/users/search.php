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
    static function ($params): array {
        $params = json_decode($params, true);

        $Groups = QUI::getGroups();
        $Users = QUI::getUsers();
        $page = 1;
        $limit = 10;

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
        $Locale = QUI::getLocale();

        foreach ($search as $user) {
            $user['id'] = $user['uuid'];

            if (!isset($user['usergroup'])) {
                $result[] = $user;
                continue;
            }

            $usergroups = explode(',', trim($user['usergroup'], ','));
            $groupnames = '';

            foreach ($usergroups as $gid) {
                if (!$gid) {
                    continue;
                }

                try {
                    $groupnames .= $Groups->getGroupNameById($gid) . ',';
                } catch (QUI\Exception) {
                    $groupnames .= $gid . ',';
                }
            }

            $user['usergroup'] = trim($groupnames, ',');

            if (empty($user['regdate'])) {
                $user['regdate'] = '-';
            } else {
                $RegDate = date_create('@' . $user['regdate']);

                if ($RegDate) {
                    $user['regdate'] = $Locale->formatDate($RegDate->getTimestamp());
                }
            }

            if (!empty($user['lastedit'])) {
                $LastEdit = date_create($user['lastedit']);

                if ($LastEdit) {
                    $user['lastedit'] = $Locale->formatDate($LastEdit->getTimestamp());
                } else {
                    $user['lastedit'] = '-';
                }
            } else {
                $user['lastedit'] = '-';
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
