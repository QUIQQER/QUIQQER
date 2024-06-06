<?php

/**
 * Return current workspace
 */

QUI::$Ajax->registerFunction(
    'ajax_desktop_workspace_load',
    static function (): array {
        $list = QUI\Workspace\Manager::getWorkspacesByUser(QUI::getUserBySession());
        $executed = !count(QUI\InstallationWizard\ProviderHandler::getNotSetUpProviderList());
        $adminMail = QUI::conf('mail', 'admin_mail');

        if ($executed) {
            if (empty($adminMail)) {
                QUI::getMessagesHandler()->addError(
                    QUI::getLocale()->get('quiqqer/core', 'message.missing.admin.mail')
                );
            }
        }

        return $list;
    },
    false,
    'Permission::checkUser'
);
