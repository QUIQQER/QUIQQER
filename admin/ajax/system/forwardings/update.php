<?php

/**
 * Get the changelog from http://update.quiqqer.com/CHANGELOG
 *
 * @return String
 */
QUI::$Ajax->registerFunction(
    'ajax_system_forwardings_update',
    function () {
        return QUI\System\Forwarding::getList();
    },
    false,
    'Permission::checkUser'
);
