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
    static function ($gid, $userIds): void {
        $userIds = json_decode($userIds, true);
        $Group = QUI::getGroups()->get($gid);

        foreach ($userIds as $userId) {
            $User = QUI::getUsers()->get($userId);

            if ($User instanceof QUI\Users\User) {
                $Group->addUser($User);
                $User->save();
            }
        }
    },
    ['gid', 'userIds'],
    'Permission::checkSU'
);
