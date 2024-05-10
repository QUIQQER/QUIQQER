<?php

/**
 * Return te CSP Settings
 */

QUI::$Ajax->registerFunction(
    'ajax_system_settings_getCSP',
    static fn(): array => QUI\System\CSP::getInstance()->getCSPDirectiveConfig(),
    false,
    ['Permission::checkAdminUser']
);
