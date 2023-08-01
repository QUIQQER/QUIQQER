<?php

use QUI\Projects\Media\Utils as Utils;

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
    'ajax_media_url_getImageSize',
    function ($fileurl) {
        if (Utils::isMediaUrl($fileurl) === false) {
            return [
                'width' => 0,
                'height' => 0
            ];
        }

        try {
            $File = Utils::getMediaItemByUrl($fileurl);

            return [
                'width' => (int)$File->getAttribute('image_width'),
                'height' => (int)$File->getAttribute('image_height')
            ];
        } catch (QUI\Exception $Exception) {
        }

        return [
            'width' => 0,
            'height' => 0
        ];
    },
    ['fileurl'],
    'Permission::checkAdminUser'
);
