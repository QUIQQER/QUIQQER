<?php

/**
 * Set the config of an project
 */

function ajax_project_set_config($project, $params)
{
    \QUI\System\Log::writeRecursive($params);

    \QUI\Projects\Manager::setConfigForProject( $project, $params );
}

\QUI::$Ajax->register(
    'ajax_project_set_config',
    array( 'project', 'params' ),
    'Permission::checkAdminUser'
);
