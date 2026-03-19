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
    static function ($uid) {
        try {
            $User = QUI::getUsers()->get($uid);
            $attributes = $User->getAttributes();
        } catch (QUI\Exception) {
            $User = QUI::getUsers()->getNobody();
            $attributes = $User->getAttributes();
        }

        if (!empty($attributes['toolbar'])) {
            $attributes['toolbar'] = QUI\Editor\Manager::normalizeToolbarIdentifier(
                $attributes['toolbar']
            );
        }

        if (!empty($attributes['assigned_toolbar'])) {
            $toolbars = array_map(
                ['QUI\\Editor\\Manager', 'normalizeToolbarIdentifier'],
                explode(',', $attributes['assigned_toolbar'])
            );

            $toolbars = array_filter($toolbars);
            $attributes['assigned_toolbar'] = implode(',', $toolbars);
        }

        $attributes['toolbars'] = QUI\Editor\Manager::getToolbarsFromUser($User);

        return $attributes;
    },
    ['uid'],
    'Permission::checkAdminUser'
);
