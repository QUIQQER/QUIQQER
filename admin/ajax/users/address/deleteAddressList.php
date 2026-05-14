<?php

/**
 * Return addresses by ids
 *
 * @param array|string $ids - list of ids
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_users_address_deleteAddressList',
    static function ($ids): array {
        $ids = json_decode($ids, true);
        $list = [];

        foreach ($ids as $id) {
            if (is_numeric($id)) {
                $result = QUI::getDataBase()->fetch([
                    'select' => ['id', 'uid', 'uuid', 'userUuid'],
                    'from' => QUI\Users\Manager::tableAddress(),
                    'where' => [
                        'id' => $id
                    ],
                    'limit' => 1
                ]);
            } else {
                $result = QUI::getDataBase()->fetch([
                    'select' => ['id', 'uid', 'uuid', 'userUuid'],
                    'from' => QUI\Users\Manager::tableAddress(),
                    'where' => [
                        'uuid' => $id,
                    ],
                    'limit' => 1
                ]);
            }


            if (!isset($result[0])) {
                continue;
            }

            try {
                $User = QUI::getUsers()->get($result[0]['userUuid']);
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
