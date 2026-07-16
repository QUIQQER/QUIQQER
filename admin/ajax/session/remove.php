<?php

/**
 * Remove a session key
 *
 * @param string $key
 */

QUI::getAjax()->registerFunction(
    'ajax_session_remove',
    static function ($key): void {
        if (!is_string($key) || !QUI\Session::isClientSessionKeyAllowed($key)) {
            throw new QUI\Exception('Access to this session key is not permitted.', 403);
        }

        QUI::getSession()->remove($key);
    },
    ['key']
);
