<?php

/**
 * Return te CSP Settings
 */
QUI::$Ajax->registerFunction(
    'ajax_system_settings_getCSP',
    function () {
        return QUI\System\CSP::getInstance()->getCSPDirectiveConfig();
    },
    false,
    array('Permission::checkAdminUser')
);
