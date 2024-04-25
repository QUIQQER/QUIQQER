<?php

/**
 * this file contains QUI\Lock\Locker
 */

namespace QUI\Lock;

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
     * @param Package $Package
     * @param $key
     * @param string $permission - optional
     * @param null $User
     *
     * @throws QUI\Permissions\Exception
     * @throws QUI\Lock\Exception
     */
    public static function lockWithPermissions(
        Package $Package,
        $key,
        string $permission = '',
        $User = null
    ) {
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
     * Check, if the item is locked
     *
     * @param Package $Package
     * @param String $key
     * @param null|QUI\Interfaces\Users\User $User - default = session user
     *
     * @throws QUI\Lock\Exception
     */
    public static function checkLocked(
        Package $Package,
        string $key,
        ?QUI\Interfaces\Users\User $User = null
    ) {
        if (self::isLocked($Package, $key, $User)) {
            throw new QUI\Lock\Exception('Item is locked');
        }
    }

    /**
     * Check if an item or an object is locked
     *
     * @param Package $Package
     * @param string $key
     * @param null|QUI\Interfaces\Users\User $User
     * @param bool $considerUser (optional) - Consider a $key as NOT locked if it was created by the given $User [default: true]
     * @return false|mixed
     */
    public static function isLocked(
        Package $Package,
        string $key,
        ?QUI\Interfaces\Users\User $User = null,
        bool $considerUser = true
    ) {
        if (is_null($User)) {
            $User = QUI::getUserBySession();
        }

        try {
            $uid = self::getStashData(self::getLockKey($Package, $key));

            if ($considerUser && $User->getUUID() == $uid) {
                return false;
            }

            try {
                return QUI::getUsers()->get($uid)->getAttributes();
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
     * @param string $name
     * @return mixed|null
     * @throws QUI\Lock\Exception
     */
    protected static function getStashData(string $name)
    {
        $Item = self::getStash($name);
        $data = $Item->get();
        $isMiss = $Item->isMiss();

        if ($isMiss) {
            throw new QUI\Lock\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
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
     * @param string $name
     * @return ItemInterface
     * @throws QUI\Lock\Exception
     */
    protected static function getStash(string $name): ItemInterface
    {
        try {
            return QUI\Cache\Manager::getStash($name);
        } catch (\Exception) {
            throw new QUI\Lock\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.lib.cache.manager.not.exist'
                ),
                404
            );
        }
    }

    /**
     * Return the key for the lock item
     *
     * @param Package $Package
     * @param string $key
     * @return string
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
     * @param Package $Package
     * @param string $key
     * @param bool|integer $lifetime
     * @param null|QUI\Interfaces\Users\User $User
     *
     * @throws QUI\Lock\Exception
     */
    public static function lock(
        Package $Package,
        string $key,
        $lifetime = false,
        QUI\Interfaces\Users\User $User = null
    ) {
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
     * @param Package $Package
     * @param $key
     * @param string $permission - optional
     * @param null $User
     *
     * @throws QUI\Permissions\Exception
     * @throws QUI\Lock\Exception
     */
    public static function unlockWithPermissions(
        Package $Package,
        $key,
        string $permission = '',
        $User = null
    ) {
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
     * @param Package $Package
     * @param string $key
     * @throws QUI\Lock\Exception
     */
    public static function unlock(Package $Package, string $key)
    {
        $Item = self::getStash(self::getLockKey($Package, $key));
        $Item->clear();
    }

    /**
     * Return the seconds from the last lock
     *
     * @param Package $Package
     * @param string $key
     * @return int
     * @throws QUI\Lock\Exception
     */
    public static function getLockTime(Package $Package, string $key): int
    {
        $Item = self::getStash(self::getLockKey($Package, $key));
        $Expire = $Item->getExpiration();

        if ($Expire === false) {
            return 0;
        }

        return time() - $Expire->getTimestamp();
    }
}
