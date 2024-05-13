<?php

/**
 * User is admin check
 */

QUI::$Ajax->registerFunction('ajax_user_canUseBackend', static function (): bool {
    return QUI::getUserBySession()->canUseBackend();
});
