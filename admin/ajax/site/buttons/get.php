<?php

/**
 * Return the action buttons from the site
 *
 * @param string $id
 * @param string $project
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_site_buttons_get',
    function ($project, $id) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);

        return QUI\Projects\Sites::getButtons($Site)->toArray();
    },
    array('project', 'id'),
    'Permission::checkAdminUser'
);
