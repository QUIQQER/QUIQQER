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
                file_put_contents(CMS_DIR.'etc/cache.ini.php', '');

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
        if (!is_dir(VAR_DIR.'cache/stack/')) {
            QUI\Utils\System\File::mkdir(VAR_DIR.'cache/stack/');
        }

        if (!is_string($key)) {
            throw new QUI\Exception('Cache: No String given', 405, array(
                'key' => $key
            ));
        }

        if (!empty($key)) {
            $key = md5(__FILE__).'/qui/'.$key;
        }

        if (empty($key)) {
            $key = md5(__FILE__).'/qui/';
        }

        $key = QUI\Utils\StringHelper::replaceDblSlashes($key);

        if (self::$Stash !== null) {
            try {
                return self::$Stash->getItem($key);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException(
                    $Exception,
                    QUI\System\Log::LEVEL_ERROR,
                    array(
                        'key' => $key
                    )
                );

                throw $Exception;
            }
        }


        $Config = self::getConfig();

        $handlers     = array();
        $confhandlers = $Config->get('handlers');

        if (empty($confhandlers)) {
            $confhandlers['filesystem'] = 1;
        }

        foreach ($confhandlers as $confhandler => $bool) {
            if (!$bool) {
                continue;
            }

            $params = array();

            switch ($confhandler) {
                case 'apc':
                    $conf   = $Config->get('apc');
                    $params = array(
                        'namespace' => 'pcsg'
                    );

                    if (isset($conf['namespace'])) {
                        $params['namespace'] = $conf['namespace'];
                    }

                    if (isset($conf['ttl'])) {
                        $params['ttl'] = $conf['ttl'];
                    }

                    try {
                        array_unshift($handlers, new Stash\Driver\Apc($params));
                    } catch (Stash\Exception\RuntimeException $Exception) {
                    }

                    break;

                case 'filesystem':
                    $conf   = $Config->get('filesystem');
                    $params = array(
                        'path' => VAR_DIR.'cache/stack/'
                    );

                    if (!empty($conf['path']) && is_dir($conf['path'])) {
                        $params['path'] = $conf['path'];
                    }

                    try {
                        $handlers[] = new Stash\Driver\FileSystem($params);
                    } catch (Stash\Exception\RuntimeException $Exception) {
                    }

                    break;

                case 'sqlite':
                    $conf   = $Config->get('sqlite');
                    $params = array(
                        'path' => VAR_DIR.'cache/stack/'
                    );

                    if (!empty($conf['path']) && is_dir($conf['path'])) {
                        $params['path'] = $conf['path'];
                    }

                    try {
                        $handlers[] = new Stash\Driver\Sqlite($params);
                    } catch (Stash\Exception\RuntimeException $Exception) {
                    }

                    break;

                case 'memcache':
                    // defaults
                    $options = array(
                        'prefix_key'           => 'pcsg',
                        'libketama_compatible' => true,
                        'cache_lookups'        => true,
                        'serializer'           => 'json'
                    );

                    // servers
                    $scount  = $Config->get('memcache', 'servers');
                    $servers = array();

                    for ($i = 1; $i <= $scount; $i++) {
                        $section = 'memcache'.$i;

                        $servers[] = array(
                            $Config->get($section, 'host'),
                            $Config->get($section, 'port'),
                            $Config->get($section, 'weight')
                        );
                    }

                    $options['servers'] = $servers;

                    $conf = $Config->get('memcache');

                    if (isset($conf['prefix_key'])
                        && !empty($conf['prefix_key'])
                    ) {
                        $options['prefix_key'] = $conf['prefix_key'];
                    }

                    if (isset($conf['libketama_compatible'])
                        && !empty($conf['libketama_compatible'])
                    ) {
                        $options['libketama_compatible']
                            = $conf['libketama_compatible'];
                    }

                    if (isset($conf['cache_lookups'])
                        && !empty($conf['cache_lookups'])
                    ) {
                        $options['cache_lookups'] = $conf['cache_lookups'];
                    }

                    if (isset($conf['serializer'])
                        && !empty($conf['serializer'])
                    ) {
                        $options['serializer'] = $conf['serializer'];
                    }

                    try {
                        array_unshift(
                            $handlers,
                            new Stash\Driver\Memcache($params)
                        );
                    } catch (Stash\Exception\RuntimeException $Exception) {
                    }

                    break;
            }
        }

        // all handlers false, so we use filesystem
        if (empty($handlers)) {
            $conf   = $Config->get('filesystem');
            $params = array('path' => VAR_DIR.'cache/stack/');

            if (!empty($conf['path']) && is_dir($conf['path'])) {
                $params['path'] = $conf['path'];
            }

            $handlers[] = new Stash\Driver\FileSystem($params);
        }

        $Handler = new Stash\Driver\Composite(array(
            'drivers' => $handlers
        ));

        $Stash = new Stash\Pool($Handler);


        self::$Stash    = $Stash;
        self::$handlers = $handlers;

        return self::$Stash->getItem($key);
    }

    /**
     * Explicitly get file system cache
     *
     * @return false|Stash\Pool
     */
    public static function getFileSystemCache()
    {
        if (!is_null(self::$FileSystemStash)) {
            return self::$FileSystemStash;
        }

        $Config = self::getConfig();
        $conf   = $Config->get('filesystem');
        $params = array(
            'path' => VAR_DIR.'cache/stack/'
        );

        if (!empty($conf['path']) && is_dir($conf['path'])) {
            $params['path'] = $conf['path'];
        }

        try {
            $handler = new Stash\Driver\FileSystem($params);
        } catch (Stash\Exception\RuntimeException $Exception) {
            return false;
        }

        $Handler = new Stash\Driver\Composite(array(
            'drivers' => [$handler]
        ));

        self::$FileSystemStash = new Stash\Pool($Handler);

        return self::$FileSystemStash;
    }

    /**
     * Gibt den Stash\Driver\Composite oder den Stash\Driver zurück
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
                if (get_class($Handler) == $type) {
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
     * Daten in den Cache setzen
     *
     * @param string $name
     * @param mixed $data
     * @param int|\DateTime|null $time -> sekunden oder datetime
     */
    public static function set($name, $data, $time = null)
    {
        $Stash = self::getStash($name);
        $Stash->set($data, $time);
        $Stash->save();
    }

    /**
     * Daten aus dem Cache bekommen
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

        if ($isMiss) {
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

    /**
     * Cache leeren
     *
     * @param string|boolean $key - optional, falls kein Key übergeben wird, wird komplett geleert
     */
    public static function clear($key = "")
    {
        if (self::$noClearing) {
            return;
        }

        self::getStash($key)->clear();

        QUI::getEvents()->fireEvent('cacheClear', array($key));
    }

    /**
     * The purge function removes stale data from the cache backends while leaving current data intact.
     * Depending on the size of the cache and the specific drivers in use this can take some time,
     * so it is best called as part of a separate maintenance task or as part of a cron job.
     */
    public static function purge()
    {
        self::$Stash->purge();

        QUI::getEvents()->fireEvent('cachePurge');
    }

    /**
     * Löscht den kompletten CMS Cache
     */
    public static function clearAll()
    {
        if (self::$noClearing) {
            return;
        }

        QUI\Utils\System\File::unlink(VAR_DIR.'cache/');

        self::getStash('')->clear();

        QUI::getEvents()->fireEvent('cacheClearAll');
    }
}
