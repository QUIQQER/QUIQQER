<?php

/**
 * Return update runner history.
 */

QUI::$Ajax->registerFunction(
    'ajax_system_update_history',
    static function ($limit): array {
        $Repository = new QUI\System\Update\RunRepository(VAR_DIR . 'update/runs/');

        return array_map(static function (QUI\System\Update\RunState $State): array {
            return $State->toPublicArray();
        }, $Repository->list((int)$limit));
    },
    ['limit'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
