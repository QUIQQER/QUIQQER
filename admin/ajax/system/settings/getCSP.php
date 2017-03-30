<?php

/**
 * Return te CSP Settings
 */
QUI::$Ajax->registerFunction(
    'ajax_system_settings_getCSP',
    function () {
        return QUI::conf('securityHeaders_csp');
    },
    false,
    array('Permission::checkAdminUser')
);
