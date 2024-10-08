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
    static function ($aid): string|int {
        if (is_numeric($aid)) {
            $result = QUI::getDataBase()->fetch([
                'select' => ['id', 'uid'],
                'from' => QUI\Users\Manager::tableAddress(),
                'where' => [
                    'id' => $aid,
                ],
                'limit' => 1
            ]);
        } else {
            $result = QUI::getDataBase()->fetch([
                'select' => ['id', 'uid'],
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
                        'addressId' => $aid
                    ]
                )
            );
        }

        $User = QUI::getUsers()->get($result[0]['uid']);

        return $User->getUUID();
    },
    ['aid'],
    ['Permission::checkAdminUser']
);
