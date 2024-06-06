<?php

/**
 * Get data for sending an e-mail to the user
 *
 * @param int $userId - QUIQQER User Id
 * @return array
 *
 * @throws QUI\Exception
 */

QUI::$Ajax->registerFunction(
    'ajax_user_getMailData',
    static function ($userId) {
        $User = QUI::getUsers()->get($userId);

        return [
            'name' => $User->getName(),
            'lang' => QUI::getLocale()->get('quiqqer/core', 'language.' . $User->getLang()),
            'email' => $User->getAttribute('email')
        ];
    },
    ['userId'],
    'Permission::checkAdminUser'
);
