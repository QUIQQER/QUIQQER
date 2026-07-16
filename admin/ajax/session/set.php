<?php

/**
 * Set a session key value
 *
 * @param string $key
 * @param mixed $value
 */

QUI::getAjax()->registerFunction(
    'ajax_session_set',
    static function ($key, $value): void {
        if (!is_string($key) || !QUI\Session::isClientSessionKeyAllowed($key)) {
            throw new QUI\Exception('Access to this session key is not permitted.', 403);
        }

        QUI::getSession()->set($key, json_decode($value, true));
    },
    ['key', 'value']
);
