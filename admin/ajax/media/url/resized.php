<?php

/**
 * Return the rewritten resized url from an image.php url
 *
 * @param string $fileurl - File url
 * @param string|integer $maxWidth - wanted width of the file
 * @param string|integer $maxHeight - wanted height of the file
 *
 * @return string
 */

use QUI\Projects\Media\Image;
use QUI\Projects\Media\Utils as Utils;

QUI::$Ajax->registerFunction(
    'ajax_media_url_resized',
    static function ($fileurl, $maxWidth, $maxHeight) {
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

            if (method_exists($File, 'getSizeCacheUrl')) {
                return $File->getSizeCacheUrl($maxWidth, $maxHeight);
            }
        } catch (QUI\Exception) {
        }

        return $fileurl;
    },
    ['fileurl', 'maxWidth', 'maxHeight'],
    'Permission::checkAdminUser'
);
