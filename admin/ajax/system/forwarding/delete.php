<?php

/**
 * Delete a forwarding
 *
 * @param string $from
 */

QUI::$Ajax->registerFunction(
    'ajax_system_forwarding_delete',
    static function ($from): void {
        QUI\System\Forwarding::delete(
            json_decode($from, true)
        );
    },
    ['from'],
    'Permission::checkAdminUser'
);
