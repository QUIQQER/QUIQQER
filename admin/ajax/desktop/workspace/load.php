<?php

/**
 * Return current workspace
 */
QUI::$Ajax->registerFunction(
    'ajax_desktop_workspace_load',
    function () {
        $list = QUI\Workspace\Manager::getWorkspacesByUser(QUI::getUserBySession());
        $executed = !count(QUI\InstallationWizard\ProviderHandler::getNotSetUpProviderList());

        if ($executed) {
            if (!QUI::conf('mail', 'admin_mail') || QUI::conf('mail', 'admin_mail') === '') {
                QUI::getMessagesHandler()->addError(
                    QUI::getLocale()->get('quiqqer/quiqqer', 'message.missing.admin.mail')
                );
            }
        }

        return $list;
    },
    false,
    'Permission::checkUser'
);
