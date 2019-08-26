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
    'ajax_site_unlink',
    function ($project, $id, $parentId, $all) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);

        $Site->deleteLinked((int)$parentId, $all);
    },
    array('project', 'id', 'parentId', 'all'),
    'Permission::checkAdminUser'
);
