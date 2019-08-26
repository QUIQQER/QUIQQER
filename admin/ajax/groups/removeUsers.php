<?php

/**
 * Remove user(s) from a group
 *
 * @param integer $gid - Group-ID
 * @param array $userIds - array with user IDs
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_groups_removeUsers',
    function ($gid, $userIds) {
        $userIds = \json_decode($userIds, true);
        $Group   = QUI::getGroups()->get((int)$gid);

        foreach ($userIds as $userId) {
            $User = QUI::getUsers()->get((int)$userId);
            $Group->removeUser($User);
            $User->save();
        }
    },
    ['gid', 'userIds'],
    'Permission::checkSU'
);
