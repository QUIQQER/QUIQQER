<?php

/**
 * Get the changelog from http://update.quiqqer.com/CHANGELOG
 *
 * @return String
 */
function ajax_system_changelog()
{
    return \Utils_Request_Url::get( 'http://update.quiqqer.com/CHANGELOG' );
}

\QUI::$Ajax->register(
    'ajax_system_changelog',
    false,
    'Permission::checkUser'
);
