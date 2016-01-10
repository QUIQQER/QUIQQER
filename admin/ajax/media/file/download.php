<?php

/**
 * Send the file to the browser
 * The file must be opend directly in the browser
 *
 * @param string $project - name of the project
 * @param string|integer $fileid - File-ID
 * @throws \QUI\Exception
 */
QUI::$Ajax->registerFunction(
    'ajax_media_file_download',
    function ($project, $fileid) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media   = $Project->getMedia();
        $File    = $Media->get($fileid);

        if (QUI\Projects\Media\Utils::isFolder($File)) {
            echo 'You cannot download a Folder';
            exit;
        }

        QUI\Utils\System\File::downloadHeader($File->getFullPath());
    },
    array('project', 'fileid'),
    'Permission::checkAdminUser'
);
