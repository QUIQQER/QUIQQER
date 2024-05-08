<?php

namespace QUI\Utils;

use QUI;
use QUI\Database\Exception;

use function count;
use function is_numeric;

class MigrationV1ToV2
{
    /**
     * migration helper for user fields in a db table
     * - table needs ID as identifier
     *
     * @throws Exception
     */
    public static function migrateUsers(string $table, array $userTableFields = []): void
    {
        if (!count($userTableFields)) {
            return;
        }

        $result = QUI::getDataBase()->fetch([
            'from' => $table
        ]);

        foreach ($result as $entry) {
            foreach ($userTableFields as $field) {
                if (!isset($entry[$field])) {
                    continue;
                }

                $uid = $entry[$field];

                if (!is_numeric($uid)) {
                    continue;
                }

                try {
                    QUI::getDataBase()->update(
                        $table,
                        [$field => QUI::getUsers()->get($uid)->getUUID()],
                        ['id' => $entry['id']]
                    );
                } catch (QUI\Exception) {
                }
            }
        }
    }

    /**
     * migration helper for user fields in a db table
     * - table needs ID as identifier
     * @throws Exception
     */
    public static function migrateAddresses(string $table, array $addressTableFields = []): void
    {
        if (!count($addressTableFields)) {
            return;
        }

        $result = QUI::getDataBase()->fetch([
            'from' => $table
        ]);

        foreach ($result as $entry) {
            foreach ($addressTableFields as $field) {
                if (!isset($entry[$field])) {
                    continue;
                }

                $addressId = $entry[$field];

                if (empty($addressId) || !is_numeric($addressId)) {
                    continue;
                }

                try {
                    $addressData = QUI::getDataBase()->fetch([
                        'from' => QUI::getDBTableName('	users_address'),
                        'where' => [
                            'id' => $addressId
                        ],
                        'limit' => 1
                    ]);

                    if (!count($addressData)) {
                        continue;
                    }

                    QUI::getDataBase()->update(
                        $table,
                        [$field => $addressData[0]['uuid']],
                        ['id' => $entry['id']]
                    );
                } catch (QUI\Exception) {
                }
            }
        }
    }
}
