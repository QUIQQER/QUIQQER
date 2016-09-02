<?php

/**
 * Benutzer deaktivieren
 *
 * @param integer|array|string $uid
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_users_deactivate',
    function ($uid) {
        $uid = json_decode($uid, true);

        if (!is_array($uid)) {
            $uid = array($uid);
        }

        $Users  = QUI::getUsers();
        $result = array();

        foreach ($uid as $_uid) {
            try {
                $User = $Users->get($_uid);
                $User->deactivate();

                $result[$_uid] = $User->isActive() ? 1 : 0;
            } catch (QUI\Exception $Exception) {
                $result[$_uid] = 0;

                QUI::getMessagesHandler()->addError(
                    $Exception->getMessage()
                );

                continue;
            }
        }

        return $result;
    },
    array('uid'),
    'Permission::checkSU'
);
