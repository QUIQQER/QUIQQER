<?php

/**
 * User is admin check
 */
QUI::$Ajax->registerFunction('ajax_user_canUseBackend', function () {
    return QUI::getUserBySession()->canUseBackend();
});
