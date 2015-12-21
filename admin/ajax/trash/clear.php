<?php

/**
 * Destroy all deleted site ids
 *
 * @param string $project - Project data, JSON Array
 */
QUI::$Ajax->registerFunction(
    'ajax_trash_clear',
    function ($project) {
        $Project = QUI::getProjectManager()->decode($project);
        $Trash   = $Project->getTrash();

        $Trash->clear();
    },
    array('project'),
    'Permission::checkAdminUser'
);
