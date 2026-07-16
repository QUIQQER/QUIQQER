<?php

/**
 * Return a session value
 *
 * @param string $key
 */

QUI::getAjax()->registerFunction(
    'ajax_session_get',
    static function ($key) {
        if (!is_string($key) || !QUI\Session::isClientSessionKeyAllowed($key)) {
            throw new QUI\Exception('Access to this session key is not permitted.', 403);
        }

        return QUI::getSession()->get($key);
    },
    ['key']
);
