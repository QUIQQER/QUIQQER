<?php

/**
 * Return te CSP Settings
 */
QUI::$Ajax->registerFunction(
    'ajax_system_settings_getAllowedCSP',
    function () {
        return QUI\System\CSP::getInstance()->getAllowedCSPList();
    },
    false,
    ['Permission::checkAdminUser']
);
