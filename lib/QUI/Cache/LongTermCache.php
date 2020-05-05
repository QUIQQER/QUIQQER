<?php

namespace QUI\Cache;

use QUI;
use Stash;

/**
 * Class LongTermCache
 *
 * @package QUI\Cache
 */
class LongTermCache
{
    /**
     * @var null
     */
    protected static $Config = null;

    /**
     * @var null
     */
    protected static $Pool = null;

    /**
     * @var null
     */
    protected static $Driver = null;

    //region API

    /**
     * Returns the cached data
     *
     * @param string $name
     * @return string|array|object|boolean
     *
     * @throws QUI\Cache\Exception
     */
    public static function get($name)
    {
        $key = self::generateStorageKey($name);

        try {
            $Pool   = self::getPool();
            $Item   = $Pool->getItem($key);
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
     * @param $name
     * @param $data
     */
    public static function set($name, $data)
    {
        $key = self::generateStorageKey($name);

        try {
            $Pool = self::getPool();
            $Item = $Pool->getItem($key);
            $Item->set($data);
            $Item->save();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
            QUI\System\Log::addError($Exception->getMessage());
        }
    }

    /**
     * Clears the cache
     *
     * @param string $name
     */
    public static function clear($name = '')
    {
        $key = self::generateStorageKey($name);

        try {
            $Pool = self::getPool();
            $Item = $Pool->getItem($key);
            $Item->clear();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    // endregion

    /**
     * execute the long time cache setup
     */
    public static function setup()
    {
        $Config = self::getConfig();

        if ($Config->get('longtime', 'type') === 'filesystem' && !\is_dir(self::fileSystemPath())) {
            QUI\Utils\System\File::mkdir(self::fileSystemPath());
        }
    }

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
     * @return Stash\Pool
     */
    protected static function getPool()
    {
        if (self::$Pool === null) {
            self::$Pool = new Stash\Pool(self::getDriver());
        }

        return self::$Pool;
    }

    /**
     * Return the current driver
     */
    protected static function getDriver()
    {
        if (self::$Driver !== null) {
            return self::$Driver;
        }

        $Config = self::getConfig();
        $type   = $Config->get('longtime', 'type');

        switch ($type) {
            case 'redis':
                $conf = $Config->get('longtime', 'redis_server');
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
                    self::$Driver = new QuiqqerRedisDriver([
                        'servers' => $servers
                    ]);
                } catch (Stash\Exception\RuntimeException $Exception) {
                }

                break;

            case 'mongo':
                if (!class_exists('\MongoDB\Client')) {
                    QUI\System\Log::write(
                        'Mongo DB Driver not found. 
                        Please install MongoDB\Client (php MongoDB extension) or don\'t use MongoDB as long term cache',
                        QUI\System\Log::LEVEL_ALERT
                    );
                } else {
                    $conf       = $Config->get('longtime');
                    $host       = 'localhost';
                    $database   = 'local';
                    $collection = \md5(__FILE__);

                    // database server
                    if (!empty($conf['mongo_host'])) {
                        $host = $conf['mongo_host'];
                    }

                    if (!empty($conf['mongo_database'])) {
                        $database = $conf['mongo_database'];
                    }

                    if (!empty($conf['mongo_collection'])) {
                        $collection = $conf['mongo_collection'];
                    }

                    if (\strpos($host, 'mongodb://') === false) {
                        $host = 'mongodb://'.$host;
                    }

                    if (!empty($conf['mongo_username']) && !empty($conf['mongo_password'])) {
                        $Client = new \MongoDB\Client($host, [
                            "username" => $conf['mongo_username'],
                            "password" => $conf['mongo_password']
                        ]);
                    } else {
                        $Client = new \MongoDB\Client($host);
                    }

                    self::$Driver = new QuiqqerMongoDriver([
                        'mongo'      => $Client,
                        'database'   => $database,
                        'collection' => $collection
                    ]);
                }

                break;
        }

        if (self::$Driver === null) {
            $conf   = $Config->get('longtime');
            $params = [
                'path' => self::fileSystemPath()
            ];

            if (!empty($conf['file_path']) && \is_dir($conf['file_path'])) {
                $params['path'] = $conf['file_path'];
            }

            self::$Driver = new QuiqqerFileDriver($params);
        }

        return self::$Driver;
    }

    /**
     * @param $name
     * @return string
     */
    protected static function generateStorageKey($name)
    {
        return \md5(__FILE__).'/quiqqer-lt/'.$name;
    }

    /**
     * @return string
     */
    public static function fileSystemPath()
    {
        return VAR_DIR.'cache/longtime/';
    }
}
