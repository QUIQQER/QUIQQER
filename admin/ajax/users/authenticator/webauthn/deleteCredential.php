<?php

use QUI\Users\Auth\WebAuthn as WebAuthnAuthenticator;
use QUI\Users\Auth\WebAuthn\CredentialRepository;
use QUI\Users\Auth\WebAuthn\Server;

QUI::getAjax()->registerFunction(
    'ajax_users_authenticator_webauthn_deleteCredential',
    static function ($id, $userUuid = ''): array {
        $id = (int)$id;
        $userUuid = (string)$userUuid;
        $repository = new CredentialRepository();
        $SessionUser = QUI::getUserBySession();

        if (
            QUI::getUsers()->isNobodyUser($SessionUser)
            || !(new Server($repository))->isFullyAuthenticatedUser($SessionUser)
        ) {
            throw new QUI\Permissions\Exception(
                ['quiqqer/core', 'exception.no.permission'],
                401
            );
        }

        $assertCanManageUser = static function (string $targetUserUuid) use ($SessionUser): void {
            if ((string)$SessionUser->getUUID() === $targetUserUuid) {
                return;
            }

            QUI\Permissions\Permission::checkPermission(
                'quiqqer.admin.users.edit',
                $SessionUser
            );
        };

        $credential = $repository->findById($id);

        if (!$credential) {
            $targetUserUuid = $userUuid ?: (string)$SessionUser->getUUID();
            $assertCanManageUser($targetUserUuid);

            return [
                'hasCredentials' => !empty($repository->findByUserUuid($targetUserUuid))
            ];
        }

        $credentialUserUuid = (string)$credential['userUuid'];

        if ($userUuid !== '' && $userUuid !== $credentialUserUuid) {
            throw new QUI\Permissions\Exception(
                ['quiqqer/core', 'exception.no.permission'],
                403
            );
        }

        $assertCanManageUser($credentialUserUuid);
        $User = (string)$SessionUser->getUUID() === $credentialUserUuid
            ? $SessionUser
            : QUI::getUsers()->get($credentialUserUuid);

        $repository->deleteForUser($id, $credentialUserUuid);
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
    ['id', 'userUuid'],
    'Permission::checkUser'
);
