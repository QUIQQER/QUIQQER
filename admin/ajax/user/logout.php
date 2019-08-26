<?php

/**
 * User logout
 */
QUI::$Ajax->registerFunction('ajax_user_logout', function () {
    QUI::getUserBySession()->logout();
});
