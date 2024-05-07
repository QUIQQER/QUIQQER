<?php

/**
 * Return te CSP Settings
 */

QUI::$Ajax->registerFunction(
    'ajax_system_settings_getCSP',
    fn() => QUI\System\CSP::getInstance()->getCSPDirectiveConfig(),
    false,
    ['Permission::checkAdminUser']
);
