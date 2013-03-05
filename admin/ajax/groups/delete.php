<?php

/**
 * Delete groups
 *
 * @param JSON(Int|Array) $gid - Group-IDs
 * @return Array - Group-IDs which have been deleted
 */
function ajax_groups_delete($gids)
{
    $gids   = json_decode( $gids, true );
    $Groups = QUI::getGroups();

    if ( !is_array( $gids ) ) {
        $gids = array( $gids );
    }

    $result = array();

    foreach ( $gids as $gid )
    {
        try
        {
            $Groups->get( $gid )->delete();

            $result[] = $gid;

        } catch ( QException $Exception )
        {

        }
    }

    QUI::getMessagesHandler()->addInformation(
        'Die Gruppe(n) '. implode( ', ', $gids ) . ' wurde(n) erfolgreich gelöscht'
    );

    return $result;
}

QUI::$Ajax->register(
	'ajax_groups_delete',
    array( 'gids' ),
    'Permission::checkSU'
);

?>