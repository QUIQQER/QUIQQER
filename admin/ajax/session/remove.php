<?php

/**
 * Remove a session key
 *
 * @param string $key
 */
QUI::$Ajax->registerFunction(
    'ajax_session_remove',
    function ($key) {
        QUI::getSession()->del($key);
    },
    array('key')
);
