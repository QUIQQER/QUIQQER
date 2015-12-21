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
    function ($project, $fileid) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media   = $Project->getMedia();
        $File    = $Media->get($fileid);

        if (QUI\Projects\Media\Utils::isFolder($File)) {
            echo 'You cannot preview a Folder';
            exit;
        }

        QUI\Utils\System\File::fileHeader($File->getFullPath());
    },
    array('project', 'fileid'),
    'Permission::checkAdminUser'
);
