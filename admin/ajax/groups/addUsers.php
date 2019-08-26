<?php

/**
 * Add user(s) to a group
 *
 * @param integer $gid - Group-ID
 * @param array $userIds - array with user IDs
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_groups_addUsers',
    function ($gid, $userIds) {
        $userIds = \json_decode($userIds, true);
        $Group   = QUI::getGroups()->get((int)$gid);

        foreach ($userIds as $userId) {
            $User = QUI::getUsers()->get((int)$userId);
            $Group->addUser($User);
            $User->save();
        }
    },
    ['gid', 'userIds'],
    'Permission::checkSU'
);
