<?php

/**
 * Search a project
 *
 * @param string $params - search string
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_project_search',
    static function ($params): array {
        $params = json_decode($params, true);

        return QUI\Utils\Grid::getResult(
            QUI\Projects\Manager::search($params),
            1,
            10
        );
    },
    ['params'],
    'Permission::checkAdminUser'
);
