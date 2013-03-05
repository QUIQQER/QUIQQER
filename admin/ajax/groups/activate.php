<?php

/**
 * Gruppe aktivieren
 *
 * @param Int $uid - Gruppen-ID
 * @return Bool
 */
function ajax_groups_activate($gid)
{
    $gid = json_decode( $gid, true );

    if ( !is_array($gid) ) {
        $gid = array( $gid );
    }

    $Groups = QUI::getGroups();
    $result = array();

    foreach ( $gid as $_gid )
    {
        try
        {
            $Group = $Groups->get( $_gid );
            $Group->activate();

            $result[ $_gid ] = $Group->isActive() ? 1 : 0;

        } catch ( QException $Exception )
        {
            QUI::getMessagesHandler()->addException( $Exception );
            continue;
        }
    }

    return $result;
}

QUI::$Ajax->register(
	'ajax_groups_activate',
    array('gid'),
    'Permission::checkSU'
);

?>