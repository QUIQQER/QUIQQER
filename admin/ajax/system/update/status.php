<?php

/**
 * Return update process state.
 */

QUI::getAjax()->registerFunction(
    'ajax_system_update_status',
    static function ($id): array {
        $Repository = new QUI\System\Update\RunRepository(VAR_DIR . 'update/runs/');

        return $Repository->load((string)$id)->toPublicArray();
    },
    ['id'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
