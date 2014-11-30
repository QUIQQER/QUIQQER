<?php

/**
 * Switch the groups status
 *
 * @param string $gid - JSON Integer | JSON Array
 * @return Array
 */
function ajax_groups_switchstatus($gid)
{
    $gid = json_decode( $gid, true );

    if ( !is_array($gid) ) {
        $gid = array( $gid );
    }

    $Groups = \QUI::getGroups();
    $result = array();

    foreach ( $gid as $_gid )
    {
        try
        {
            $Group = $Groups->get( $_gid );

            if ( $Group->isActive() )
            {
                $Group->deactivate();
            } else
            {
                $Group->activate();
            }

            $result[ $_gid ] = $Group->isActive() ? 1 : 0;

        } catch ( \QUI\Exception $Exception )
        {
            \QUI::getMessagesHandler()->addError(
                $Exception->getMessage()
            );

            continue;
        }
    }

    return $result;
}

\QUI::$Ajax->register(
	'ajax_groups_switchstatus',
    array('gid'),
    'Permission::checkSU'
);
