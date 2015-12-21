<?php

/**
 * System Tabellen optimieren
 */
QUI::$Ajax->registerFunction(
    'ajax_system_optimize',
    function () {
        $Table = \QUI::getDataBase()->Table();
        $Table->optimize($Table->getTables());
    },
    false,
    'Permission::checkSU'
);
