<?php

use QUI\Users\Auth\WebAuthn as WebAuthnAuthenticator;
use QUI\Users\Auth\WebAuthn\CredentialRepository;

QUI::getAjax()->registerFunction(
    'ajax_users_authenticator_webauthn_deleteCredential',
    static function ($id, $userUuid = ''): array {
        $repository = new CredentialRepository();
        $credential = $repository->findById((int)$id);

        if (!$credential) {
            return [
                'hasCredentials' => $userUuid !== '' && !empty($repository->findByUserUuid($userUuid))
            ];
        }

        $credentialUserUuid = (string)$credential['userUuid'];

        if ($userUuid !== '' && $userUuid !== $credentialUserUuid) {
            throw new QUI\Permissions\Exception(
                ['quiqqer/core', 'exception.no.permission'],
                403
            );
        }

        $SessionUser = QUI::getUserBySession();

        if (QUI::getUsers()->isNobodyUser($SessionUser) && QUI::getSession()->get('uid')) {
            $SessionUser = QUI::getUsers()->get(QUI::getSession()->get('uid'));
        }

        if (QUI::getUsers()->isNobodyUser($SessionUser)) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail.user.not.found'],
                404
            );
        }

        $User = $SessionUser;

        if ($SessionUser->getUUID() !== $credentialUserUuid) {
            QUI\Permissions\Permission::checkAdminUser();
            $User = QUI::getUsers()->get($credentialUserUuid);
        }

        if ($User->getUUID() !== $credentialUserUuid) {
            throw new QUI\Permissions\Exception(
                ['quiqqer/core', 'exception.no.permission'],
                403
            );
        }

        $repository->deleteForUser((int)$id, $credentialUserUuid);
        $hasCredentials = !empty($repository->findByUserUuid($credentialUserUuid));

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
    ['id', 'userUuid']
);
