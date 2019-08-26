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
        $uids  = \json_decode($uid, true);

        if (!\is_array($uids)) {
            $uids = [$uids];
        }

        foreach ($uids as $uid) {
            $Users->get($uid)->delete();
        }

        QUI::getMessagesHandler()->addInformation(
            QUI::getLocale()->get('quiqqer/quiqqer', 'message.user.deleted.successful', [
                'ids' => \implode(', ', $uids)
            ])
        );

        return true;
    },
    ['uid'],
    'Permission::checkSU'
);
