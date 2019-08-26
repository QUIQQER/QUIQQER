<?php

/**
 * Save the available permissions to a user or a group
 *
 * @param string $params - JSON Array
 * @param string $btype - bind type (QUI.controls.users.User or QUI.controls.groups.Group)
 * @param string $permissions - JSON permissions
 * @throws \QUI\Exception
 */
QUI::$Ajax->registerFunction(
    'ajax_permissions_recursive',
    function ($params, $btype, $permissions) {
        $Manager     = QUI::getPermissionManager();
        $permissions = \json_decode($permissions, true);
        $params      = \json_decode($params, true);
        $errors      = 0;

        switch ($btype) {
            case 'classes/projects/project/Site':
                if (!isset($params['id'])) {
                    throw new QUI\Exception(
                        QUI::getLocale()->get('quiqqer/quiqqer', 'exception.missing.missing.index.id')
                    );
                }

                $Project = QUI::getProject($params['project'], $params['lang']);
                $Site    = $Project->get($params['id']);
                break;

            default:
                throw new QUI\Exception(
                    QUI::getLocale()->get('quiqqer/quiqqer', 'exception.missing.permission.entry')
                );
        }


        $childrenIds = $Site->getChildrenIdsRecursive([
            'active' => '0&1'
        ]);

        foreach ($childrenIds as $siteId) {
            try {
                $Manager->setPermissions(
                    new \QUI\Projects\Site\Edit($Project, $siteId),
                    $permissions
                );
            } catch (QUI\Exception $Exception) {
                QUI::getMessagesHandler()->addAttention(
                    $Exception->getMessage()
                );

                $errors++;
            }
        }

        if (!$errors) {
            QUI::getMessagesHandler()->addSuccess(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'permissions.message.save.success'
                )
            );
        }
    },
    ['params', 'btype', 'permissions'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.permissions'
    ]
);
