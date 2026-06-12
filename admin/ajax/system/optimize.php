<?php

/**
 * System Tabellen optimieren
 */

QUI::$Ajax->registerFunction(
    'ajax_system_optimize',
    static function (): void {
        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();

        if (!($Platform instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform)) {
            return;
        }

        foreach (QUI::getSchemaManager()->listTableNames() as $table) {
            $Connection->executeStatement(
                'OPTIMIZE TABLE ' . $Platform->quoteSingleIdentifier($table)
            );
        }
    },
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
