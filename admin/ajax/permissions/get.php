<?php

/**
 * Return the rights for the binded type (group or user)
 *
 * @param $bid   - binded object id
 * @param $btype - binded object type
 *
 * @return array
 */
function ajax_permissions_get($params, $btype)
{
    $params  = json_decode( $params, true );
    $Manager = \QUI::getPermissionManager();

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

    return $Manager->getPermissions( $Bind );
}

QUI::$Ajax->register(
    'ajax_permissions_get',
    array( 'params', 'btype' ),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.permissions'
    )
);

?>