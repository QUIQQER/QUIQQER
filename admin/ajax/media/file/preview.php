<?php

/**
 * Send the file to the browser
 * The file must be opend directly in the browser
 *
 * @param string $project - Name of the project
 * @param string|integer $fileid - File-ID
 * @throws \QUI\Exception
 */

QUI::$Ajax->registerFunction(
    'ajax_media_file_preview',
    static function ($project, $fileid): void {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();
        $File = $Media->get($fileid);

        if (QUI\Projects\Media\Utils::isFolder($File)) {
            echo 'You cannot preview a Folder';
            exit;
        }

        if (method_exists($File, 'getFullPath')) {
            QUI\Utils\System\File::fileHeader($File->getFullPath());
        }
    },
    ['project', 'fileid'],
    'Permission::checkAdminUser'
);
