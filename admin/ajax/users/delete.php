<?php

/**
 * Delete Users
 *
 * @param JSONArray|Int $uid - Users-IDs
 * @return Bool
 */
function ajax_users_delete($uid)
{
    $Users = \QUI::getUsers();
    $uids  = json_decode( $uid, true );

    if ( !is_array( $uids ) ) {
        $uids = array( $uids );
    }

    foreach ( $uids as $uid ) {
        $Users->get( $uid )->delete();
    }

    \QUI::getMessagesHandler()->addInformation(
        'Die Benutzer '. implode(', ', $uids) . ' wurden erfolgreich gelöscht'
    );

    return true;
}

\QUI::$Ajax->register('ajax_users_delete', array('uid'), 'Permission::checkSU');
