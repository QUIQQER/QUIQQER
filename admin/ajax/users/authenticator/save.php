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
    static function ($authenticators): void {
        $User = QUI::getUserBySession();
        $authenticators = json_decode($authenticators, true);

        if (!$User->isSU()) {
            throw new QUI\Exception([
                'quiqqer/core',
                'exception.config.save.not.allowed'
            ]);
        }

        $Config = QUI::getConfig('etc/conf.ini.php');

        // cleanup
        $Config->del('auth');
        $Config->del('auth_frontend');
        $Config->del('auth_backend');
        $Config->del('auth_frontend_secondary');
        $Config->del('auth_backend_secondary');

        // setter
        if (!empty($authenticators['primary'])) {
            foreach (['backend', 'frontend'] as $type) {
                foreach ($authenticators['primary'][$type] as $authenticator) {
                    try {
                        QUI\Users\Auth\Handler::getInstance()->getAuthenticator(
                            $authenticator,
                            $User->getUsername()
                        );

                        $Config->setValue('auth_' . $type, $authenticator, 1);
                    } catch (QUI\Exception $Exception) {
                        QUI\System\Log::writeException($Exception);
                    }
                }
            }
        }

        if (!empty($authenticators['secondary'])) {
            foreach (['backend', 'frontend'] as $type) {
                foreach ($authenticators['secondary'][$type] as $authenticator) {
                    try {
                        QUI\Users\Auth\Handler::getInstance()->getAuthenticator(
                            $authenticator,
                            $User->getUsername()
                        );

                        $Config->setValue(
                            'auth_' . $type . '_secondary',
                            $authenticator,
                            1
                        );
                    } catch (QUI\Exception $Exception) {
                        QUI\System\Log::writeException($Exception);
                    }
                }
            }
        }

        $Config->save();
    },
    ['authenticators'],
    'Permission::checkAdminUser'
);
