<?php

/**
 * Has the user the permission?
 *
 * @param $permission - name of the permission
 */
function ajax_permissions_session_getPermission($permission, $ruleset)
{
    return \QUI::getUserBySession()->getPermission( $permission, $ruleset );
}

\QUI::$Ajax->register(
    'ajax_permissions_session_getPermission',
    array( 'permission', 'ruleset' )
);
