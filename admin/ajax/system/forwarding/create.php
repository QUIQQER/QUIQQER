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
    function ($from, $target, $code) {
        QUI\System\Forwarding::create($from, $target, $code);
    },
    array('from', 'target', 'code'),
    'Permission::checkAdminUser'
);
