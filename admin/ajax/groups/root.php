<?php

/**
 * Return the root ID
 *
 * @return integer
 */
QUI::$Ajax->registerFunction(
    'ajax_groups_root',
    function () {
        require_once 'get.php';

        return ajax_groups_get(
            (int)QUI::conf('globals', 'root')
        );
    },
    false,
    'Permission::checkSU'
);
