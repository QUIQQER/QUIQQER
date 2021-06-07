<?php

/**
 * Delete cache of an media item
 */
QUI::$Ajax->registerFunction(
    'ajax_media_deleteCache',
    function ($project, $fileId) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media   = $Project->getMedia();

        $Item = $Media->get($fileId);
        $Item->deleteCache();
    },
    ['project', 'fileId'],
    'Permission::checkAdminUser'
);
