<?php

/**
 * This file contains \QUI\Cache\Manager
 */

namespace QUI\Cache;

use QUI;
use Stash;

/**
 * Cache Manager
 * Easy access fot different cache types
 *
 * @author www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Manager
{
    /**
     * Global clearing flag
     * better control about the cache clearing process
     * (for process performance optimization)
     *
     * @var bool
     */
    public static $noClearing = false;

    /**
     * Cache Manager Configs
     *
     * @var \QUI\Config
     */
    public static $Config = null;

    /**
     * Stash Object
     *
     * @var Stash\Pool
     */
    public static $Stash = null;

    /**
     * File system stach object
     *
     * @var Stash\Pool
     */
    public static $FileSystemStash = null;

    /**
     * the stash multihandler
     *
     * @var Stash\Interfaces\DriverInterface
     */
    public static $Handler = null;

    /**
     * all stash cache objects
     *
     * @var array
     */
    public static $handlers = null;

    /**
     * @var array
     */
    protected static $drivers = [];

    /**
     * @var null
     */
    protected static $currentDriver = null;

    /**
     * Cache Settings
     *
     * @return QUI\Config
     */
    public static function getConfig()
    {
        if (!self::$Config) {
            try {
                self::$Config = QUI::getConfig('etc/cache.ini.php');
            } catch (QUI\Exception $Exception) {
                \file_put_contents(CMS_DIR.'etc/cache.ini.php', '');

                self::$Config = QUI::getConfig('etc/cache.ini.php');
            }
        }

        return self::$Config;
    }

    /**
     * Create the Stash Cache Handler
     *
     * @param string $key - (optional) cache name, cache key
     *
     * @return Stash\Interfaces\ItemInterface
     * @throws \QUI\Exception|\Exception
     */
    public static function getStash($key = '')
    {
        // pfad erstellen falls nicht erstellt ist
        if (!\is_dir(VAR_DIR.'cache/stack/')) {
            QUI\Utils\System\File::mkdir(VAR_DIR.'cache/stack/');
        }

        if (!\is_string($key)) {
            throw new QUI\Exception('Cache: No String given', 405, [
                'key' => $key
            ]);
        }

        if (!empty($key)) {
            $key = \md5(__FILE__).'/qui/'.$key;
        }

        if (empty($key)) {
            $key = \md5(__FILE__).'/qui/';
        }

        $key = QUI\Utils\StringHelper::replaceDblSlashes($key);

        if (self::$Stash !== null) {
            try {
                return self::$Stash->getItem($key);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException(
                    $Exception,
                    QUI\System\Log::LEVEL_ERROR,
                    [
                        'key' => $key
                    ]
                );

                throw $Exception;
            }
        }

        if (self::$handlers === null) {
            self::$handlers = self::getHandlers();
        }

        $Handler = new Stash\Driver\Composite([
            'drivers' => self::$handlers
        ]);

        $Stash       = new Stash\Pool($Handler);
        self::$Stash = $Stash;

        return self::$Stash->getItem($key);
    }

    /**
     * @return array
     */
    public static function getHandlers()
    {
        $Config = self::getConfig();

        $handlers     = [];
        $confHandlers = $Config->get('handlers');

        if (empty($confHandlers)) {
            $confHandlers['filesystem'] = 1;
        }

        foreach ($confHandlers as $confHandler => $bool) {
            if (!$bool) {
                continue;
            }

            switch ($confHandler) {
                case 'apc':
                    try {
                        \array_unshift($handlers, self::getDriver([], 'apc'));
                    } catch (Stash\Exception\RuntimeException $Exception) {
                    }

                    break;

                case 'filesystem':
                    try {
                        $handlers[] = self::getDriver([], 'filesystem');
                    } catch (Stash\Exception\RuntimeException $Exception) {
                    }

                    break;

                case 'redis':
                    try {
                        $handlers[] = self::getDriver([], 'redis');
                    } catch (Stash\Exception\RuntimeException $Exception) {
                    }

                    break;

                case 'memcache':
                    try {
                        \array_unshift($handlers, self::getDriver([], 'memcache'));
                    } catch (Stash\Exception\RuntimeException $Exception) {
                    }

                    break;
            }
        }

        // all handlers false, so we use filesystem
        if (empty($handlers)) {
            $handlers[] = self::getDriver();
        }

        return $handlers;
    }

    /**
     * Return the current cache driver.
     *
     * @param array $options - optional
     * @param string|boolean $driver - optional
     *
     * @return Stash\Driver\AbstractDriver
     */
    public static function getDriver($options = [], $driver = false)
    {
        if ($driver === false) {
            $driver = self::getCurrentDriver();
        }

        $Config = self::getConfig();

        switch ($driver) {
            case 'apc':
                $conf   = $Config->get('apc');
                $params = [
                    'namespace' => 'pcsg'
                ];

                if (isset($conf['namespace'])) {
                    $params['namespace'] = $conf['namespace'];
                }

                if (isset($options['namespace'])) {
                    $params['namespace'] = $options['namespace'];
                }

                if (isset($conf['ttl'])) {
                    $params['ttl'] = $conf['ttl'];
                }

                try {
                    return new Stash\Driver\Apc($params);
                } catch (Stash\Exception\RuntimeException $Exception) {
                }

                break;

            case 'redis':
                $conf = $Config->get('general', 'redis');
                $conf = \explode(',', $conf);

                $servers = [];

                if (\is_array($conf) && !empty($conf[0])) {
                    foreach ($conf as $server) {
                        $servers[] = \explode(':', $server);
                    }
                }

                // check if empty
                if (empty($servers)) {
                    $servers[] = ['localhost'];
                }

                foreach ($servers as $key => $params) {
                    if (!isset($params[$key][0])) {
                        continue;
                    }

                    if (empty($params[$key][0][0])) {
                        $params[$key][0][$key] = 'localhost';
                    }
                }

                try {
                    return new QuiqqerRedisDriver([
                        'servers' => $servers
                    ]);
                } catch (Stash\Exception\RuntimeException $Exception) {
                }

                break;

            case 'memcache':
                // defaults
                $defaults = [
                    'prefix_key'           => 'pcsg',
                    'libketama_compatible' => true,
                    'cache_lookups'        => true,
                    'serializer'           => 'json'
                ];

                // servers
                $serverCount = $Config->get('memcache', 'servers');
                $servers     = [];

                for ($i = 1; $i <= $serverCount; $i++) {
                    $section = 'memcache'.$i;

                    $servers[] = [
                        $Config->get($section, 'host'),
                        $Config->get($section, 'port'),
                        $Config->get($section, 'weight')
                    ];
                }

                $defaults['servers'] = $servers;

                $conf = $Config->get('memcache');

                if (isset($conf['prefix_key']) && !empty($conf['prefix_key'])) {
                    $defaults['prefix_key'] = $conf['prefix_key'];
                }

                if (isset($conf['libketama_compatible']) && !empty($conf['libketama_compatible'])) {
                    $defaults['libketama_compatible'] = $conf['libketama_compatible'];
                }

                if (isset($conf['cache_lookups']) && !empty($conf['cache_lookups'])) {
                    $defaults['cache_lookups'] = $conf['cache_lookups'];
                }

                if (isset($conf['serializer']) && !empty($conf['serializer'])) {
                    $defaults['serializer'] = $conf['serializer'];
                }

                if (isset($options['prefix_key']) && !empty($options['prefix_key'])) {
                    $defaults['prefix_key'] = $options['prefix_key'];
                }

                try {
                    return new Stash\Driver\Memcache($defaults);
                } catch (Stash\Exception\RuntimeException $Exception) {
                }

                break;
        }

        // default = filesystem
        $conf   = $Config->get('filesystem');
        $params = [
            'path' => VAR_DIR.'cache/stack/'
        ];

        if (!empty($conf['path']) && \is_dir($conf['path'])) {
            $params['path'] = $conf['path'];
        }

        if (!empty($options['path']) && \is_dir($options['path'])) {
            $params['path'] = $options['path'];
        }

        return new QuiqqerFileDriver($params);
    }

    /**
     * @return int|string
     */
    protected static function getCurrentDriver()
    {
        if (self::$currentDriver === null) {
            return self::$currentDriver;
        }

        $Config   = self::getConfig();
        $handlers = $Config->get('handlers');

        if (empty($handlers)) {
            self::$currentDriver = 'filesystem';

            return 'filesystem';
        }

        foreach ($handlers as $handler => $bool) {
            if ($bool) {
                self::$currentDriver = $handler;

                return $handler;
            }
        }

        self::$currentDriver = 'filesystem';

        return 'filesystem';
    }

    /**
     * Returns explicitly the file system cache
     *
     * @return false|Stash\Pool
     * @deprecated use getDriver
     */
    public static function getFileSystemCache()
    {
        if (!\is_null(self::$FileSystemStash)) {
            return self::$FileSystemStash;
        }

        $Config = self::getConfig();
        $conf   = $Config->get('filesystem');
        $params = [
            'path' => VAR_DIR.'cache/stack/'
        ];

        if (!empty($conf['path']) && \is_dir($conf['path'])) {
            $params['path'] = $conf['path'];
        }

        try {
            $handler = new QuiqqerFileDriver($params);
        } catch (Stash\Exception\RuntimeException $Exception) {
            return false;
        }

        $Handler = new Stash\Driver\Composite([
            'drivers' => [$handler]
        ]);

        self::$FileSystemStash = new Stash\Pool($Handler);

        return self::$FileSystemStash;
    }

    /**
     * Returns the Stash\Driver\Composite or the Stash\Driver
     *
     * @param string|boolean $type = optional: bestimmten Cache Handler bekommen
     *
     * @return Stash\Interfaces\DriverInterface|boolean
     */
    public static function getHandler($type = false)
    {
        if ($type != false) {
            $handlers = self::$handlers;

            foreach ($handlers as $Handler) {
                if (\get_class($Handler) == $type) {
                    return $Handler;
                }
            }

            return false;
        }

        if (self::$Handler === null) {
            return false;
        }

        return self::$Handler;
    }

    /**
     * Stores data into the cache.
     *
     * Putting something in the cache does not guarantee that it's actually stored.
     * This happens because of the cache's volatility.
     * That means that data can get lost or removed from cache at any time.
     *
     * @param string $name
     * @param mixed $data
     * @param \DateTimeInterface|int|\DateInterval|null $time Seconds, Interval or exact date at/after which the cache item expires.
     *                                                         If $time is null, the cache will try to use the default value,
     *                                                         if no default value is set, the maximum possible time for the used implementation will be used.
     */
    public static function set($name, $data, $time = null)
    {
        try {
            $Stash = self::getStash($name);
            $Stash->set($data);

            if ($time instanceof \DateTimeInterface) {
                $Stash->expiresAt($time);
            }

            if (\is_numeric($time) || $time instanceof \DateInterval) {
                $Stash->expiresAfter($time);
            }

            $Stash->save();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
            QUI\System\Log::addError($Exception->getMessage());
        }
    }

    /**
     * Returns cached data.
     * Throws an exception if no data is present in the cache for the given key.
     *
     * @param string $name
     *
     * @return string|array|object|boolean
     *
     * @throws QUI\Cache\Exception
     */
    public static function get($name)
    {
        if (self::getConfig()->get('general', 'nocache')) {
            throw new QUI\Cache\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.cache.manager.not.exist'
                ),
                404
            );
        }

        try {
            $Item   = self::getStash($name);
            $data   = $Item->get();
            $isMiss = $Item->isMiss();
        } catch (\Exception $Exception) {
            throw new QUI\Cache\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.cache.manager.not.exist'
                ),
                404
            );
        }

        /**
         * @todo
         *
         * Do not treat cache misses as missing cache items OR throw other
         * Exception.
         */
        if ($isMiss) {
            //
            // auskommentiert by hen, da diese vorgehensweise nicht optimal ist und server zugespamt werden
            //
//            QUI\System\Log::addDebug(
//                'Cache item "'.$name.'" is a miss. This means the item could not be reliably'
//                .' retrieved from the cache. This does NOT necessarily mean that the item is actually not cached.'
//                .' But QUIQQER currently handles all cache misses as a non-existing cache entry.'
//                .' This is behaviour will be fixed in the future. This message is for information'
//                .' purposes only.'
//            );

            throw new QUI\Cache\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.cache.manager.not.exist'
                ),
                404
            );
        }

        return $data;
    }

    // region clearing

    /**
     * Clears the settings cache
     * - /settings/
     */
    public static function clearSettingsCache()
    {
        self::clear('settings');

        try {
            QUI::getEvents()->fireEvent('clearSettingsCache');
        } catch (\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }
    }

    /**
     * Clears the complete quiqqer cache
     * - /quiqqer/
     */
    public static function clearCompleteQuiqqerCache()
    {
        self::clear('quiqqer');

        try {
            QUI::getEvents()->fireEvent('clearCompleteQuiqqerCache');
        } catch (\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }


        try {
            QUI\Utils\System\File::unlink(VAR_DIR.'cache/compile');
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }
    }

    /**
     * Clears the projects cache
     * - /quiqqer/projects/
     */
    public static function clearProjectsCache()
    {
        self::clear('quiqqer/projects/');

        try {
            QUI::getEvents()->fireEvent('clearProjectsCache');
        } catch (\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }
    }

    /**
     * Clears the project cache
     * - /quiqqer/projects/projectName
     *
     * @param string $projectName - name of the project
     */
    public static function clearProjectCache($projectName)
    {
        self::clear('quiqqer/projects/'.$projectName);

        try {
            QUI::getEvents()->fireEvent('clearProjectCache', [$projectName]);
        } catch (\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }
    }

    /**
     * Clears the groups cache
     * - /quiqqer/groups/
     */
    public static function clearGroupsCache()
    {
        self::clear('quiqqer/groups/');

        try {
            QUI::getEvents()->fireEvent('clearGroupsCache');
        } catch (\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }
    }

    /**
     * Clears the users cache
     * - /quiqqer/users/
     */
    public static function clearUsersCache()
    {
        self::clear('quiqqer/users/');

        try {
            QUI::getEvents()->fireEvent('clearUsersCache');
        } catch (\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }
    }

    /**
     * Clears the permissions cache
     * - /quiqqer/permissions/
     */
    public static function clearPermissionsCache()
    {
        self::clear('quiqqer/permissions/');

        try {
            QUI::getEvents()->fireEvent('clearPermissionsCache');
        } catch (\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }
    }

    /**
     * Clears the packages cache
     * - /quiqqer/packages/
     */
    public static function clearPackagesCache()
    {
        self::clear('quiqqer/packages/');

        try {
            QUI::getEvents()->fireEvent('clearPackagesCache');
        } catch (\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }
    }

    /**
     * Clears the package cache
     * - /quiqqer/package/packageName
     *
     * @param string $packageName - Name of the package
     */
    public static function clearPackageCache($packageName)
    {
        self::clear('quiqqer/package/'.$packageName);

        try {
            QUI::getEvents()->fireEvent('clearPackageCache', [$packageName]);
        } catch (\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }
    }

    /**
     * Clears all or only a given entry from the cache.
     *
     * @param string|boolean $key - optional; if no key is given the whole cache is cleared
     */
    public static function clear($key = "")
    {
        if (self::$noClearing) {
            return;
        }

        try {
            self::getStash($key)->clear();

            QUI::getEvents()->fireEvent('cacheClear', [$key]);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * The purge function removes stale data from the cache backends while leaving current data intact.
     * Depending on the size of the cache and the specific drivers in use this can take some time,
     * so it is best called as part of a separate maintenance task or as part of a cron job.
     */
    public static function purge()
    {
        self::$Stash->purge();

        try {
            QUI::getEvents()->fireEvent('cachePurge');
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * Clears the entire quiqqer cache.
     */
    public static function clearAll()
    {
        if (self::$noClearing) {
            return;
        }

        try {
            QUI::getTemp()->moveToTemp(VAR_DIR.'cache/');

            self::getStash('')->clear();

            QUI::getEvents()->fireEvent('cacheClearAll');
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    //endregion

    /**
     * Returns the size of the /var/cache/ folder in bytes.
     * By default the value is returned from cache.
     * If there is no value in cache, null is returned, unless you set the force parameter to true.
     * Only if you really need to get a freshly calculated result, you may set the force parameter to true.
     * When using the force parameter expect timeouts since the calculation could take a lot of time.
     *
     * @param boolean $force - Force a calculation of the cache folder's size. Values aren't returned from cache. Expect timeouts.
     *
     * @return int
     */
    public static function getCacheFolderSize($force = false)
    {
        $cacheFolder = VAR_DIR."cache/";

        return QUI\Utils\System\Folder::getFolderSize($cacheFolder, $force);
    }

    /**
     * Returns the timestamp when the cache folder's size was stored in cache.
     * Returns null if there is no data in the cache.
     *
     * @return int|null
     */
    public static function getCacheFolderSizeTimestamp()
    {
        $cacheFolder = VAR_DIR."cache/";

        return QUI\Utils\System\Folder::getFolderSizeTimestamp($cacheFolder);
    }

    //region longtime

    /**
     * Clears all or only a given entry from the longtime cache.
     *
     * @param string|boolean $key - optional; if no key is given the whole cache is cleared
     */
    public static function longTimeCacheClear($key = "")
    {
        if (self::$noClearing) {
            return;
        }

        try {
            LongTermCache::clear($key);

            QUI::getEvents()->fireEvent('longTimeCacheClear', [$key]);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * clear the complete quiqqer long time cache
     */
    public static function longTimeCacheClearCompleteQuiqqer()
    {
        self::longTimeCacheClear('quiqqer');

        try {
            QUI::getEvents()->fireEvent('longTimeCacheClearCompleteQuiqqerCache');
        } catch (\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }


        try {
            QUI\Utils\System\File::unlink(LongTermCache::fileSystemPath());
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }
    }

    //endregion
}
