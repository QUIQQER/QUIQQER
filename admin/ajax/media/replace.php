<?php

/**
 * Replace a file with another file
 *
 * @param string $project - name of the project
 * @param integer $fileid
 * @param QDOM $File
 */

use QUI\QDOM;

QUI::$Ajax->registerFunction(
    'ajax_media_replace',
    static function ($project, $fileid, $File): void {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();

        /* @var $File QDOM */
        $file = $File->getAttribute('filepath');

        if (!file_exists($file)) {
            return;
        }

        $Media->replace($fileid, $file);
    },
    ['project', 'fileid', 'File'],
    'Permission::checkAdminUser'
);
