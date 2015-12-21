<?php

/**
 * Delete Users
 *
 * @param string|integer $uid - JSONArray of Users-IDs, or one User-ID
 *
 * @return boolean
 */
QUI::$Ajax->registerFunction(
    'ajax_users_delete',
    function ($uid) {
        $Users = QUI::getUsers();
        $uids  = json_decode($uid, true);

        if (!is_array($uids)) {
            $uids = array($uids);
        }

        foreach ($uids as $uid) {
            $Users->get($uid)->delete();
        }

        QUI::getMessagesHandler()->addInformation(
            'Die Benutzer ' . implode(', ', $uids) . ' wurden erfolgreich gelöscht'
        ); // #locale

        return true;
    },
    array('uid'),
    'Permission::checkSU'
);
