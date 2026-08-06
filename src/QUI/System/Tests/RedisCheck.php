<?php

/**
 * This class contains \QUI\System\Tests\RedisCheck
 */

namespace QUI\System\Tests;

use Exception;
use QUI;
use Redis;

use function class_exists;
use function parse_url;

/**
 * Redis Server Test
 */
class RedisCheck extends QUI\System\Test
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setAttributes([
            'title' => 'Redis',
            'description' => ''
        ]);

        $this->isRequired = self::TEST_IS_OPTIONAL;
    }

    /**
     * Check, if redis is available
     *
     * @return int self::STATUS_OK|self::STATUS_ERROR
     */
    public function execute(): int
    {
        return self::checkServer();
    }

    /**
     * @param string $server - optional
     * @param bool $message - error codes as message or flag?
     * @return ($message is true ? int|string : int)
     */
    public static function checkServer(string $server = '', bool $message = false): int|string
    {
        if (!class_exists('RedisArray') || !class_exists('Redis')) {
            if ($message) {
                return QUI::getLocale()->get('quiqqer/core', 'message.redis.classes.missing');
            }

            return self::STATUS_ERROR;
        }

        if (empty($server)) {
            $server = 'localhost';
        }

        try {
            $Redis = new Redis();
            $serverConfig = parse_url($server);

            if ($serverConfig === false) {
                throw new Exception('Invalid Redis server address.');
            }

            $host = $serverConfig['host'] ?? $serverConfig['path'] ?? '';

            if ($host === '') {
                throw new Exception('Invalid Redis server address.');
            }

            if (isset($serverConfig['port'])) {
                $Redis->connect($host, $serverConfig['port']);
            } else {
                $Redis->connect($host);
            }

            $Redis->ping();

            if ($message) {
                return QUI::getLocale()->get('quiqqer/core', 'message.redis.connection.ok');
            }

            return self::STATUS_OK;
        } catch (Exception $Exception) {
            if ($message) {
                return $Exception->getMessage();
            }

            return self::STATUS_ERROR;
        }
    }
}
