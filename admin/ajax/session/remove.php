<?php

/**
 * Remove a session key
 *
 * @param string $key
 */

QUI::$Ajax->registerFunction(
    'ajax_session_remove',
    static function ($key): void {
        QUI::getSession()->del($key);
    },
    ['key']
);
