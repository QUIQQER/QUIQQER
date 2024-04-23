<?php

/**
 * Return the root ID
 *
 * @return integer
 */

QUI::$Ajax->registerFunction(
    'ajax_groups_root',
    function () {
        require_once __DIR__ . '/get.php';

        $result = QUI::$Ajax->callRequestFunction('ajax_groups_get', [
            'gid' => QUI::conf('globals', 'root')
        ]);

        return $result['result'];
    },
    false,
    'Permission::checkAdminUser'
);
