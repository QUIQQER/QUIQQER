<?php

/**
 * Healthcheck
 *
 * @return String
 */

QUI::getAjax()->registerFunction(
    'ajax_system_health_system',
    static function (): array {
        return QUI\System\Checks\Health::systemCheck();
    },
    false,
    'Permission::checkSU'
);
