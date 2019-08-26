<?php

/**
 * User profile template
 *
 * @return String
 */
QUI::$Ajax->registerFunction('ajax_user_profileTemplate', function () {
    return QUI::getUsers()->getProfileTemplate();
});
