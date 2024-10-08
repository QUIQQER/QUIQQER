<?php

/**
 * Return the forwarding list
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_system_forwarding_getList',
    static function () {
        return QUI\System\Forwarding::getList()->toArray();
    },
    false,
    'Permission::checkAdminUser'
);
