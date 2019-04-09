<?php

/**
 * set the global authenticators
 *
 * @param integer|string $uid
 * @param string $authenticator
 *
 * @throws \QUI\Users\Exception
 */
QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_save',
    function ($authenticators) {
        $User           = QUI::getUserBySession();
        $authenticators = \json_decode($authenticators, true);

        if (!$User->isSU()) {
            throw new QUI\Exception([
                'quiqqer/quiqqer',
                'exception.config.save.not.allowed'
            ]);
        }

        $Config = QUI::getConfig('etc/conf.ini.php');

        $Config->del('auth');
        $Config->del('auth_frontend');
        $Config->del('auth_backend');

        foreach ($authenticators as $authenticator => $range) {
            try {
                // exist authenticator?
                QUI\Users\Auth\Handler::getInstance()->getAuthenticator(
                    $authenticator,
                    $User->getUsername()
                );

                if (!isset($range['frontend'])) {
                    $range['frontend'] = 0;
                }

                if (!isset($range['backend'])) {
                    $range['backend'] = 0;
                }

                $Config->setValue('auth_frontend', $authenticator, $range['frontend']);
                $Config->setValue('auth_backend', $authenticator, $range['backend']);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        $Config->save();
    },
    ['authenticators'],
    'Permission::checkAdminUser'
);
