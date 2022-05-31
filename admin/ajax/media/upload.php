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
                            $rotation = 90;
                        } else {
                            if ($exif['Orientation'] == 8) {
                                $rotation = -90;
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

        return $Folder->uploadFile($file)->getAttributes();
    },
    ['project', 'parentid', 'File'],
    'Permission::checkAdminUser'
);
