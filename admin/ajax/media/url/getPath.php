<?php

/**
 * Saves the data of a media file
 *
 * @param string $project - Name of the project
 * @param string|integer - File-ID
 * @param string $attributes - JSON Array, new file attributes
 *
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_media_url_getPath',
    function ($fileurl) {
        if (QUI\Projects\Media\Utils::isMediaUrl($fileurl) === false) {
            return $fileurl;
        }

        try {
            $File = QUI\Projects\Media\Utils::getMediaItemByUrl($fileurl);

            return URL_DIR . $File->getPath();
        } catch (QUI\Exception $Exception) {
        }

        return $fileurl;
    },
    array('fileurl'),
    'Permission::checkAdminUser'
);
