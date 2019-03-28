<?php

/**
 * Execute a site search
 *
 * @param string $search - search string
 * @param string $params - JSON Array
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_site_search',
    function ($search, $params) {
        $params = \json_decode($params, true);
        $page   = 1;

        if (isset($params['page']) && (int)$params['page']) {
            $page = (int)$params['page'];
        }

        $data = QUI\Projects\Sites::search($search, $params);

        $params['count'] = true;
        $total           = QUI\Projects\Sites::search($search, $params);

        return [
            'data'  => $data,
            'page'  => $page,
            'total' => $total
        ];
    },
    ['search', 'params'],
    'Permission::checkAdminUser'
);
