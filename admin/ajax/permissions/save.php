<?php

/**
 * Save the available permissions to a user or a group
 *
 * @param Integer $bid		  - bind id (user id or group id)
 * @param String $btype		  - bind type (QUI.controls.users.User or QUI.controls.groups.Group)
 * @param String $permissions - JSON permissions
 */
function ajax_permissions_save($params, $btype, $permissions)
{
    $Manager     = \QUI::getRights();
    $permissions = json_decode( $permissions, true );
    $params      = json_decode( $params, true );

    switch ( $btype )
    {
        case 'QUI.classes.users.User':
            $Bind = \QUI::getUsers()->get( $params['id'] );
        break;

        case 'QUI.classes.groups.Group':
            $Bind = \QUI::getGroups()->get( $params['id'] );
        break;

        case 'QUI.classes.projects.Project':
            $Bind = \QUI::getProject( $params['project'] );
        break;

        case 'QUI.classes.projects.Site':
            $Project = \QUI::getProject( $params['project'], $params['lang'] );
            $Bind    = $Project->get( $params['id'] );
        break;

        default:
            throw new \QException(
                'Cannot find permissions for Object'
            );
        break;
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
    array( 'params', 'btype', 'permissions' ),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.permissions'
    )
);

?>