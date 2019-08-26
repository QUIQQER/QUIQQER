<?php

use \QUI\Projects\Media\Utils as Utils;

/**
 * Return the rewrited url from an image.php url
 *
 * @param string $fileurl - File url
 * @param string|integer $maxWidth - wanted width of the file
 * @param string|integer $maxHeight - wanted height of the file
 *
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_media_url_resized',
    function ($fileurl, $maxWidth, $maxHeight) {
        if (Utils::isMediaUrl($fileurl) === false) {
            return $fileurl;
        }

        try {
            $File = Utils::getMediaItemByUrl($fileurl);

            if (!Utils::isImage($File)) {
                if (Utils::isFolder($File)) {
                    return Utils::getIconByExtension('folder');
                }

                return Utils::getIconByExtension(
                    Utils::getExtension($File->getFullPath())
                );
            }

            /* @var $File \QUI\Projects\Media\Image */
            return $File->getSizeCacheUrl($maxWidth, $maxHeight);
        } catch (QUI\Exception $Exception) {
        }

        return $fileurl;
    },
    array('fileurl', 'maxWidth', 'maxHeight'),
    'Permission::checkAdminUser'
);
