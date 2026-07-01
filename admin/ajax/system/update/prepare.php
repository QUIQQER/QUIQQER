<?php

/**
 * Prepare a web update process.
 */

QUI::$Ajax->registerFunction(
    'ajax_system_update_prepare',
    static function (): array {
        $Repository = new QUI\System\Update\RunRepository(VAR_DIR . 'update/runs/');
        $runs = $Repository->cleanupAndFindActive(time(), 86400);

        if (!empty($runs['active'])) {
            $State = $runs['active'][0];

            return [
                'prepared' => false,
                'active' => true,
                'run' => $State->toArray(),
                'deleted' => $runs['deleted']
            ];
        }

        $Launcher = QUI\System\Update\RunLauncherFactory::createDefault();
        $Launch = $Launcher->create(null, [
            'type' => 'web',
            'arguments' => []
        ]);
        $Run = $Launch->getRun();

        return [
            'prepared' => true,
            'active' => false,
            'id' => $Run->getState()->getId(),
            'url' => $Launch->getWebUrl(),
            'run' => $Run->getState()->toArray(),
            'deleted' => $runs['deleted']
        ];
    },
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
