<?php

/**
 * Return te CSP Settings
 */

QUI::getAjax()->registerFunction(
    'ajax_system_settings_getCSP',
    static function (): array {
        return QUI\System\CSP::getInstance()->getCSPDirectiveConfig();
    },
    false,
    ['Permission::checkAdminUser']
);
