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
    'ajax_permissions_get',
    function ($params, $btype) {
        $params  = \json_decode($params, true);
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
                $Bind    = $Project->get($params['id']);
                break;

            case 'classes/projects/project/media/File':
            case 'classes/projects/project/media/Folder':
            case 'classes/projects/project/media/Image':
            case 'classes/projects/project/media/Item':
                $Project = QUI::getProject($params['project']);
                $Media   = $Project->getMedia();
                $Bind    = $Media->get($params['id']);
                break;

            default:
                throw new QUI\Exception(
                    QUI::getLocale()->get('quiqqer/quiqqer', 'exception.missing.permission.entry')
                );
                break;
        }

        return $Manager->getPermissions($Bind);
    },
    ['params', 'btype'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.permissions'
    ]
);
