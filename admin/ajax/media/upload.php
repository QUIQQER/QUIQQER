<?php

/**
 * Upload a file
 *
 * @param string $project - Name of the project
 * @param integer|string $parentid
 * @param QDOM $File
 *
 * @throws \QUI\Exception
 */

use QUI\Projects\Media\Folder;
use QUI\Projects\Media\Utils;
use QUI\QDOM;

QUI::getAjax()->registerFunction(
    'ajax_media_upload',
    static function ($project, $parentid, $File) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();
        $Folder = $Media->get((int)$parentid);
        $PermissionUser = QUI::getUserBySession();

        if ($Folder->getType() !== Folder::class) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/core', 'exception.media.upload.is.no.folder')
            );
        }

        $Folder->checkPermission('quiqqer.projects.media.upload', $PermissionUser);

        /* @var $Folder QUI\Projects\Media\Folder */
        /* @var $File QUI\QDOM */
        $file = $File->getAttribute('filepath');

        if (!file_exists($file)) {
            return '';
        }

        $params = $File->getAttribute('params');

        // if a file has a folder in an original file path
        if (!empty($params) && !empty($params['filepath']) && str_contains($params['filepath'], '/')) {
            $path = trim($params['filepath'], '/');
            $path = explode('/', $path);

            array_pop($path);

            foreach ($path as $folder) {
                $folder = Utils::stripFolderName($folder);

                if ($Folder->childWithNameExists($folder)) {
                    $Folder = $Folder->getChildByName($folder);
                } else {
                    $Folder = $Folder->createFolder($folder, $PermissionUser);
                }
            }
        }

        return $Folder->uploadFile($file, Folder::FILE_OVERWRITE_TRUE, $PermissionUser)->getAttributes();
    },
    ['project', 'parentid', 'File'],
    'Permission::checkAdminUser'
);
