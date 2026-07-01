<?php

/**
 * Cancel an update process.
 */

QUI::$Ajax->registerFunction(
    'ajax_system_update_cancel',
    static function ($id): array {
        $Repository = new QUI\System\Update\RunRepository(VAR_DIR . 'update/runs/');
        $State = $Repository->cancel((string)$id);
        $process = $State->getProcess();
        $pid = is_array($process) ? (int)($process['pid'] ?? 0) : 0;

        if ($pid > 0 && function_exists('posix_kill')) {
            $isRunning = posix_kill($pid, 0);

            if ($isRunning) {
                posix_kill($pid, 15);
            }
        }

        return $State->toPublicArray();
    },
    ['id'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
