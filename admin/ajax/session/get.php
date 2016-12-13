<?php

/**
 * Return a session value
 *
 * @param string $key
 */
QUI::$Ajax->registerFunction(
    'ajax_session_get',
    function ($key) {
        return QUI::getSession()->get($key);
    },
    array('key')
);
