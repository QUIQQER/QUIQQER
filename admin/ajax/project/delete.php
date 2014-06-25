<?php

/**
 * Create a new project
 *
 * @param Array $params
 */
function ajax_project_delete($project)
{
    return \QUI::getProjectManager()->deleteProject(
        \QUI::getProjectManager()->getProject( $project )
    );
}

\QUI::$Ajax->register(
    'ajax_project_delete',
    array( 'project' ),
    'Permission::checkSU'
);
