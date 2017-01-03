<?php

/**
 * Return the forwarding list
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_system_forwardings_getList',
    function () {
        return QUI\System\Forwarding::getList()->toArray();
    },
    false,
    'Permission::checkAdminUser'
);
