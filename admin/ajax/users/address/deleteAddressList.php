<?php

/**
 * Return addresses by ids
 *
 * @param array|string $ids - list of ids
 *
 * @return array
 */

QUI::getAjax()->registerFunction(
    'ajax_users_address_deleteAddressList',
    static function ($ids): array {
        $ids = json_decode($ids, true);
        $list = [];

        foreach ($ids as $id) {
            $addressField = is_numeric($id) ? "id" : "uuid";
            $QueryBuilder = QUI::getQueryBuilder();
            $result = $QueryBuilder
                ->select("id", "uid", "uuid", "userUuid")
                ->from(QUI\Users\Manager::tableAddress())
                ->where($QueryBuilder->expr()->eq($addressField, ":addressId"))
                ->setParameter("addressId", $id)
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();


            if (!$result) {
                continue;
            }

            try {
                $User = QUI::getUsers()->get($result["userUuid"]);
                $Address = $User->getAddress($id);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);

                continue;
            }

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

            try {
                $Address->delete();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }
        }

        return $list;
    },
    ['ids'],
    'Permission::checkAdminUser'
);
