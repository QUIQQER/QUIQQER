<?php

/**
 * Switch the user status of the users
 *
 * @param string $uid - JSON Array | JSON Integer
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_users_switchstatus',
    function ($uid) {
        $uid = json_decode($uid, true);

        if (!is_array($uid)) {
            $uid = [$uid];
        }

        $Users  = QUI::getUsers();
        $result = [];

        foreach ($uid as $_uid) {
            try {
                $User = $Users->get($_uid);

                if ($User->isActive()) {
                    $User->deactivate();

                    QUI::getMessagesHandler()->addSuccess(
                        QUI::getLocale()->get(
                            'quiqqer/system',
                            'message.user.deactivate'
                        )
                    );
                } else {
                    $User->activate();

                    QUI::getMessagesHandler()->addSuccess(
                        QUI::getLocale()->get(
                            'quiqqer/system',
                            'message.user.activate'
                        )
                    );
                }

                $result[$_uid] = $User->isActive() ? 1 : 0;
            } catch (QUI\Exception $Exception) {
                QUI::getMessagesHandler()->addAttention(
                    $Exception->getMessage()
                );

                continue;
            }
        }

        return $result;
    },
    ['uid'],
    'Permission::checkAdminUser'
);
