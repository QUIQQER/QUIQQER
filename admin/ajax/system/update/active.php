<?php

/**
 * Return active update processes.
 */

QUI::$Ajax->registerFunction(
    'ajax_system_update_active',
    static function (): array {
        $Repository = new QUI\System\Update\RunRepository(VAR_DIR . 'update/runs/');
        $runs = $Repository->cleanupAndFindActive(time(), 86400);

        return [
            'active' => array_map(static function (QUI\System\Update\RunState $State): array {
                return $State->toArray();
            }, $runs['active']),
            'deleted' => $runs['deleted']
        ];
    },
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
