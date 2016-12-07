<?php

/**
 * this file contains QUI\Lock\Locker
 */
namespace QUI\Lock;

use QUI;
use QUI\Package\Package;

/**
 * Class Lock
 * Helps to lock a item or an object
 *
 * @package QUI
 */
class Locker
{
    /**
     * Lock a item or an object
     *
     * @param Package $Package
     * @param string $key
     * @param bool|integer $lifetime
     */
    public static function lock(Package $Package, $key, $lifetime = false)
    {
        $name  = self::getLockKey($Package, $key);
        $value = QUI::getUserBySession()->getId();

        if (!$lifetime) {
            $lifetime = QUI::conf('session', 'max_life_time');
        }

        $Item = self::getStash($name);
        $Item->expiresAfter($lifetime);
        $Item->set($value);
        $Item->save();
    }

    /**
     * Unlock a item or an object
     *
     * @param Package $Package
     * @param string $key
     * @param null|QUI\Interfaces\Users\User $User
     */
    public static function unlock(Package $Package, $key, $User = null)
    {
        if (is_null($User)) {
            $User = QUI::getUserBySession();
        }

        $locked = self::isLocked($Package, $key);

        if ($locked === false) {
            return;
        }

        if ($User->isSU()
            || QUI::getUsers()->isSystemUser($User)
            || $locked === $User->getId()
        ) {
            $Item = self::getStash(self::getLockKey($Package, $key));
            $Item->clear();
        }
    }

    /**
     * Check if a item or an object is locked
     *
     * @param Package $Package
     * @param string $key
     * @param null|QUI\Interfaces\Users\User $User
     * @return false|mixed
     */
    public static function isLocked(Package $Package, $key, $User = null)
    {
        if (is_null($User)) {
            $User = QUI::getUserBySession();
        }

        try {
            $uid = self::getStashData(self::getLockKey($Package, $key));

            if ($User->getId() == $uid) {
                return false;
            }

            return $uid;
        } catch (QUI\Lock\Exception $Exception) {
        }

        return false;
    }

    /**
     * @param Package $Package
     * @param String $key
     * @throws QUI\Exception
     */
    public static function checkLocked(Package $Package, $key)
    {
        if (self::isLocked($Package, $key)) {
            throw new QUI\Lock\Exception('Item is locked');
        }
    }

    /**
     * Return the seconds from the last lock
     *
     * @param Package $Package
     * @param string $key
     * @return int
     */
    public static function getLockTime(Package $Package, $key)
    {
        $Item   = self::getStash(self::getLockKey($Package, $key));
        $Expire = $Item->getExpiration();

        if ($Expire === false) {
            return 0;
        }

        return time() - $Expire->getTimestamp();
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
    protected static function getLockKey(Package $Package, $key)
    {
        if (!is_string($key) || empty($key)) {
            throw new QUI\Lock\Exception('Lock::lock() need a string as key');
        }

        return 'lock/' . $Package->getName() . '_' . $key;
    }

    /**
     * Return the stash item
     *
     * @param string $name
     * @return \Stash\Interfaces\ItemInterface
     * @throws QUI\Lock\Exception
     */
    protected static function getStash($name)
    {
        try {
            return QUI\Cache\Manager::getStash($name);
        } catch (\Exception $Exception) {
            throw new QUI\Lock\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.cache.manager.not.exist'
                ),
                404
            );
        }
    }

    /**
     * Return the data from the cache
     *
     * @param string $name
     * @return mixed|null
     * @throws QUI\Lock\Exception
     */
    protected static function getStashData($name)
    {
        $Item   = self::getStash($name);
        $data   = $Item->get();
        $isMiss = $Item->isMiss();

        if ($isMiss) {
            throw new QUI\Lock\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.cache.manager.not.exist'
                ),
                404
            );
        }

        return $data;
    }
}
