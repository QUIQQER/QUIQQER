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

        $list = array();

        foreach ($available as $authenticator) {
            try {
                $Authenticator = new $authenticator($User->getName());

                $list[] = array(
                    'title'         => $Authenticator->getTitle(),
                    'authenticator' => $authenticator
                );
            } catch (\Exception $Exception) {
                \QUI\System\Log::writeException($Exception);
            }
        }

        return array(
            'global'    => $Auth->getGlobalAuthenticators(),
            'available' => $list
        );
    },
    false,
    'Permission::checkAdminUser'
);
