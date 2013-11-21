<?php

/**
 * This file contains a php standard upload
 * if the browser supports no html5 upload
 */

$dir = str_replace('lib/QUI/upload/bin', '', dirname( __FILE__ ));

require_once $dir .'bootstrap.php';

$QUM = new \QUI\Upload\Manager();

try
{
    $QUM->init();

} catch ( \QUI\Exception $Exception )
{
    \QUI\System\Log::writeException( $Exception );

    $QUM->flushMessage( $Exception->toArray() );
}
