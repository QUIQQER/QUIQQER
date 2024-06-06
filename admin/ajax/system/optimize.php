<?php

/**
 * System Tabellen optimieren
 */

QUI::$Ajax->registerFunction(
    'ajax_system_optimize',
    static function (): void {
        $Table = QUI::getDataBase()->table();
        $Table->optimize($Table->getTables());
    },
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
