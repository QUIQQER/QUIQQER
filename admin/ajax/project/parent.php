<?php

/**
 * Return the parent ID of a site
 *
 * @param string $project - Project data; JSON Array
 * @param string|integer $id - Site-ID
 * @return array
 */
function ajax_project_parent($project, $id)
{
    $Project = QUI::getProjectManager()->decode($project);
    $Site    = $Project->get($id);

    if (!$Site->getParentId()) {
        return 1;
    }

    return $Site->getParentId();
}

QUI::$Ajax->register(
    'ajax_project_parent',
    array('project', 'id')
);
