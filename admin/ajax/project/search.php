<?php

/**
 * Search a project
 *
 * @param String $params - search string
 * @return Array
 */
function ajax_project_search($params)
{
    $params = json_decode( $params, true );

    return QUI\Utils\Grid::getResult(
        QUI\Projects\Manager::search( $params ),
        1,
        10
    );
}

QUI::$Ajax->register(
    'ajax_project_search',
    array( 'params' ),
    'Permission::checkAdminUser'
);
