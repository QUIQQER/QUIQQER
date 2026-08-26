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
    'ajax_users_address_delete',
    static function ($uid, $aid): void {
        if (!isset($uid) || !$uid) {
            $addressField = is_numeric($aid) ? "id" : "uuid";
            $QueryBuilder = QUI::getQueryBuilder();
            $result = $QueryBuilder
                ->select("id", "uid", "userUuid")
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
                            'addressId' => $aid,
                            'userId' => $uid
                        ]
                    )
                );
            }

            $uid = $result["userUuid"];
        }

        $User = QUI::getUsers()->get($uid);
        $Address = $User->getAddress($aid);
        $StandardAddress = $User->getStandardAddress();

        if (
            $StandardAddress !== null
            && $StandardAddress->getUUID() === $Address->getUUID()
        ) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.users.address.delete.default'
                )
            );
        }

        $Address->delete();
    },
    ['uid', 'aid'],
    ['Permission::checkAdminUser', 'quiqqer.admin.users.edit']
);
