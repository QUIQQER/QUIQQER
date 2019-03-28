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
        $uid = \json_decode($uid, true);

        if (!\is_array($uid)) {
            $uid = [$uid];
        }

        $Users       = QUI::getUsers();
        $result      = [];
        $deactivated = [];

        foreach ($uid as $_uid) {
            try {
                $User = $Users->get($_uid);
            } catch (QUI\Exception $Exception) {
                continue;
            }

            try {
                $User->deactivate();

                $result[$_uid] = $User->isActive() ? 1 : 0;

                if (!$User->isActive()) {
                    $deactivated[] = $User->getId();
                }
            } catch (QUI\Exception $Exception) {
                $result[$_uid] = $User->isActive() ? 1 : 0;

                QUI::getMessagesHandler()->addError(
                    $Exception->getMessage()
                );

                continue;
            }
        }

        if (\count($deactivated)) {
            QUI::getMessagesHandler()->addSuccess(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'message.users.deactivated',
                    [
                        'users' => \implode(',', $deactivated)
                    ]
                )
            );
        }

        return $result;
    },
    ['uid'],
    'Permission::checkAdminUser'
);
