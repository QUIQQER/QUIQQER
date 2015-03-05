<?php

/**
 * QUIQQER Download Manager
 * Its only a test, here you can get the main README File ;-)
 *
 * @throws \QUI\Exception
 */
function ajax_downloadTest()
{
    sleep( 2 );
    QUI\Utils\System\File::downloadHeader( CMS_DIR .'README.md' );
}

\QUI::$Ajax->register(
    'ajax_downloadTest',
    false,
    'Permission::checkAdminUser'
);
