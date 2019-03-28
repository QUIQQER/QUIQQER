<?php

/**
 * set the global authenticators
 *
 * @param integer|string $uid
 * @param string $authenticator
 * @return string
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

        foreach ($authenticators as $authenticator) {
            try {
                // exist authenticator?
                QUI\Users\Auth\Handler::getInstance()->getAuthenticator(
                    $authenticator,
                    $User->getUsername()
                );

                $Config->setValue('auth', $authenticator, 1);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        $Config->save();
    },
    ['authenticators'],
    'Permission::checkAdminUser'
);
