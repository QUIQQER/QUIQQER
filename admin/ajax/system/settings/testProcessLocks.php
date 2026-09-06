<?php

QUI::getAjax()->registerFunction(
    'ajax_system_settings_testProcessLocks',
    static function ($data): bool {
        $input = json_decode($data, true);

        if (!is_array($input)) {
            throw new QUI\Exception(['quiqqer/core', 'processLocks.invalid'], 400);
        }

        return (new QUI\Lock\Settings())->test($input);
    },
    ['data'],
    ['Permission::checkAdminUser', 'quiqqer.settings']
);
