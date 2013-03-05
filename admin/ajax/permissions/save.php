<?php

/**
 * Save the available permissions to a user or a group
 *
 * @param Integer $bid		  - bind id (user id or group id)
 * @param String $btype		  - bind type (QUI.controls.users.User or QUI.controls.groups.Group)
 * @param String $permissions - JSON permissions
 */
function ajax_permissions_save($bid, $btype, $permissions)
{
    $Manager     = \QUI::getRights();
    $permissions = json_decode( $permissions, true );
    $bid         = (int)$bid;

    switch ( $btype )
    {
        case 'QUI.classes.users.User':
            $Bind = \QUI::getUsers()->get( $bid );
        break;

        case 'QUI.classes.groups.Group':
            $Bind = \QUI::getGroups()->get( $bid );
        break;

        default:
            return;
    }

    $Manager->setPermissions( $Bind, $permissions );

    \QUI::getMessagesHandler()->addSuccess(
        \QUI::getLocale()->get(
        	'quiqqer/system',
        	'permissions.message.save.success'
        )
    );
}

\QUI::$Ajax->register(
	'ajax_permissions_save',
    array( 'bid', 'btype', 'permissions' ),
    array(
    	'Permission::checkAdminUser',
        'quiqqer.system.permissions'
    )
);

?>