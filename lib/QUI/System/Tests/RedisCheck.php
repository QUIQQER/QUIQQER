<?php

/**
 * This class contains \QUI\System\Tests\RedisCheck
 */

namespace QUI\System\Tests;

use QUI;

/**
 * Redis Server Test
 *
 * @package quiqqer/quiqqer
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
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
            'title'       => 'Redis',
            'description' => ''
        ]);

        $this->isRequired = self::TEST_IS_OPTIONAL;
    }

    /**
     * Check, if redis is available
     *
     * @return int self::STATUS_OK|self::STATUS_ERROR
     */
    public function execute()
    {
        return self::checkServer();
    }

    /**
     * @param string $server - optional
     * @param bool $message - error codes as message or flag?
     * @return int
     */
    static function checkServer($server = '', $message = false)
    {
        if (!\class_exists('RedisArray') || !\class_exists('Redis')) {
            if ($message) {
                return QUI::getLocale()->get('quiqqer/quiqqer', 'message.redis.classes.missing');
            }

            return self::STATUS_ERROR;
        }

        if (empty($server)) {
            if ($message) {
                return QUI::getLocale()->get('quiqqer/quiqqer', 'message.redis.ok');
            }

            return self::STATUS_OK;
        }

        try {
            $Redis  = new \Redis();
            $server = \parse_url($server);

            if (!isset($server['port'])) {
                $Redis->connect($server['path']);
            } else {
                $Redis->connect($server['path'], $server['port']);
            }

            $Redis->ping();

            if ($message) {
                return QUI::getLocale()->get('quiqqer/quiqqer', 'message.redis.connection.ok');
            }

            return self::STATUS_OK;
        } catch (\Exception $Exception) {
            if ($message) {
                return $Exception->getMessage();
            }

            return self::STATUS_ERROR;
        }
    }
}
