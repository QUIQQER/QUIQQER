<?php

/**
 * Return the rights for the binded type (group or user)
 *
 * @param string $params - JSON array
 * @param string $btype - binded object type
 *
 * @return array
 * @throws \QUI\Exception
 */

QUI::$Ajax->registerFunction(
    'ajax_permissions_getCompleteList',
    static function ($params, $btype) {
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
                    QUI::getLocale()->get('quiqqer/core', 'exception.missing.permission.entry')
                );
        }

        return $Manager->getCompletePermissionList($Bind);
    },
    ['params', 'btype'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.permissions'
    ]
);
