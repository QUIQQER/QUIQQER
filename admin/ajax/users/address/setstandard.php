<?php

/**
 * Set the address as standard address
 *
 * @param integer|String $uid - id of the user
 * @param integer|String $aid - id of the address
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_users_address_setstandard',
    static function ($uid, $aid): void {
        $User = QUI::getUsers()->get($uid);
        $Address = $User->getAddress($aid);

        $User->setAttribute('address', $Address->getUUID());
        $User->save();
    },
    ['uid', 'aid'],
    ['Permission::checkAdminUser', 'quiqqer.admin.users.edit']
);
