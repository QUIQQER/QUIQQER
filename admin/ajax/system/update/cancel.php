<?php

/**
 * Cancel an update process.
 */

QUI::$Ajax->registerFunction(
    'ajax_system_update_cancel',
    static function ($id): array {
        $Repository = new QUI\System\Update\RunRepository(VAR_DIR . 'update/runs/');

        return $Repository->cancel((string)$id)->toArray();
    },
    ['id'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
