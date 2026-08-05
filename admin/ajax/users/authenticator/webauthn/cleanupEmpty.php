<?php

use QUI\Users\Auth\WebAuthn as WebAuthnAuthenticator;
use QUI\Users\Auth\WebAuthn\CredentialRepository;

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_webauthn_cleanupEmpty',
    static function ($userUuid = ''): array {
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
            QUI\Permissions\Permission::checkAdminUser();
            $User = QUI::getUsers()->get($userUuid);
        }

        if ($userUuid === '') {
            $userUuid = $User->getUUID();
        }

        if ($User->getUUID() !== $userUuid) {
            throw new QUI\Permissions\Exception(
                ['quiqqer/core', 'exception.no.permission'],
                403
            );
        }

        $hasCredentials = !empty((new CredentialRepository())->findByUserUuid((string)$userUuid));

        if (!$hasCredentials) {
            try {
                $User->disableAuthenticator(WebAuthnAuthenticator::class, QUI::getUsers()->getSystemUser());
            } catch (QUI\Users\Exception $Exception) {
                if ($Exception->getCode() !== 404) {
                    throw $Exception;
                }
            }
        }

        return [
            'hasCredentials' => $hasCredentials
        ];
    },
    ['userUuid']
);
