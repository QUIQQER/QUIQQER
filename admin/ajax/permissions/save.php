<?php

/**
 * Save the available permissions to a user or a group
 *
 * @param string $params - JSON Array
 * @param string $btype - bind type (QUI.controls.users.User or QUI.controls.groups.Group)
 * @param string $permissions - JSON permissions
 * @throws \QUI\Exception
 */
function ajax_permissions_save($params, $btype, $permissions)
{
    $Manager     = QUI::getPermissionManager();
    $permissions = json_decode($permissions, true);
    $params      = json_decode($params, true);

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
            if (!isset($params['id'])) {
                throw new QUI\Exception('Undefined index id');
            }

            $Project = QUI::getProject($params['project'], $params['lang']);
            $Bind    = $Project->get($params['id']);
            break;

        default:
            throw new QUI\Exception(
                'Cannot find permissions for Object'
            );
            break;
    }

    $Manager->setPermissions($Bind, $permissions);

    QUI::getMessagesHandler()->addSuccess(
        QUI::getLocale()->get(
            'quiqqer/system',
            'permissions.message.save.success'
        )
    );
}

QUI::$Ajax->register(
    'ajax_permissions_save',
    array('params', 'btype', 'permissions'),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.permissions'
    )
);
