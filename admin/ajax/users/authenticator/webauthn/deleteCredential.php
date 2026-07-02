<?php

use QUI\Users\Auth\WebAuthn as WebAuthnAuthenticator;
use QUI\Users\Auth\WebAuthn\CredentialRepository;

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_webauthn_deleteCredential',
    static function ($id, $userUuid = ''): bool {
        $User = QUI::getUserBySession();

        if (QUI::getUsers()->isNobodyUser($User) && QUI::getSession()->get('uid')) {
            $User = QUI::getUsers()->get(QUI::getSession()->get('uid'));
        }

        if ($userUuid !== '' && (QUI::getUsers()->isNobodyUser($User) || $userUuid !== $User->getUUID())) {
            QUI\Permissions\Permission::checkAdminUser();
            $User = QUI::getUsers()->get($userUuid);
        }

        if (QUI::getUsers()->isNobodyUser($User)) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail.user.not.found'],
                404
            );
        }

        $repository = new CredentialRepository();
        $repository->deleteForUser((int)$id, $User->getUUID());

        if (empty($repository->findByUserUuid($User->getUUID()))) {
            $User->disableAuthenticator(WebAuthnAuthenticator::class, QUI::getUsers()->getSystemUser());
        }

        return true;
    },
    ['id', 'userUuid']
);
