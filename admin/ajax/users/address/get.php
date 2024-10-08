<?php

/**
 * Return an address from an user
 *
 * @param integer|string $uid - id of the user
 * @param integer|string $aid - id of the address
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_users_address_get',
    static function ($uid, $aid) {
        if (!isset($uid) || !$uid) {
            if (is_numeric($aid)) {
                $result = QUI::getDataBase()->fetch([
                    'select' => ['id', 'uid', 'userUuid'],
                    'from' => QUI\Users\Manager::tableAddress(),
                    'where' => [
                        'id' => $aid,
                    ],
                    'limit' => 1
                ]);
            } else {
                $result = QUI::getDataBase()->fetch([
                    'select' => ['id', 'uid', 'userUuid'],
                    'from' => QUI\Users\Manager::tableAddress(),
                    'where' => [
                        'uuid' => $aid
                    ],
                    'limit' => 1
                ]);
            }

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

            $uid = $result[0]['uid'];
        }

        $User = QUI::getUsers()->get($uid);
        $Address = $User->getAddress($aid);
        $address = $Address->getAttributes();
        $Standard = $User->getStandardAddress();

        if ($Standard && $Standard->getUUID() == $Address->getUUID()) {
            $address['default'] = 1;
        } else {
            $address['default'] = 0;
        }

        return $address;
    },
    ['uid', 'aid'],
    'Permission::checkAdminUser'
);
