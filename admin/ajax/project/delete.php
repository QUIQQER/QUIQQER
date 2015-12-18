<?php

/**
 * Delete a project
 *
 * @param string $project - Project data, JSON Array
 * @throws QUI\Exception
 */
function ajax_project_delete($project)
{
    QUI::getProjectManager()->deleteProject(
        QUI::getProjectManager()->decode($project)
    );
}

QUI::$Ajax->register(
    'ajax_project_delete',
    array('project'),
    'Permission::checkSU'
);
