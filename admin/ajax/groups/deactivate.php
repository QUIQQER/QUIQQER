<?php

/**
 * Gruppe deaktivieren
 *
 * @param Int $uid - Gruppen-ID
 * @return Bool
 */
function ajax_groups_deactivate($gid)
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
            $Group->deactivate();

            $result[ $_gid ] = $Group->isActive() ? 1 : 0;

        } catch ( \QUI\Exception $Exception )
        {
            QUI::getMessagesHandler()->addException( $Exception );
            continue;
        }
    }

    return $result;
}

QUI::$Ajax->register(
	'ajax_groups_deactivate',
    array('gid'),
    'Permission::checkSU'
);

?>