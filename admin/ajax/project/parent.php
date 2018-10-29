<?php

/**
 * Return the parent ID of a site
 *
 * @param string $project - Project data; JSON Array
 * @param string|integer $id - Site-ID
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_project_parent',
    function ($project, $id) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = $Project->get($id);

        if (!$Site->getParentId()) {
            return 1;
        }

        return $Site->getParentId();
    },
    ['project', 'id']
);
