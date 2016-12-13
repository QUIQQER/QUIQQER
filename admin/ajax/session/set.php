<?php

/**
 * Set a session key value
 *
 * @param string $key
 * @param mixed $value
 */
QUI::$Ajax->registerFunction(
    'ajax_session_set',
    function ($key, $value) {
        QUI::getSession()->set($key, $value);
    },
    array('key', 'value')
);
