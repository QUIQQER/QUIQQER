<?php

/**
 * Return authenticator lists
 *
 * @return array
 * @throws \QUI\Users\Exception
 */

use QUI\System\Log;
use QUI\Users\AuthenticatorInterface;

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_globalAuthenticators',
    static function () {
        $User = QUI::getUserBySession();
        $Auth = QUI\Users\Auth\Handler::getInstance();
        $Config = QUI::getConfig('etc/conf.ini.php');
        $available = $Auth->getAvailableAuthenticators();

        $list = [];

        foreach ($available as $authenticator) {
            try {
                $Authenticator = new $authenticator($User->getUsername());

                /* @var $Authenticator AuthenticatorInterface */
                $list[] = [
                    'title' => $Authenticator->getTitle(),
                    'description' => $Authenticator->getDescription(),
                    'authenticator' => $authenticator,
                    'isPrimaryAuthentication' => $Authenticator->isPrimaryAuthentication(),
                    'isSecondaryAuthentication' => $Authenticator->isSecondaryAuthentication()
                ];
            } catch (Exception $Exception) {
                Log::writeException($Exception);
            }
        }

        return [
            'global' => [
                'primary' => [
                    'frontend' => $Auth->getGlobalFrontendAuthenticators(),
                    'backend' => $Auth->getGlobalBackendAuthenticators()
                ],
                'secondary' => [
                    'frontend' => (int)$Config->getValue('auth_settings', 'secondary_frontend'),
                    'backend' => (int)$Config->getValue('auth_settings', 'secondary_backend')
                ]
            ],
            'available' => $list
        ];
    },
    false,
    'Permission::checkAdminUser'
);
