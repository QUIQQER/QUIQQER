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
    function ($uid, $aid) {
        if (!isset($uid) || !$uid) {
            $result = QUI::getDataBase()->fetch([
                'select' => ['id', 'uid'],
                'from'   => QUI\Users\Manager::tableAddress(),
                'where'  => [
                    'id' => $aid
                ],
                'limit'  => 1
            ]);

            if (!isset($result[0])) {
                throw new QUI\Users\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/quiqqer',
                        'exception.lib.user.address.not.found',
                        [
                            'addressId' => $aid,
                            'userId'    => $uid
                        ]
                    )
                );
            }

            $uid = (int)$result[0]['uid'];
        }

        $User    = QUI::getUsers()->get((int)$uid);
        $Address = $User->getAddress((int)$aid);

        return $Address->getAttributes();
    },
    ['uid', 'aid'],
    'Permission::checkAdminUser'
);
