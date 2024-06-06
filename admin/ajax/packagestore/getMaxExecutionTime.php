<?php

/**
 * Get PHP max_execution_time setting
 *
 * @return int
 */

QUI::$Ajax->registerFunction(
    'ajax_packagestore_getMaxExecutionTime',
    static function (): int {
        return (int)ini_get('max_execution_time');
    },
    [],
    'Permission::checkAdminUser'
);
