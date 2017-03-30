<?php

/**
 * Search groups
 * Result is for a grid
 *
 * @param string $params - json array
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_groups_list',
    function ($params) {
        $Groups = QUI::getGroups();
        $params = json_decode($params, true);
        $page   = 1;
        $limit  = 10;

        $params['start'] = 0;

        if (isset($params['limit'])) {
            $limit = $params['limit'];
        }

        if (isset($params['page'])) {
            $page            = (int)$params['page'];
            $params['start'] = ($page - 1) * $limit;
        }

        $search = $Groups->search($params);
        $Groups = QUI::getGroups();

        foreach ($search as $key => $group) {
            try {
                $Group   = $Groups->get($group['id']);
                $isAdmin = $Group->hasPermission('quiqqer.admin');

                $search[$key]['admin'] = $isAdmin ? 1 : 0;
                $search[$key]['users'] = $Group->countUser();
            } catch (QUI\Exception $Group) {
                $search[$key]['admin'] = 0;
                $search[$key]['users'] = 0;
            }
        }

        return array(
            'total' => $Groups->count($params),
            'page'  => $page,
            'data'  => $search
        );
    },
    array('params'),
    'Permission::checkAdminUser'
);
