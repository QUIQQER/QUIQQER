<?php

/**
 * Destroy user session
 */

QUI::getAjax()->registerFunction(
    'ajax_users_logout',
    static function (): void {
        QUI::getUserBySession()->logout();
    },
    false
);
