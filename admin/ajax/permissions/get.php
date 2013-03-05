<?php

/**
 * Return the rights for the binded type (group or user)
 *
 * @param $bid   - binded object id
 * @param $btype - binded object type
 *
 * @return array
 */
function ajax_permissions_get($bid, $btype)
{
    $Manager = QUI::getRights();

    switch ( $btype )
    {
        case 'QUI.classes.users.User':
            $Bind = \QUI::getUsers()->get( $bid );
        break;

        case 'QUI.classes.groups.Group':
            $Bind = \QUI::getGroups()->get( $bid );
        break;

        default:
            throw new \QException(
                'Cannot find permissions for Object'
            );
        break;
    }

    return $Manager->getPermissions( $Bind );
}

QUI::$Ajax->register(
	'ajax_permissions_get',
    array( 'bid', 'btype' ),
    array(
    	'Permission::checkAdminUser',
        'quiqqer.system.permissions'
    )
);

?>