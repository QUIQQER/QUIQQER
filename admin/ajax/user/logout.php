<?php

/**
 * User logout
 */

QUI::$Ajax->registerFunction('ajax_user_logout', static function (): void {
    QUI::getUserBySession()->logout();
});
