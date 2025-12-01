<?php

QUI::$Ajax->registerFunction(
    'ajax_system_systemInfo',
    static function (): array {
        $connection = QUI::getDataBaseConnection();
        $dbVersion = $connection->getServerVersion();
        $dbType = $connection->getParams()['driver'] ?? null;

        return [
            'version' => QUI::getPackageManager()->getVersion(),
            'hash' => QUI::getPackageManager()->getHash(),
            'php_version' => phpversion(),
            'database' => [
                'type' => $dbType,
                'version' => $dbVersion
            ]
        ];
    },
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
