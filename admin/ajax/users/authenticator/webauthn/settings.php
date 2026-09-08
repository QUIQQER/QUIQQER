<?php

use QUI\Users\Auth\WebAuthn as WebAuthnAuthenticator;

QUI::getAjax()->registerFunction(
    'ajax_users_authenticator_webauthn_settings',
    static function ($userUuid = ''): string {
        $User = QUI::getUserBySession();

        if (QUI::getUsers()->isNobodyUser($User) && QUI::getSession()->get('uid')) {
            $User = QUI::getUsers()->get(QUI::getSession()->get('uid'));
        }

        if (QUI::getUsers()->isNobodyUser($User)) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail.user.not.found'],
                404
            );
        }

        if ($userUuid !== '' && $userUuid !== $User->getUUID()) {
            QUI\Permissions\Permission::checkAdminUser($User);
            QUI\Permissions\Permission::checkPermission(
                'quiqqer.admin.users.edit',
                $User
            );
            $User = QUI::getUsers()->get($userUuid);
        }

        $Settings = (new WebAuthnAuthenticator($User))->getSettingsControl();

        if (!$Settings) {
            return '';
        }

        $Output = new QUI\Output();
        $css = QUI\Control\Manager::getCSS();

        return $Output->parse($css . $Settings->create());
    },
    ['userUuid']
);
