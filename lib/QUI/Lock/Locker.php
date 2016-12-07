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
     */
    public static function lock(Package $Package, $key)
    {
        file_put_contents(
            self::getLockFilePath($Package, $key),
            QUI::getUserBySession()->getId()
        );
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

        $file = self::getLockFilePath($Package, $key);

        if ($User->isSU() || QUI::getUsers()->isSystemUser($User)) {
            unlink($file);
            return;
        }

        if ($locked === $User->getId()) {
            unlink($file);
        }
    }

    /**
     * Check if a item or an object is locked
     *
     * @param Package $Package
     * @param string $key
     * @return bool|string
     */
    public static function isLocked(Package $Package, $key)
    {
        if (!file_exists(self::getLockFilePath($Package, $key))) {
            return false;
        }

        return file_get_contents(self::getLockFilePath($Package, $key));
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
        return time() - filemtime(self::getLockFilePath($Package, $key));
    }

    /**
     * Return the key for the lock item
     *
     * @param Package $Package
     * @param string $key
     * @return string
     *
     * @throws QUI\Exception
     */
    protected static function getLockFilePath(Package $Package, $key)
    {
        if (!is_string($key) || empty($key)) {
            throw new QUI\Exception('Lock::lock() need a string as key');
        }

        $package = str_replace('/', '_', $Package->getName());

        return VAR_DIR . 'lock/' . $package . '_' . $key;
    }
}
