<?php

/**
 * Return the rewrited url from an image.php url
 *
 * @param string $fileurl - image.php string
 *
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_media_url_rewrited',
    function ($fileurl) {
        if (QUI\Projects\Media\Utils::isMediaUrl($fileurl) === false) {
            return $fileurl;
        }

        try {
            $File = QUI\Projects\Media\Utils::getImageByUrl($fileurl);
            $url  = $File->getUrl(true);

            if (empty($url)) {
                return $File->getUrl();
            }

            return $url;

        } catch (QUI\Exception $Exception) {
        }

        return $fileurl;
    },
    array('fileurl'),
    'Permission::checkAdminUser'
);
