<?php

/**
 * Update File installieren
 *
 * @return String
 */
QUI::$Ajax->registerFunction(
    'ajax_system_update_install',
    function ($File) {

    },
    array('File'),
    'Permission::checkSU'
);
