<?php

/**
 * Return te CSP Settings
 */
QUI::$Ajax->registerFunction(
    'ajax_system_settings_saveCSP',
    function ($data) {
        $data = json_decode($data, true);
        $CSP  = QUI\System\CSP::getInstance();

        $CSP->clearCSPDirectives();

        foreach ($data as $key => $value) {
            $CSP->setCSPDirectiveToConfig($key, $value);
        }
    },
    ['data'],
    ['Permission::checkAdminUser']
);
