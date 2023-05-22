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

use QUI\Projects\Media\Folder;
use QUI\Projects\Media\Utils;

QUI::$Ajax->registerFunction(
    'ajax_media_upload',
    function ($project, $parentid, $File) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media   = $Project->getMedia();
        $Folder  = $Media->get((int)$parentid);

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

        // check if image must be rotated
        $fInfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fInfo, $file);
        finfo_close($fInfo);

        try {
            if (in_array($mimeType, ['image/jpeg', 'image/tiff'])) {
                $exif = exif_read_data($file);

                if (!empty($exif['Orientation'])) {
                    // Decide orientation
                    if ($exif['Orientation'] == 3) {
                        $rotation = 180;
                    } else {
                        if ($exif['Orientation'] == 6) {
                            $rotation = -90;
                        } else {
                            if ($exif['Orientation'] == 8) {
                                $rotation = 90;
                            } else {
                                $rotation = 0;
                            }
                        }
                    }

                    // Rotate the image
                    if ($rotation) {
                        $ImageManager = $Media->getImageManager();
                        $Image        = $ImageManager->make($file);

                        $Image->rotate($rotation);
                        $Image->save();
                    }
                }
            }
        } catch (\Exception $Exception) {
        }

        $params = $File->getAttribute('params');

        // if file has a folder in original file path
        if (!empty($params) && !empty($params['filepath']) && strpos($params['filepath'], '/') !== false) {
            $path = trim($params['filepath'], '/');
            $path = explode('/', $path);

            array_pop($path);

            foreach ($path as $folder) {
                $folder = Utils::stripFolderName($folder);

                if ($Folder->childWithNameExists($folder)) {
                    $Folder = $Folder->getChildByName($folder);
                } else {
                    $Folder = $Folder->createFolder($folder);
                }
            }
        }

        return $Folder->uploadFile($file, Folder::FILE_OVERWRITE_TRUE)->getAttributes();
    },
    ['project', 'parentid', 'File'],
    'Permission::checkAdminUser'
);
