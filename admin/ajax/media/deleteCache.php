<?php

/**
 * Delete cache of a media item
 */

QUI::$Ajax->registerFunction(
    'ajax_media_deleteCache',
    static function ($project, $fileId): void {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();

        $Item = $Media->get($fileId);
        $Item->deleteCache();
    },
    ['project', 'fileId'],
    'Permission::checkAdminUser'
);
