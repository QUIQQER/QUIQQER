<?php

/**
 * Destroy all deleted site ids
 *
 * @param String $project - Project data, JSON Array
 */
function ajax_trash_clear($project)
{
    $Project = QUI::getProjectManager()->decode($project);
    $Trash   = $Project->getTrash();

    $Trash->clear();
}

QUI::$Ajax->register(
    'ajax_trash_clear',
    array('project'),
    'Permission::checkAdminUser'
);
