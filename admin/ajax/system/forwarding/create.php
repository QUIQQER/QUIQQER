<?php

/**
 * Create a new forwarding
 *
 * @param string $from
 * @param string $target
 * @param int|string $code
 */

QUI::$Ajax->registerFunction(
    'ajax_system_forwarding_create',
    static function ($from, $target, $code): void {
        QUI\System\Forwarding::create($from, $target, $code);
    },
    ['from', 'target', 'code'],
    'Permission::checkAdminUser'
);
