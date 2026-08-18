<?php

/**
 * User logout
 */

QUI::getAjax()->registerFunction('ajax_user_logout', static function (): void {
    QUI::getUserBySession()->logout();
});
