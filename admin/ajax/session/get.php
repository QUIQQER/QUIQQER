<?php

/**
 * Return a session value
 *
 * @param string $key
 */

QUI::$Ajax->registerFunction(
    'ajax_session_get',
    static fn($key) => QUI::getSession()->get($key),
    ['key']
);
