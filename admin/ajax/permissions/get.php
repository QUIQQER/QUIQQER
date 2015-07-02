<?php

/**
 * Return the rights for the binded type (group or user)
 *
 * @param String $params - JSON array
 * @param String $btype  - binded object type
 *
 * @return array
 * @throws \QUI\Exception
 */
function ajax_permissions_get($params, $btype)
{
    $params = json_decode($params, true);
    $Manager = QUI::getPermissionManager();

    switch ($btype) {
        case 'classes/users/User':
            $Bind = QUI::getUsers()->get($params['id']);
            break;

        case 'classes/groups/Group':
            $Bind = QUI::getGroups()->get($params['id']);
            break;

        case 'classes/projects/Project':
            $Bind = QUI::getProject($params['project']);
            break;

        case 'classes/projects/project/Site':
            $Project = QUI::getProject($params['project'], $params['lang']);
            $Bind = $Project->get($params['id']);
            break;

        default:
            throw new QUI\Exception(
                'Cannot find permissions for Object'
            );
            break;
    }

    return $Manager->getPermissions($Bind);
}

QUI::$Ajax->register(
    'ajax_permissions_get',
    array('params', 'btype'),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.permissions'
    )
);
