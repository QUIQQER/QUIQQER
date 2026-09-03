<?php

/**
 * Send the file to the browser
 * The file must be opend directly in the browser
 *
 * @param string $project - Name of the project
 * @param string|integer $fileid - File-ID
 * @throws \QUI\Exception
 */

QUI::getAjax()->registerFunction(
    'ajax_media_file_preview',
    static function ($project, $fileid): void {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();
        $File = $Media->get($fileid);

        if (QUI\Projects\Media\Utils::isFolder($File)) {
            echo 'You cannot preview a Folder';
            exit;
        }

        $File->checkPermission('quiqqer.projects.media.view');
        QUI\Rewrite::sendFileWithRange(
            $File->getFullPath(),
            (string)$File->getAttribute('mime_type')
        );
    },
    ['project', 'fileid'],
    'Permission::checkAdminUser'
);
