<?php

/**
 * Return te CSP Settings
 */

QUI::$Ajax->registerFunction(
    'ajax_system_settings_getAllowedCSP',
    static fn(): array => QUI\System\CSP::getInstance()->getAllowedCSPList(),
    false,
    ['Permission::checkAdminUser']
);
