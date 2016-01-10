<?php

/**
 * Move a site under another site
 *
 * @param string $project - project data
 * @param integer $id - site ID
 * @param integer $newParentId - new parent ID
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_site_move',
    function ($project, $id, $newParentId) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);

        $Site->move((int)$newParentId);
    },
    array('project', 'id', 'newParentId'),
    'Permission::checkAdminUser'
);
