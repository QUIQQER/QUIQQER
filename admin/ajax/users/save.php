<?php

/**
 * Benutzer speichern
 *
 * @param Int $uid - Benutzer-ID
 * @param JSON String $attributes - Attributes
 * @return Bool
 */
function ajax_users_save($uid, $attributes, $rights)
{
    $Users = QUI::getUsers();
    $User  = $Users->get( $uid );

    $attributes = json_decode( $attributes, true );
    $rights     = json_decode( $rights, true );

    foreach ( $attributes as $k => $v ) {
        $User->setAttribute( $k, $v );
    }

    foreach ( $rights as $k => $v ) {
        //$User->setAttribute( $k, $v );
    }

    $User->save();

    // aktivieren / deaktivieren
    if ( isset($attributes['active']) )
    {
        if ( (int)$attributes['active'] === 1 )
        {
            $User->activate();
        } else
        {
            $User->deactivate();
        }
    }

    QUI::getMessagesHandler()->addInformation(
    	'Der Benutzer '. $User->getName() .' ('. $User->getId() .') wurde erfolgreich gespeichert'
    );

    return true;
}

QUI::$Ajax->register(
	'ajax_users_save',
    array('uid', 'attributes', 'rights'),
    'Permission::checkSU'
);

?>