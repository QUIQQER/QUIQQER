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
    'ajax_users_address_getUserByAddress',
    function ($aid) {
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
                        'addressId' => $aid
                    ]
                )
            );
        }

        $uid  = (int)$result[0]['uid'];
        $User = QUI::getUsers()->get((int)$uid);

        return $User->getId();
    },
    ['aid'],
    ['Permission::checkAdminUser']
);
