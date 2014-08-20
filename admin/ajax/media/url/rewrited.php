
<?php

/**
 * Return the rewrited url from an image.php url
 *
 * @param String $image_url
 * @return String
 */
function ajax_media_url_rewrited($fileurl)
{
    if ( \QUI\Projects\Media\Utils::isMediaUrl( $fileurl ) === false ) {
        return $fileurl;
    }

    try
    {
        $File = \QUI\Projects\Media\Utils::getImageByUrl( $fileurl );
        $url  = $File->getUrl( true );

        if ( empty( $url ) ) {
            return $File->getUrl();
        }

        return $url;

    } catch ( \QUI\Exception $Exception )
    {

    }

    return $fileurl;
}

\QUI::$Ajax->register(
    'ajax_media_url_rewrited',
    array( 'fileurl' ),
    'Permission::checkAdminUser'
);