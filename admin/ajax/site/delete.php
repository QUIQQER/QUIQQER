<?php

/**
 * Delete a site
 *
 * @param string $project
 * @param string $id
 * @return boolean
 */
QUI::$Ajax->registerFunction(
    'ajax_site_delete',
    function ($project, $id) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);

        return $Site->delete();
    },
    ['project', 'id'],
    ['Permission::checkAdminUser', 'quiqqer.projects.site.del']
);
