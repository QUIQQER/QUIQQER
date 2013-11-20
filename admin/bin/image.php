<?php

/**
 * Admin Bildercache
 */

require_once '../header.php';

if ( !QUI::getUserBySession()->isAdmin() )
{
    header( "HTTP/1.1 404 Not Found" );
    exit;
}

if ( !isset($_REQUEST['project']) || !isset($_REQUEST['id']) )
{
    header( "HTTP/1.1 404 Not Found" );
    exit;
}

try
{
    $Project = Projects_Manager::getProject($_REQUEST['project']);
    $Media   = $Project->getMedia();
    $File    = $Media->get( (int)$_REQUEST['id'] );

} catch ( \QUI\Exception $Exception )
{
    header( "HTTP/1.0 404 Not Found" );
    \QUI\System\Log::writeException( $Exception );
    exit;
}

if ( $File->getType() != 'Projects_Media_Image' )
{
    header( "HTTP/1.1 404 Not Found" );
    exit;
}

$file  = $File->getAttribute('file');
$image = $File->getFullPath();

if ( !file_exists($image) )
{
    header( "HTTP/1.0 404 Not Found" );
    \QUI\System\Log::write( 'File not exist '. $image, 'error' );
    exit;
}

// resize
if ( isset($_REQUEST['maxwidth']) || isset($_REQUEST['maxheight']) )
{
    $maxwidth  = false;
    $maxheight = false;

    if ( isset($_REQUEST['maxwidth']) ) {
        $maxwidth = (int)$_REQUEST['maxwidth'];
    }

    if ( isset($_REQUEST['maxheight']) ) {
        $maxheight = (int)$_REQUEST['maxheight'];
    }

    $size = $File->getResizeSize( $maxwidth, $maxheight );

    $width  = $size['width'];
    $height = $size['height'];

    $cache_folder = VAR_DIR .'media_cache/'. $Project->getAttribute('name') .'/';
    \QUI\Utils\System\File::mkdir( $cache_folder );

    $new_image = $cache_folder . $File->getId() .'_'. $width .'x'. $height;

    if ( !file_exists( $new_image ) )
    {
        try
        {
            \QUI\Utils\System\File::resize(
                $image,
                $new_image,
                $width,
                $height
            );

        } catch ( \QUI\Exception $Exception )
        {
            header( "HTTP/1.0 404 Not Found" );
            \QUI\System\Log::writeException( $Exception );
            exit;
        }
    }

    $image = $new_image;
}

header( "Content-Type: ". $File->getAttribute( 'mime_type' ) );
header( "Expires: ". gmdate("D, d M Y H:i:s") . " GMT" );
header( "Pragma: public" );
header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
header( "Accept-Ranges: bytes" );
header( "Content-Disposition: inline; filename=\"". pathinfo( $image, PATHINFO_BASENAME ) ."\"" );
header( "Content-Size: ". filesize( $image ) );
header( "Content-Length: ". filesize( $image ) );
header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
header( "Connection: Keep-Alive" );

$fo_image = fopen( $image, "r" );
$fr_image = fread( $fo_image, filesize($image) );
fclose( $fo_image );

echo $fr_image;
