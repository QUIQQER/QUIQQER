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
    function ($uid) {
        try {
            $Users = QUI::getUsers();
            $User = $Users->get((int)$uid);
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
