<?php

/**
 * Benutzer aktivieren
 *
 * @param integer|array|string $uid
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_users_activate',
    function ($uid) {
        $uid = json_decode($uid, true);

        if (!is_array($uid)) {
            $uid = array($uid);
        }

        $Users     = QUI::getUsers();
        $result    = array();
        $activated = array();

        foreach ($uid as $_uid) {
            try {
                $User = $Users->get($_uid);
                $User->activate();

                $result[$_uid] = $User->isActive() ? 1 : 0;

                if ($User->isActive()) {
                    $activated[] = $User->getId();
                }
            } catch (QUI\Exception $Exception) {
                $result[$_uid] = 0;

                QUI::getMessagesHandler()->addError(
                    $Exception->getMessage()
                );

                continue;
            }
        }

        if (count($activated)) {
            QUI::getMessagesHandler()->addSuccess(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'message.users.activated',
                    array(
                        'users' => implode(',', $activated)
                    )
                )
            );
        }

        return $result;
    },
    array('uid'),
    'Permission::checkSU'
);
