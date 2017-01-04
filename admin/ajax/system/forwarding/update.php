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
    function ($from, $target, $code) {
        QUI\System\Forwarding::update($from, $target, $code);
    },
    array('from', 'target', 'code'),
    'Permission::checkUser'
);
