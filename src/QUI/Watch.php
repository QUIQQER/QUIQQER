<?php

namespace QUI;

use QUI;

/**
 * Class Watch
 *
 * Set quiqqer/watcher messages
 */
class Watch
{
    /**
     * Write failed login attempts to watcher log
     */
    public static function authenticatorLoginError($event, $params): string
    {
        $userId = $params[0];
        $authenticator = '-';

        if (!empty($params[2])) {
            $authenticator = $params[2];
        }

        $username = QUI::getLocale()->get('quiqqer/core', 'watch.unknown_user');

        try {
            $User = QUI::getUsers()->get($userId);
            $username = $User->getUsername();
        } catch (\Exception) {
            // nothing
        }

        return QUI::getLocale()->get(
            'quiqqer/core',
            'watch.authenticatorLoginError',
            [
                'userId' => $userId ?: '-',
                'username' => $username,
                'authenticator' => $authenticator,
                'ipAddress' => $_SERVER['REMOTE_ADDR'],
                'userAgent' => empty($_SERVER['HTTP_USER_AGENT']) ? '-' : $_SERVER['HTTP_USER_AGENT']
            ]
        );
    }
}
