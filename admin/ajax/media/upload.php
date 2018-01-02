<?php

/**
 * Upload a file
 *
 * @param string $project - Name of the project
 * @param integer|string $parentid
 * @param \QUI\QDOM $File
 *
 * @throws \QUI\Exception
 */
QUI::$Ajax->registerFunction(
    'ajax_media_upload',
    function ($project, $parentid, $File) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media   = $Project->getMedia();
        $Folder  = $Media->get((int)$parentid);

        //#locale
        if ($Folder->getType() != 'QUI\\Projects\\Media\\Folder') {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.media.upload.is.no.folder')
            );
        }

        /* @var $Folder QUI\Projects\Media\Folder */
        /* @var $File QUI\QDOM */
        $file = $File->getAttribute('filepath');

        if (!file_exists($file)) {
            return '';
        }

        return $Folder->uploadFile($file)->getAttributes();
    },
    array('project', 'parentid', 'File'),
    'Permission::checkAdminUser'
);
