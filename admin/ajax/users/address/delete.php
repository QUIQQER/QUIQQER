<?php

/**
 * Delete a address
 *
 * @param integer|string $uid - id of the user
 * @param integer|string $aid - id of the address
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_users_address_delete',
    static function ($uid, $aid): void {
        if (!isset($uid) || !$uid) {
            $result = QUI::getDataBase()->fetch([
                'select' => ['id', 'uid'],
                'from' => QUI\Users\Manager::tableAddress(),
                'where_or' => [
                    'id' => $aid,
                    'uuid' => $aid
                ],
                'limit' => 1
            ]);

            if (!isset($result[0])) {
                throw new QUI\Users\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/core',
                        'exception.lib.user.address.not.found',
                        [
                            'addressId' => $aid,
                            'userId' => $uid
                        ]
                    )
                );
            }

            $uid = $result[0]['uuid'];
        }

        $User = QUI::getUsers()->get($uid);
        $Address = $User->getAddress($aid);

        $Address->delete();
    },
    ['uid', 'aid'],
    ['Permission::checkAdminUser', 'quiqqer.admin.users.edit']
);
