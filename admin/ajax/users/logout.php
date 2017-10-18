<?php

/**
 * Destroy user session
 */
QUI::$Ajax->registerFunction(
    'ajax_users_logout',
    function() {
        QUI::getUserBySession()->logout();
    },
    false
);
