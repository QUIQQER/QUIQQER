<?php

/**
 * Clear a site name
 *
 * @param string $project
 * @param string $name
 *
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_site_clear',
    function ($project, $name) {
        return QUI\Projects\Site\Utils::clearUrl(
            $name,
            QUI::getProjectManager()->decode($project)
        );
    },
    array('project', 'name'),
    'Permission::checkAdminUser'
);
