<?php

/**
 * this file contains QUI\Lock\Locker
 */

namespace QUI\Lock;

use DateTime;
use QUI;
use QUI\Package\Package;
use Stash\Interfaces\ItemInterface;

use function is_null;
use function time;

/**
 * Class Lock
 * Helps to lock an item or an object
 */
class Locker
{
    /**
     * Lock an item or an object and checks the permissions
     *
     * @throws QUI\Permissions\Exception
     * @throws QUI\Lock\Exception
     */
    public static function lockWithPermissions(
        Package $Package,
        string $key,
        string $permission = '',
        null | QUI\Interfaces\Users\User $User = null
    ): void {
        if (is_null($User)) {
            $User = QUI::getUserBySession();
        }

        self::checkLocked($Package, $key, $User);

        if (!empty($permission)) {
            QUI\Permissions\Permission::checkPermission($permission, $User);
        }

        self::lock($Package, $key, false, $User);
    }

    /**
     * @throws QUI\Lock\Exception
     */
    public static function checkLocked(
        Package $Package,
        string $key,
        null | QUI\Interfaces\Users\User $User = null
    ): void {
        if (self::isLocked($Package, $key, $User)) {
            throw new QUI\Lock\Exception('Item is locked');
        }
    }

    /**
     * @param Package $Package
     * @param string $key
     * @param null|QUI\Interfaces\Users\User $User
     * @param bool $considerUser (optional) - Consider a $key as NOT locked if it was created by the given $User [default: true]
     *
     * @return mixed
     */
    public static function isLocked(
        Package $Package,
        string $key,
        null | QUI\Interfaces\Users\User $User = null,
        bool $considerUser = true
    ): mixed {
        if (is_null($User)) {
            $User = QUI::getUserBySession();
        }

        try {
            $uid = self::getStashData(self::getLockKey($Package, $key));

            if ($considerUser && $User->getUUID() == $uid) {
                return false;
            }

            try {
                return QUI::getUsers()->get($uid)->getUUID();
            } catch (QUI\Exception) {
                return $uid;
            }
        } catch (QUI\Lock\Exception) {
        }

        return false;
    }

    /**
     * Return the data from the cache
     *
     * @return mixed|null
     * @throws QUI\Lock\Exception
     */
    protected static function getStashData(string $name): mixed
    {
        $Item = self::getStash($name);
        $data = $Item->get();
        $isMiss = $Item->isMiss();

        if ($isMiss) {
            throw new QUI\Lock\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.lib.cache.manager.not.exist'
                ),
                404
            );
        }

        return $data;
    }

    /**
     * Return the stash item
     *
     * @throws QUI\Lock\Exception
     */
    protected static function getStash(string $name): ItemInterface
    {
        try {
            return QUI\Cache\Manager::getStash($name);
        } catch (\Exception) {
            throw new QUI\Lock\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.lib.cache.manager.not.exist'
                ),
                404
            );
        }
    }

    /**
     * Return the key for the lock item
     *
     * @throws QUI\Lock\Exception
     */
    protected static function getLockKey(Package $Package, string $key): string
    {
        if (empty($key)) {
            throw new QUI\Lock\Exception('Lock::lock() need a string as key');
        }

        return 'lock/' . $Package->getName() . '_' . $key;
    }

    /**
     * Lock an item or an object
     * no permission check
     *
     * @throws QUI\Lock\Exception
     */
    public static function lock(
        Package $Package,
        string $key,
        bool | int $lifetime = false,
        null | QUI\Interfaces\Users\User $User = null
    ): void {
        if (is_null($User)) {
            $User = QUI::getUserBySession();
        }

        $name = self::getLockKey($Package, $key);
        $value = $User->getUUID();

        if (!$lifetime) {
            $lifetime = QUI::conf('session', 'max_life_time');
        }

        $Item = self::getStash($name);
        $Item->expiresAfter($lifetime);
        $Item->set($value);
        $Item->save();
    }

    /**
     * Unlock an item or an object and checks the permissions
     *
     * @throws QUI\Permissions\Exception
     * @throws QUI\Lock\Exception
     */
    public static function unlockWithPermissions(
        Package $Package,
        string $key,
        string $permission = '',
        null | QUI\Interfaces\Users\User $User = null
    ): void {
        if (is_null($User)) {
            $User = QUI::getUserBySession();
        }

        $locked = self::isLocked($Package, $key, $User);

        if ($locked === false) {
            return;
        }

        if (!empty($permission)) {
            QUI\Permissions\Permission::checkPermission($permission, $User);
            self::unlock($Package, $key);

            return;
        }

        if (
            $User->isSU()
            || QUI::getUsers()->isSystemUser($User)
            || $locked === $User->getUUID()
            || (!empty($locked['id']) && $locked['id'] === $User->getUUID())
        ) {
            self::unlock($Package, $key);
        }
    }

    /**
     * Unlock an item or an object
     * no permission check
     *
     * @throws QUI\Lock\Exception
     */
    public static function unlock(Package $Package, string $key): void
    {
        $Item = self::getStash(self::getLockKey($Package, $key));
        $Item->clear();
    }

    /**
     * Return the seconds from the last lock
     *
     * @throws QUI\Lock\Exception
     */
    public static function getLockTime(Package $Package, string $key): int
    {
        $Item = self::getStash(self::getLockKey($Package, $key));
        $Expire = $Item->getExpiration();

        if (!($Expire instanceof DateTime)) {
            return 0;
        }

        return time() - $Expire->getTimestamp();
    }
}
