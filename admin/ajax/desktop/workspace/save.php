<?php

/**
 * Return all widgets of a desktop
 *
 * @param Integer $did - Desktop-ID
 */
function ajax_desktop_workspace_save($data)
{
    \QUI\System\Log::writeRecursive( $data );



}

\QUI::$Ajax->register(
    'ajax_desktop_workspace_save',
    array( 'data' ),
    'Permission::checkUser'
);
