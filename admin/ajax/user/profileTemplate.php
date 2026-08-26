<?php

/**
 * User profile template
 *
 * @return String
 */

QUI::getAjax()->registerFunction('ajax_user_profileTemplate', static function (): string {
    return QUI::getUsers()->getProfileTemplate();
});
