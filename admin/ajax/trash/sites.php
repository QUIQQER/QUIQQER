<?php

/**
 * Return the sites in the trash
 *
 * @param String $project - Project data, JSON Array
 * @param String $params  - JSON Array
 *
 * @return Array
 */
function ajax_trash_sites($project, $params)
{
    $Project = QUI::getProjectManager()->decode($project);
    $Trash = $Project->getTrash();

    return $Trash->getList(
        json_decode($params, true)
    );
}

QUI::$Ajax->register(
    'ajax_trash_sites',
    array('project', 'lang', 'params'),
    'Permission::checkAdminUser'
);
