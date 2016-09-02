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
    function ($fileurl, $params) {
        if (QUI\Projects\Media\Utils::isMediaUrl($fileurl) === false) {
            return $fileurl;
        }

        if (!isset($params)) {
            $params = array();
        } else {
            $params = json_decode($params, true);
        }

        try {
            $File   = QUI\Projects\Media\Utils::getImageByUrl($fileurl);
            $width  = false;
            $height = false;

            if (isset($params['width'])) {
                $width = $params['width'];
            }

            if (isset($params['height'])) {
                $height = $params['height'];
            }

            $url = $File->getSizeCacheUrl($width, $height);

            if (!empty($url)) {
                return $url;
            }

            $url = $File->getUrl(true);

            if (empty($url)) {
                return $File->getUrl();
            }

            return $url;
        } catch (QUI\Exception $Exception) {
        }

        return $fileurl;
    },
    array('fileurl', 'params'),
    'Permission::checkAdminUser'
);
