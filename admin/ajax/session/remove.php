<?php

/**
 * Remove a session key
 *
 * @param string $key
 */

QUI::$Ajax->registerFunction(
    'ajax_session_remove',
    static function ($key) {
        QUI::getSession()->del($key);
    },
    ['key']
);
