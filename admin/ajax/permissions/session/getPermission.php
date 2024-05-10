<?php

/**
 * Has the user the permission?
 *
 * @param $permission - name of the permission
 * @param $ruleset
 */

QUI::$Ajax->registerFunction(
    'ajax_permissions_session_getPermission',
    static fn($permission, $ruleset) => QUI::getUserBySession()->getPermission($permission, $ruleset),
    ['permission', 'ruleset']
);
