<?php

/**
 * Search a project
 *
 * @param String $search - search string
 * @param {Array}
 */
function ajax_project_search($params)
{
    $params = json_decode( $params, true );

    return Utils_Grid::getResult(
        \QUI\Projects\Manager::search( $params ),
        1,
        10
    );
}

\QUI::$Ajax->register(
    'ajax_project_search',
    array( 'params' ),
    'Permission::checkAdminUser'
);
