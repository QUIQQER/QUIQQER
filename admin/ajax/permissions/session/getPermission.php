<?php

/**
 * Has the user the permission?
 *
 * @param $permission - name of the permission
 * @param $ruleset
 */
QUI::$Ajax->registerFunction(
    'ajax_permissions_session_getPermission',
    function ($permission, $ruleset) {
        return QUI::getUserBySession()->getPermission($permission, $ruleset);
    },
    array('permission', 'ruleset')
);
