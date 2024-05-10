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
    static function ($uid) {
        $Users = QUI::getUsers();
        $uIds = json_decode($uid, true);

        if (!is_array($uIds)) {
            $uIds = [$uIds];
        }

        foreach ($uIds as $uid) {
            $Users->get($uid)->delete();
        }

        QUI::getMessagesHandler()->addInformation(
            QUI::getLocale()->get('quiqqer/core', 'message.user.deleted.successful', [
                'ids' => implode(', ', $uIds)
            ])
        );

        return true;
    },
    ['uid'],
    'Permission::checkAdminUser'
);
