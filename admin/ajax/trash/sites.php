<?php

/**
 * Return the sites in the trash
 *
 * @param string $project - Project data, JSON Array
 * @param string $params - JSON Array
 *
 * @return array
 */
function ajax_trash_sites($project, $params)
{
    $Project = QUI::getProjectManager()->decode($project);
    $Trash   = $Project->getTrash();

    return $Trash->getList(
        json_decode($params, true)
    );
}

QUI::$Ajax->register(
    'ajax_trash_sites',
    array('project', 'params'),
    'Permission::checkAdminUser'
);
