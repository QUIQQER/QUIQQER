<?php

/**
 * Return a session value
 *
 * @param string $key
 */

QUI::$Ajax->registerFunction(
    'ajax_session_get',
    fn($key) => QUI::getSession()->get($key),
    ['key']
);
