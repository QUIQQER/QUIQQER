<?php

QUI::getAjax()->registerFunction(
    'ajax_system_settings_saveProcessLocks',
    static function ($data): array {
        $input = json_decode($data, true);

        if (!is_array($input)) {
            throw new QUI\Exception(['quiqqer/core', 'processLocks.invalid'], 400);
        }

        return (new QUI\Lock\Settings())->save($input);
    },
    ['data'],
    ['Permission::checkAdminUser', 'quiqqer.settings']
);
