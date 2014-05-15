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
    $User = \QUI::getUsers()->get( $uid );

    $attributes = json_decode( $attributes, true );
    //$rights     = json_decode( $rights, true );
    /*
    if ( isset( $attributes['extra'] ) )
    {
        foreach ( $attributes['extra'] as $key => $value ) {
            $User->setExtra( $key, $value );
        }

        unset( $attributes['extra'] );
    }
    */


    foreach ( $attributes as $key => $value ) {
        $User->setAttribute( $key, $value );
    }

    /*
    foreach ( $rights as $k => $v ) {
        //$User->setAttribute( $k, $v );
    }
    */

    $User->save();

    // aktivieren / deaktivieren
    if ( isset( $attributes['active'] ) )
    {
        if ( (int)$attributes['active'] === 1 )
        {
            if ( !$User->isActive() ) {
                $User->activate();
            }

        } else
        {
            $User->deactivate();
        }
    }

    \QUI::getMessagesHandler()->addInformation(
        'Der Benutzer '. $User->getName() .' ('. $User->getId() .') wurde erfolgreich gespeichert'
    );

    return true;
}

\QUI::$Ajax->register(
    'ajax_users_save',
    array( 'uid', 'attributes', 'rights' ),
    'Permission::checkSU'
);
