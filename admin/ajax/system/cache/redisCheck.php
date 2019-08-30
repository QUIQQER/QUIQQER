<?php

/**
 * redis check
 */

use QUI\System\Tests\RedisCheck;

QUI::$Ajax->registerFunction(
    'ajax_system_cache_redisCheck',
    function ($server) {
        $status  = RedisCheck::checkServer($server);
        $message = RedisCheck::checkServer($server, true);

        return [
            'status'  => $status,
            'message' => $message
        ];
    },
    ['server'],
    'Permission::checkSU'
);
