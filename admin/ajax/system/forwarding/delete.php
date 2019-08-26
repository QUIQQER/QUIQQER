<?php

/**
 * Delete a forwarding
 *
 * @param string $from
 */
QUI::$Ajax->registerFunction(
    'ajax_system_forwarding_delete',
    function ($from) {
        QUI\System\Forwarding::delete(
            \json_decode($from, true)
        );
    },
    ['from'],
    'Permission::checkAdminUser'
);
