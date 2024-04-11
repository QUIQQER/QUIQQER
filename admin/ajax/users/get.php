<?php

/**
 * Return the user data
 *
 * @param string / Integer $uid
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_users_get',
    function ($uid) {
        try {
            $User = QUI::getUsers()->get($uid);
            $attributes = $User->getAttributes();
        } catch (QUI\Exception) {
            $User = QUI::getUsers()->getNobody();
            $attributes = $User->getAttributes();
        }

        $attributes['toolbars'] = QUI\Editor\Manager::getToolbarsFromUser($User);

        return $attributes;
    },
    ['uid'],
    'Permission::checkAdminUser'
);
