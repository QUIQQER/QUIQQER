<?php

/**
 * Get the elements in the media trash
 *
 * @param string $project - Project data, JSON Array
 * @param string $params
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_trash_media',
    function ($project, $params) {
        $Project = QUI::getProjectManager()->decode($project);
        $Media   = $Project->getMedia();
        $Trash   = $Media->getTrash();

        return $Trash->getList(
            \json_decode($params, true)
        );
    },
    ['project', 'params'],
    'Permission::checkAdminUser'
);
