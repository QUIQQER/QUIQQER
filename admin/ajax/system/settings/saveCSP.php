<?php

/**
 * Return te CSP Settings
 */
QUI::$Ajax->registerFunction(
    'ajax_system_settings_saveCSP',
    function ($data) {
        $data = json_decode($data, true);

        QUI\System\CSP::getInstance()->clearCSPDirectives();

        foreach ($data as $key => $value) {
            QUI\System\CSP::getInstance()->setCSPDirectiveToConfig($key, $value);
        }
    },
    array('data'),
    array('Permission::checkAdminUser')
);
