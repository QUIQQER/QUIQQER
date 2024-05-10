<?php

/**
 * Return the forwarding list
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_system_forwarding_get',
    static function ($forwarding) {
        return QUI\System\Forwarding::getList()
            ->find(static function ($value, $key) use ($forwarding) {
                return $key == $forwarding;
            });
    },
    ['forwarding'],
    'Permission::checkAdminUser'
);
