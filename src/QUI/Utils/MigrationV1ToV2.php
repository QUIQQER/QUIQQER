<?php

namespace QUI\Utils;

use QUI;
use QUI\Database\Exception;

use function count;
use function implode;
use function is_numeric;

class MigrationV1ToV2
{
    /**
     * migration helper for user fields in a db table
     * - default table identifier for the inset is ID
     * - you can overwrite the identifier with $indexId
     *
     * @throws Exception
     */
    public static function migrateUsers(
        string $table,
        array $userTableFields = [],
        string $indexId = 'id'
    ): void {
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
                    $uuid = QUI::getUsers()->get($uid)->getUUID();
                } catch (QUI\Exception) {
                    continue;
                }

                if (empty($uuid)) {
                    continue;
                }

                try {
                    QUI::getDataBase()->update(
                        $table,
                        [$field => $uuid],
                        [$indexId => $entry[$indexId]]
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

                if (empty($addressId)) {
                    continue;
                }

                if (!is_numeric($addressId)) {
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

    public static function migrateUserGroupField(string $table, string $field, string $indexId = 'id'): void
    {
        $result = QUI::getDataBase()->fetch([
            'from' => $table
        ]);

        foreach ($result as $entry) {
            $id = $entry[$indexId];
            $ugField = $entry[$field];
            $new = [];

            if (empty($ugField)) {
                continue;
            }

            $userGroups = UserGroups::parseUsersGroupsString($ugField);

            // users
            foreach ($userGroups['users'] as $userId) {
                if (!is_numeric($userId)) {
                    $new[] = 'u' . $userId;
                    continue;
                }

                try {
                    $new[] = 'u' . QUI::getUsers()->get($userId)->getUUID();
                } catch (QUI\Exception) {
                    $new[] = 'u' . $userId;
                }
            }

            // groups
            foreach ($userGroups['groups'] as $groupId) {
                if (!is_numeric($groupId)) {
                    $new[] = 'g' . $groupId;
                    continue;
                }

                try {
                    $new[] = 'g' . QUI::getGroups()->get($groupId)->getUUID();
                } catch (QUI\Exception) {
                    $new[] = 'g' . $groupId;
                }
            }

            // update
            try {
                QUI::getDataBase()->update(
                    $table,
                    [$field => ',' . implode(',', $new) . ','],
                    [$indexId => $id]
                );
            } catch (QUI\Exception) {
            }
        }
    }
}
