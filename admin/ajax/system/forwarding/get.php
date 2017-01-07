<?php

/**
 * Return the forwarding list
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_system_forwarding_get',
    function ($forwarding) {
        return QUI\System\Forwarding::getList()
            ->find(function ($value, $key) use ($forwarding) {
                return $key == $forwarding;
            });
    },
    array('forwarding'),
    'Permission::checkAdminUser'
);
