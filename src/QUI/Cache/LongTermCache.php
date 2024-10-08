<?php

namespace QUI\Cache;

use MongoDB\Client;
use QUI;
use Stash;
use Stash\Interfaces\DriverInterface;

use function explode;
use function file_put_contents;
use function is_dir;
use function md5;

/**
 * Class LongTermCache
 */
class LongTermCache
{
    protected static ?QUI\Config $Config = null;

    protected static ?Stash\Pool $Pool = null;

    protected static ?DriverInterface $Driver = null;

    protected static array $runtime = [];

    //region API

    public static function set($name, $data): void
    {
        $key = self::generateStorageKey($name);

        if (isset(self::$runtime[$key])) {
            unset(self::$runtime[$key]);
        }

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

    protected static function generateStorageKey($name): string
    {
        return md5(__FILE__) . '/quiqqer-lt/' . $name;
    }

    /**
     * @return Stash\Pool
     */
    protected static function getPool(): ?Stash\Pool
    {
        if (self::$Pool === null) {
            self::$Pool = new Stash\Pool(self::getDriver());
        }

        return self::$Pool;
    }

    // endregion

    /**
     * Return the current driver
     */
    protected static function getDriver(): ?DriverInterface
    {
        if (self::$Driver !== null) {
            return self::$Driver;
        }

        $Config = self::getConfig();
        $type = $Config->get('longtime', 'type');

        switch ($type) {
            case 'redis':
                $conf = $Config->get('longtime', 'redis_server');
                $conf = explode(',', $conf);

                $servers = [];

                if (!empty($conf[0])) {
                    foreach ($conf as $server) {
                        $servers[] = explode(':', $server);
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
                } catch (Stash\Exception\RuntimeException) {
                }

                break;

            case 'mongo':
                if (!class_exists('\MongoDB\Client')) {
                    QUI\System\Log::write(
                        'Mongo DB Driver not found. 
                        Please install MongoDB\Client (php MongoDB extension) and the mongodb/mongodb package.
                        Otherwise don\'t use MongoDB as long term cache',
                        QUI\System\Log::LEVEL_ALERT
                    );
                } else {
                    $conf = $Config->get('longtime');
                    $host = 'localhost';
                    $database = 'local';
                    $collection = 'quiqqer.longterm';

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

                    if (!str_contains($host, 'mongodb://')) {
                        $host = 'mongodb://' . $host;
                    }

                    if (!empty($conf['mongo_username']) && !empty($conf['mongo_password'])) {
                        $Client = new Client($host, [
                            "username" => $conf['mongo_username'],
                            "password" => $conf['mongo_password']
                        ]);
                    } else {
                        $Client = new Client($host);
                    }

                    self::$Driver = new QuiqqerMongoDriver([
                        'mongo' => $Client,
                        'database' => $database,
                        'collection' => $collection
                    ]);
                }

                break;
        }

        if (self::$Driver === null) {
            $conf = $Config->get('longtime');
            $params = [
                'path' => self::fileSystemPath()
            ];

            if (!empty($conf['file_path']) && is_dir($conf['file_path'])) {
                $params['path'] = $conf['file_path'];
            }

            self::$Driver = new QuiqqerFileDriver($params);
        }

        return self::$Driver;
    }

    /**
     * Cache Settings
     */
    public static function getConfig(): \QUI\Config
    {
        if (!self::$Config) {
            try {
                self::$Config = QUI::getConfig('etc/cache.ini.php');
            } catch (QUI\Exception) {
                file_put_contents(CMS_DIR . 'etc/cache.ini.php', '');

                self::$Config = QUI::getConfig('etc/cache.ini.php');
            }
        }

        return self::$Config;
    }

    /**
     * Returns the cached data
     *
     * @throws QUI\Cache\Exception
     */
    public static function get(string $name): mixed
    {
        $key = self::generateStorageKey($name);

        if (isset(self::$runtime[$key])) {
            return self::$runtime[$key];
        }

        try {
            $Pool = self::getPool();
            $Item = $Pool->getItem($key);
            $data = $Item->get();
            $isMiss = $Item->isMiss();
        } catch (\Exception) {
            throw new QUI\Cache\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.lib.cache.manager.not.exist'
                ),
                404
            );
        }

        if ($isMiss) {
            throw new QUI\Cache\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.lib.cache.manager.not.exist'
                ),
                404
            );
        }

        self::$runtime[$key] = $data;

        return $data;
    }

    public static function fileSystemPath(): string
    {
        return VAR_DIR . 'cache/longtime/';
    }

    /**
     * Clears the cache
     */
    public static function clear(string $name = ''): void
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

    /**
     * execute the long time cache setup
     */
    public static function setup(): void
    {
        $Config = self::getConfig();

        if ($Config->get('longtime', 'type') !== 'filesystem') {
            return;
        }

        if (is_dir(self::fileSystemPath())) {
            return;
        }

        QUI\Utils\System\File::mkdir(self::fileSystemPath());
    }
}
