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
    'ajax_media_folder_download',
    function ($project, $folderId) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media   = $Project->getMedia();
        $File    = $Media->get($folderId);

        if (!QUI\Projects\Media\Utils::isFolder($File)) {
            QUI\Utils\System\File::downloadHeader($File->getFullPath());
            exit;
        }

        try {
            /* @var $File \QUI\Projects\Media\Folder */
            $zipFile = $File->createZIP();

            QUI\Utils\System\File::downloadHeader($zipFile);
        } catch (QUI\Exception $Exception) {
            header("Content-Type: text/html");

            $message = $Exception->getMessage();

            echo '<script>
            window.parent.QUI.getMessageHandler().then(function(MH) {
                MH.addError("' . $message . '");
            });
            </script>';
            exit;
        }
    },
    array('project', 'folderId'),
    'Permission::checkAdminUser'
);
