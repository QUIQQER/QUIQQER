<?php

/**
 * Return addresses by ids
 *
 * @param array|string $ids - list of ids
 *
 * @return array
 */

QUI::getAjax()->registerFunction(
    'ajax_users_address_getAddressList',
    static function ($ids): array {
        $ids = json_decode($ids, true);
        $list = [];

        foreach ($ids as $id) {
            $addressField = is_numeric($id) ? "id" : "uuid";
            $QueryBuilder = QUI::getQueryBuilder();
            $result = $QueryBuilder
                ->select("id", "uid")
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
                $uid = $result["uid"];
                $User = QUI::getUsers()->get($uid);
                $Address = $User->getAddress($id);

                $attributes = $Address->getAttributes();
                $attributes['text'] = $Address->getText();
                $attributes['id'] = $Address->getUUID();
                $attributes['uuid'] = $Address->getUUID();

                $list[] = $attributes;
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }
        }

        return $list;
    },
    ['ids'],
    'Permission::checkAdminUser'
);
