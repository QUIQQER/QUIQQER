<?php

/**
 * Destroy user session
 */

QUI::$Ajax->registerFunction(
    'ajax_users_logout',
    static function () {
        QUI::getUserBySession()->logout();
    },
    false
);
