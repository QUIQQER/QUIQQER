<?php

/**
 * Return authenticator lists
 *
 * @return array
 * @throws \QUI\Users\Exception
 */
QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_globalAuthenticators',
    function () {
        $User      = QUI::getUserBySession();
        $Auth      = QUI\Users\Auth\Handler::getInstance();
        $available = $Auth->getAvailableAuthenticators();

        $list = [];

        foreach ($available as $authenticator) {
            try {
                $Authenticator = new $authenticator($User->getUsername());

                /* @var $Authenticator \QUI\Users\AuthenticatorInterface */
                $list[] = [
                    'title'         => $Authenticator->getTitle(),
                    'description'   => $Authenticator->getDescription(),
                    'authenticator' => $authenticator
                ];
            } catch (\Exception $Exception) {
                \QUI\System\Log::writeException($Exception);
            }
        }

        return [
            'global'    => [
                'frontend' => $Auth->getGlobalAuthenticators(),
                'backend'  => $Auth->getGlobalBackendAuthenticators()
            ],
            'available' => $list
        ];
    },
    false,
    'Permission::checkAdminUser'
);
