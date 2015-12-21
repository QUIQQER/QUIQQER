<?php

/**
 * is the site from another user locked?
 *
 * @param string $project - Project data; JSON Array
 * @param string $id - Site ID
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_site_isLockedFromOther',
    function ($project, $id) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = new QUI\Projects\Site\Edit($Project, $id);

        return $Site->isLockedFromOther();
    },
    array('project', 'id'),
    'Permission::checkAdminUser'
);
