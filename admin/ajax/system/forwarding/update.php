<?php

/**
 * Update a new forwarding
 *
 * @param string $from
 * @param string $target
 * @param int|string $code
 */

QUI::$Ajax->registerFunction(
    'ajax_system_forwarding_update',
    static function ($from, $target, $code): void {
        QUI\System\Forwarding::update($from, $target, $code);
    },
    ['from', 'target', 'code'],
    'Permission::checkAdminUser'
);
