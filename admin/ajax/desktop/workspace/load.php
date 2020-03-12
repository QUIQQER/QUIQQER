<?php

/**
 * Return current workspace
 */
QUI::$Ajax->registerFunction(
    'ajax_desktop_workspace_load',
    function () {
        $list = QUI\Workspace\Manager::getWorkspacesByUser(
            QUI::getUserBySession()
        );

        if (!QUI::conf('mail', 'admin_mail') || QUI::conf('mail', 'admin_mail') === '') {
            QUI::getMessagesHandler()->addAttention(
                QUI::getLocale()->get('quiqqer/quiqqer', 'message.missing.admin.mail')
            );
        }

        return $list;
    },
    false,
    'Permission::checkUser'
);
