<?php

/**
 * Return a session value
 *
 * @param string $key
 */

QUI::$Ajax->registerFunction(
    'ajax_session_get',
    static function ($key) {
        return QUI::getSession()->get($key);
    },
    ['key']
);
