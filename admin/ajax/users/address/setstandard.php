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
    function ($uid, $aid) {
        $User    = QUI::getUsers()->get((int)$uid);
        $Address = $User->getAddress((int)$aid);

        $User->setAttribute('address', $Address->getId());
        $User->save();
    },
    ['uid', 'aid'],
    ['Permission::checkAdminUser', 'quiqqer.admin.users.edit']
);
