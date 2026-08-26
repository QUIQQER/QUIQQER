<?php

/**
 * If the image has an external image, the image will be updated
 *
 * @param string $project - Name of the project
 * @param string|integer $fileid - File-ID
 */

use QUI\Projects\Media\Image;

QUI::getAjax()->registerFunction(
    'ajax_media_file_externalUpdate',
    static function ($project, $fileid): void {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();
        $File = $Media->get((int)$fileid);

        if ($File instanceof Image) {
            $File->updateExternalImage();
        }
    },
    ['project', 'fileid'],
    'Permission::checkAdminUser'
);
