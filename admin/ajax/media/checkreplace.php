<?php

/**
 * Checks, if a replacement of the file can be executed
 *
 * @param string $project - Name of the project
 * @param integer $fileid - File-ID
 * @param string $filename - File name
 * @param string $filetype - File type
 */

QUI::$Ajax->registerFunction(
    'ajax_media_checkreplace',
    static function ($project, $fileid, $filename, $filetype): void {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();

        // check before upload if a replacement is allowed
        QUI\Projects\Media\Utils::checkReplace($Media, $fileid, [
            'name' => $filename,
            'type' => $filetype
        ]);
    },
    ['project', 'fileid', 'filename', 'filetype'],
    'Permission::checkAdminUser'
);
