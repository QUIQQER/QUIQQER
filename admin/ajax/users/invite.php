<?php

/**
 * Invite one user by e-mail address.
 *
 * @param string $email - E-mail address of the new user
 * @param string $groups - JSON encoded group UUID list
 *
 * @return string User UUID
 */

QUI::$Ajax->registerFunction(
    'ajax_users_invite',
    static function ($email, $groups): string {
        $groups = json_decode($groups, true);

        if (!is_array($groups)) {
            $groups = [];
        }

        $Invite = new QUI\Users\Invite();
        $User = $Invite->invite($email, $groups);

        return (string)$User->getUUID();
    },
    ['email', 'groups'],
    ['Permission::checkUser', 'quiqqer.admin.users.create']
);
