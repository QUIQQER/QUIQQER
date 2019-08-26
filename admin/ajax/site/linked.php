<?php

/**
 * Create a linkage / shortcut
 *
 * @param string $project
 * @param integer $id
 * @param integer $newParentId
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_site_linked',
    function ($project, $id, $newParentId) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);

        $Site->linked((int)$newParentId);
    },
    array('project', 'id', 'newParentId'),
    'Permission::checkAdminUser'
);
