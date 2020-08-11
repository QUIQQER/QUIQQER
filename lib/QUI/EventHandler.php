<?php

/**
 * This file contains \QUI\Intranet\EventHandler
 */

namespace QUI;

use QUI;

/**
 * Intranet
 *
 * @author www.pcsg.de
 */
class EventHandler
{
    /**
     * event on onAdminLoadFooter
     */
    public static function onAdminLoadFooter()
    {
        $User = QUI::getUserBySession();

        if (!$User->getAttribute('quiqqer.set.new.password')) {
            return;
        }

        echo "<script>
            var openChangePasswordWindow = function() {
                require([
                    'controls/users/password/Window',
                    'Locale'
                ], function(Password, QUILocale) {
                    new Password({
                        mustChange: true,
                        message: QUILocale.get('quiqqer/quiqqer', 'message.set.new.password')
                    }).open();
                });
            };
       
            require(['Locale'], function(QUILocale) {
                if (!QUILocale.exists('quiqqer/quiqqer', 'message.set.new.password')) {
                    (function() {
                        openChangePasswordWindow();
                    }).delay(2000);
                    return;
                }
                
                openChangePasswordWindow();
            });
    
        </script>";
    }

    /**
     * @param QUI\Interfaces\Users\User $User
     * @param string $newPass
     * @param string $oldPass
     */
    public static function onUserChangePassword(QUI\Interfaces\Users\User $User, $newPass, $oldPass)
    {
        $User->setAttribute('quiqqer.set.new.password', 0);
        $User->save(QUI::getUsers()->getSystemUser());
    }

    /**
     * Event: OnPackageUpdate
     *
     * @param Package\Package $Package
     *
     * @throws QUI\Exception
     */
    public static function onPackageUpdate(QUI\Package\Package $Package)
    {
        if ($Package->getName() != "quiqqer/quiqqer") {
            return;
        }

        // Check if htaccess or nginx need to be recreated
        $webServerType = QUI::conf("webserver", "type");

        if (\strpos($webServerType, 'apache') !== false) {
            $HtAccess = new QUI\System\Console\Tools\Htaccess();

            if ($HtAccess->hasModifications()) {
                $HtAccess->execute();

                QUI\System\Log::addInfo(
                    "Found changes in .htaccess. Recreating the htaccess file."
                );
            }
        }

        if ($webServerType == "nginx") {
            $Nginx = new QUI\System\Console\Tools\Nginx();

            if ($Nginx->hasModifications()) {
                $Nginx->execute();

                QUI\System\Log::addInfo(
                    "Found changes in nginx.conf . Recreating the nginx.conf file."
                );
            }
        }

        self::setPackageStoreUrl();
    }

    /**
     * Set (default) package store URL in QUIQQER settings
     *
     * @return void
     *
     * @throws QUI\Exception
     */
    public static function setPackageStoreUrl()
    {
        $packageStoreUrlConf = QUI::conf('packagestore', 'url');

        if (empty($packageStoreUrlConf)) {
            $packageStoreUrlConf = [];
        } else {
            $packageStoreUrlConf = \json_decode($packageStoreUrlConf, true);

            if (empty($packageStoreUrlConf) || !\is_array($packageStoreUrlConf)) {
                $packageStoreUrlConf = [];
            }
        }

        foreach (QUI::availableLanguages() as $lang) {
            switch ($lang) {
                case 'de':
                    $url = 'https://store.quiqqer.de';
                    break;

                default:
                    $url = 'https://store.quiqqer.com';
            }

            if (empty($packageStoreUrlConf[$lang])) {
                $packageStoreUrlConf[$lang] = $url;
            }
        }

        $Conf = QUI::getConfig('etc/conf.ini.php');
        $Conf->set('packagestore', 'url', \json_encode($packageStoreUrlConf));
        $Conf->save();
    }

    /**
     * quiqqer/quiqqer: onUserLoginError
     *
     * Increase User failedLogins counter
     *
     * @param int $userId - ID of the QUIQQER user that tries to log in
     * @param QUI\Users\Exception $Exception
     * @return void
     */
    public static function onUserLoginError($userId, QUI\Users\Exception $Exception)
    {
        switch ($Exception->getAttribute('reason')) {
            case QUI\Users\Manager::AUTH_ERROR_AUTH_ERROR:
                break;

            default:
                return;
        }

        try {
            $User         = QUI::getUsers()->get($userId);
            $failedLogins = $User->getAttribute('failedLogins');

            if (empty($failedLogins)) {
                $failedLogins = 0;
            }

            $User->setAttributes([
                'failedLogins'     => ++$failedLogins,
                'lastLoginAttempt' => \date('Y-m-d H:i:s')
            ]);

            $User->save(QUI::getUsers()->getSystemUser());
        } catch (QUI\Users\Exception $Exception) {
            // Log wrong username in auth.log
            QUI\System\Log::write(
                $Exception->getMessage(),
                QUI\System\Log::LEVEL_WARNING,
                [],
                'auth'
            );
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * quiqqer/quiqer: userAuthenticatorLoginStart
     *
     * @param int|false $userId
     * @param string $authenticator
     * @return void
     *
     * @throws QUI\Users\Exception
     */
    public static function onUserAuthenticatorLoginStart($userId, $authenticator)
    {
        self::onUserLoginStart($userId);
    }

    /**
     * quiqqer/quiqqer: onUserLoginStart
     *
     * @param int|false $userId
     * @return void
     *
     * @throws QUI\Users\Exception
     * @throws \Exception
     */
    public static function onUserLoginStart($userId)
    {
        if (!$userId) {
            return;
        }

        try {
            $User = QUI::getUsers()->get((int)$userId);
        } catch (\Exception $Exception) {
            // do nothing if user cannot be found
            return;
        }

        $failedLogins     = (int)$User->getAttribute('failedLogins');
        $lastLoginAttempt = $User->getAttribute('lastLoginAttempt');

        if (!$failedLogins || !$lastLoginAttempt) {
            return;
        }

        $NextLoginAllowed = new \DateTime($lastLoginAttempt.' +'.$failedLogins.' second');
        $Now              = new \DateTime();

        if ($Now < $NextLoginAllowed) {
            throw new QUI\Users\Exception(
                ['quiqqer/system', 'exception.login.fail.login_locked'],
                404
            );
        }
    }

    /**
     * quiqqer/quiqqer: onUserLogin
     *
     * @param Users\User $User
     * @return void
     */
    public static function onUserLogin(QUI\Users\User $User)
    {
        try {
            $User->setAttributes([
                'failedLogins'     => 0,
                'lastLoginAttempt' => false
            ]);

            $User->save(QUI::getUsers()->getSystemUser());
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }
}
