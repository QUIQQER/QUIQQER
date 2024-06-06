<?php

/**
 * User activation
 *
 * @param integer|array|string $uid
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_users_activate',
    static function ($uid): array {
        $uid = json_decode($uid, true);

        if (!is_array($uid)) {
            $uid = [$uid];
        }

        $Users = QUI::getUsers();
        $result = [];
        $activated = [];

        foreach ($uid as $_uid) {
            try {
                $User = $Users->get($_uid);
            } catch (QUI\Exception) {
                continue;
            }

            try {
                $User->activate();

                $result[$_uid] = $User->isActive() ? 1 : 0;

                if ($User->isActive()) {
                    $activated[] = $User->getUUID();
                }
            } catch (QUI\Exception $Exception) {
                $result[$_uid] = $User->isActive() ? 1 : 0;

                QUI::getMessagesHandler()->addError(
                    $Exception->getMessage()
                );

                continue;
            }
        }

        if (count($activated)) {
            QUI::getMessagesHandler()->addSuccess(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'message.users.activated',
                    [
                        'users' => implode(',', $activated)
                    ]
                )
            );
        }

        return $result;
    },
    ['uid'],
    'Permission::checkAdminUser'
);
