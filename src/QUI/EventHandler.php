<?php

/**
 * This file contains \QUI\Intranet\EventHandler
 */

namespace QUI;

use DateTime;
use QUI;
use QUI\Users\Manager;

use function date;
use function is_array;
use function json_decode;
use function json_encode;
use function strpos;

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
    public static function onAdminLoadFooter(): void
    {
        $User = QUI::getUserBySession();

        if (!$User->getAttribute('quiqqer.set.new.password')) {
            return;
        }

        echo "<script>
            require(['Locale'], function(QUILocale) {
                const openChangePasswordWindow = function() {
                    require([
                        'controls/users/password/Window',
                        'Locale'
                    ], function(Password, QUILocale) {
                        new Password({
                            mustChange: true,
                            message: QUILocale.get('quiqqer/core', 'message.set.new.password')
                        }).open();
                    });
                };
           
                if (!QUILocale.exists('quiqqer/core', 'message.set.new.password')) {
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
    public static function onUserChangePassword(QUI\Interfaces\Users\User $User, string $newPass, string $oldPass): void
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
    public static function onPackageUpdate(QUI\Package\Package $Package): void
    {
        if ($Package->getName() !== "quiqqer/core") {
            return;
        }

        // Check if htaccess or nginx need to be recreated
        $webServerType = QUI::conf("webserver", "type");

        if (str_contains($webServerType, 'apache')) {
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
    public static function setPackageStoreUrl(): void
    {
        $packageStoreUrlConf = QUI::conf('packagestore', 'url');

        if (empty($packageStoreUrlConf)) {
            $packageStoreUrlConf = [];
        } else {
            $packageStoreUrlConf = json_decode($packageStoreUrlConf, true);

            if (empty($packageStoreUrlConf) || !is_array($packageStoreUrlConf)) {
                $packageStoreUrlConf = [];
            }
        }

        foreach (QUI::availableLanguages() as $lang) {
            $url = match ($lang) {
                'de' => 'https://store.quiqqer.de',
                default => 'https://store.quiqqer.com',
            };

            if (empty($packageStoreUrlConf[$lang])) {
                $packageStoreUrlConf[$lang] = $url;
            }
        }

        $Conf = QUI::getConfig('etc/conf.ini.php');
        $Conf->set('packagestore', 'url', json_encode($packageStoreUrlConf));
        $Conf->save();
    }

    /**
     * quiqqer/core: onUserLoginError
     *
     * Increase User failedLogins counter
     *
     * @param int|string $userId - ID of the QUIQQER user that tries to log in
     * @param QUI\Users\Exception $Exception
     * @return void
     */
    public static function onUserLoginError(int|string $userId, QUI\Users\Exception $Exception): void
    {
        switch ($Exception->getAttribute('reason')) {
            case QUI\Users\Manager::AUTH_ERROR_AUTH_ERROR:
                break;

            default:
                return;
        }

        try {
            $User = QUI::getUsers()->get($userId);
            $failedLogins = $User->getAttribute('failedLogins');

            if (empty($failedLogins)) {
                $failedLogins = 0;
            }

            $User->setAttributes([
                'failedLogins' => ++$failedLogins,
                'lastLoginAttempt' => date('Y-m-d H:i:s')
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
     * quiqqer/core: userAuthenticatorLoginStart
     *
     * @param int|string $userId
     * @param string $authenticator
     * @return void
     *
     * @throws QUI\Users\Exception
     */
    public static function onUserAuthenticatorLoginStart(int|string $userId, string $authenticator): void
    {
        self::onUserLoginStart($userId);
    }

    /**
     * quiqqer/core: onUserLoginStart
     *
     * @param int|string $userId
     * @return void
     *
     * @throws QUI\Users\Exception
     * @throws \Exception
     */
    public static function onUserLoginStart(int|string $userId): void
    {
        if (!$userId) {
            return;
        }

        try {
            $User = QUI::getUsers()->get($userId);
        } catch (\Exception) {
            // do nothing if user cannot be found
            return;
        }

        $failedLogins = (int)$User->getAttribute('failedLogins');
        $lastLoginAttempt = $User->getAttribute('lastLoginAttempt');

        if (!$failedLogins || !$lastLoginAttempt) {
            return;
        }

        $NextLoginAllowed = new DateTime($lastLoginAttempt . ' +' . $failedLogins . ' second');
        $Now = new DateTime();

        if ($Now < $NextLoginAllowed) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail.login_locked'],
                429
            );
        }
    }

    /**
     * quiqqer/core: onUserLogin
     *
     * @param Users\User $User
     * @return void
     */
    public static function onUserLogin(QUI\Users\User $User): void
    {
        try {
            $User->setAttributes([
                'failedLogins' => 0,
                'lastLoginAttempt' => false
            ]);

            // Directly update database and do not save user.
            QUI::getDataBase()->update(
                Manager::table(),
                [
                    'lastLoginAttempt' => null,
                    'failedLogins' => 0
                ],
                [
                    'uuid' => $User->getUUID()
                ]
            );
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }
}
