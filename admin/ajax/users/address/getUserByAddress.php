<?php

/**
 * Delete a address
 *
 * @param integer|string $uid - id of the user
 * @param integer|string $aid - id of the address
 *
 * @return array
 */

QUI::getAjax()->registerFunction(
    'ajax_users_address_getUserByAddress',
    static function ($aid): string|int {
        $addressField = is_numeric($aid) ? "id" : "uuid";
        $QueryBuilder = QUI::getQueryBuilder();
        $result = $QueryBuilder
            ->select("id", "uid")
            ->from(QUI\Users\Manager::tableAddress())
            ->where($QueryBuilder->expr()->eq($addressField, ":addressId"))
            ->setParameter("addressId", $aid)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();


        if (!$result) {
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

        $User = QUI::getUsers()->get($result["uid"]);

        return $User->getUUID();
    },
    ['aid'],
    ['Permission::checkAdminUser']
);
