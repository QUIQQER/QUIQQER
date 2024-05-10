<?php

/**
 * Gibt die Button für den Benutzer zurück
 *
 * @param string / Integer $uid
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_users_getCategories',
    static function ($uid) {
        try {
            $Users = QUI::getUsers();
            $User = $Users->get($uid);
            $Toolbar = QUI\Users\Utils::getUserToolbar($User);

            return $Toolbar->toArray();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            throw $Exception;
        }
    },
    ['uid'],
    'Permission::checkAdminUser'
);
