<?php

/**
 * Set a session key value
 *
 * @param string $key
 * @param mixed $value
 */

QUI::$Ajax->registerFunction(
    'ajax_session_set',
    static function ($key, $value): void {
        QUI::getSession()->set($key, json_decode($value, true));
    },
    ['key', 'value']
);
