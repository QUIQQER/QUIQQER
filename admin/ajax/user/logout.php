<?php

/**
 * User logout
 */

QUI::$Ajax->registerFunction('ajax_user_logout', static function () {
    QUI::getUserBySession()->logout();
});
