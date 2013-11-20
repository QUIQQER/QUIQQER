<?php

/**
 * Return the rewrited url from an image.php url
 *
 * @param String $image_url
 * @return String
 */
function ajax_media_url_rewrited($fileurl)
{
    if ( strpos( $fileurl, 'qui=1' ) === false ||
         strpos( $fileurl, 'image.php' ) === false ) {
        return $fileurl;
    }

    try
    {
        $File = Projects_Media_Utils::getImageByUrl( $fileurl );
        $url  = $File->getUrl( true );

        if ( empty( $url ) ) {
            return $File->getUrl();
        }

        return $url;

    } catch ( \QUI\Exception $e )
    {

    }

    return $fileurl;
}

QUI::$Ajax->register(
	'ajax_media_url_rewrited',
    array( 'fileurl' ),
    'Permission::checkAdminUser'
);

?>