<?php

/**
 * Switch the user status of the users
 *
 * @param {JSON Integer|JSON Array} $uid
 * @return Array
 */
function ajax_users_switchstatus($uid)
{
    $uid = json_decode( $uid, true );

    if ( !is_array($uid) ) {
        $uid = array( $uid );
    }

    $Users  = QUI::getUsers();
    $result = array();

    foreach ( $uid as $_uid )
    {
        try
        {
            $User = $Users->get( $_uid );

            if ( $User->isActive() )
            {
                $User->deactivate();
            } else
            {
                $User->activate();
            }

            $result[ $_uid ] = $User->isActive() ? 1 : 0;

        } catch ( QException $Exception )
        {
            QUI::getMessagesHandler()->addException( $Exception );
            continue;
        }
    }

    return $result;
}
QUI::$Ajax->register('ajax_users_switchstatus', array('uid'), 'Permission::checkSU');

?>