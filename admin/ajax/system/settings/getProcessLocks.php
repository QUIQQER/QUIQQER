<?php

QUI::getAjax()->registerFunction(
    'ajax_system_settings_getProcessLocks',
    static fn(): array => (new QUI\Lock\Settings())->get(),
    [],
    ['Permission::checkAdminUser', 'quiqqer.settings']
);
