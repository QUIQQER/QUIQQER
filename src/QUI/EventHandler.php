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
 * EventHandler
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

    public static function onUserChangePassword(QUI\Interfaces\Users\User $User, string $newPass, string $oldPass): void
    {
        $User->setAttribute('quiqqer.set.new.password', 0);
        $User->save(QUI::getUsers()->getSystemUser());
    }

    /**
     * @throws QUI\Exception
     */
    public static function onPackageSetup(QUI\Package\Package $Package): void
    {
        if ($Package->getName() !== 'quiqqer/core') {
            return;
        }

        self::cleanupLegacyAssetPackagePermissions();
    }

    /**
     * @throws QUI\Exception
     */
    public static function onPackageUpdate(QUI\Package\Package $Package): void
    {
        if ($Package->getName() !== "quiqqer/core") {
            return;
        }

        self::cleanupLegacyAssetPackagePermissions();

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
     * Remove legacy package permissions for quiqqer-asset packages.
     *
     * @throws QUI\Database\Exception
     */
    protected static function cleanupLegacyAssetPackagePermissions(): void
    {
        $Connection = QUI::getDataBaseConnection();
        $table = QUI\Permissions\Manager::table();

        $QueryBuilder = $Connection->createQueryBuilder();
        $permissions = $QueryBuilder
            ->select('name')
            ->from(QUI\Utils\Doctrine::quoteIdentifier($table))
            ->executeQuery()
            ->fetchAllAssociative();

        $permissionNames = [];

        foreach ($permissions as $permission) {
            if (!isset($permission['name'])) {
                continue;
            }

            $name = $permission['name'];

            if (
                strpos($name, 'quiqqer.packages.quiqqerasset') === 0
                || strpos($name, 'permission.quiqqer.packages.quiqqerasset') === 0
            ) {
                $permissionNames[] = $name;
            }
        }

        if (empty($permissionNames)) {
            return;
        }

        self::cleanupLegacyAssetPackagePermissionAssignments(
            $table . '2users',
            'user_id',
            $permissionNames
        );

        self::cleanupLegacyAssetPackagePermissionAssignments(
            $table . '2groups',
            'group_id',
            $permissionNames
        );

        foreach ($permissionNames as $permissionName) {
            $Connection->delete(
                QUI\Utils\Doctrine::quoteIdentifier($table),
                ['name' => $permissionName]
            );
        }

        QUI::$Rights = null;

        QUI\System\Log::addInfo(
            'Removed legacy quiqqer-asset package permissions.',
            ['count' => count($permissionNames)]
        );
    }

    /**
     * Remove stale permission keys from user/group permission blobs.
     *
     * @throws QUI\Database\Exception
     */
    protected static function cleanupLegacyAssetPackagePermissionAssignments(
        string $table,
        string $idField,
        array $permissionNames
    ): void {
        $QueryBuilder = QUI::getQueryBuilder();
        $rows = $QueryBuilder
            ->select($idField, 'permissions')
            ->from(QUI\Utils\Doctrine::quoteIdentifier($table))
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($rows as $row) {
            if (empty($row['permissions'])) {
                continue;
            }

            $permissions = json_decode($row['permissions'], true);

            if (!is_array($permissions)) {
                continue;
            }

            $hasChanges = false;

            foreach ($permissionNames as $permissionName) {
                if (!array_key_exists($permissionName, $permissions)) {
                    continue;
                }

                unset($permissions[$permissionName]);
                $hasChanges = true;
            }

            if (!$hasChanges) {
                continue;
            }

            QUI::getDataBaseConnection()->update(
                QUI\Utils\Doctrine::quoteIdentifier($table),
                ['permissions' => json_encode($permissions)],
                [$idField => $row[$idField]]
            );
        }
    }

    /**
     * Set (default) package store URL in QUIQQER settings
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
    public static function onUserLoginError(int | string $userId, QUI\Users\Exception $Exception): void
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
     * @throws QUI\Users\Exception
     */
    public static function onUserAuthenticatorLoginStart(int | string $userId): void
    {
        self::onUserLoginStart($userId);
    }

    /**
     * quiqqer/core: onUserLoginStart
     *
     * @throws QUI\Users\Exception
     * @throws \Exception
     */
    public static function onUserLoginStart(int | string $userId): void
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
     */
    public static function onUserLogin(QUI\Users\User $User): void
    {
        try {
            $User->setAttributes([
                'failedLogins' => 0,
                'lastLoginAttempt' => false
            ]);

            // Directly update database and do not save user.
            QUI::getDataBaseConnection()->update(
                QUI\Utils\Doctrine::quoteIdentifier(Manager::table()),
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
