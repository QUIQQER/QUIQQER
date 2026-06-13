<?php

namespace QUI\Users;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use QUI;
use QUI\Exception;
use QUI\ExceptionStack;

use function array_filter;

use const OPT_DIR;

/**
 * Is responsible for installing and customizing the databases for users and groups in the QUIQQER system.
 *
 * It contains routines that ensure that all necessary database structures and entries
 * for the administration of users and groups are created and updated correctly.
 */
class Install
{
    /**
     * User installation stuff
     */
    public static function user(): void
    {
        try {
            $Connection = QUI::getDataBaseConnection();
            $Platform = $Connection->getDatabasePlatform();
            $table = QUI\Users\Manager::table();

            self::ensureColumnDefinition($Connection, $table, "lastedit", "datetime", ["notnull" => false]);
            self::ensureColumnDefinition($Connection, $table, "expire", "datetime", ["notnull" => false]);
            self::ensureColumnDefinition($Connection, $table, "password", "string", [
                "length" => 255,
                "default" => ""
            ]);
            self::ensureColumnDefinition($Connection, $table, "birthday", "date", ["notnull" => false]);

            if (!$Platform instanceof AbstractMySQLPlatform) {
                return;
            }

            $quotedTable = $Platform->quoteSingleIdentifier($table);

            $Connection->executeStatement(
                "UPDATE " . $quotedTable . "
                SET " . $Platform->quoteSingleIdentifier("lastedit") . " = NULL
                WHERE
                    CAST(" . $Platform->quoteSingleIdentifier("lastedit") . " AS CHAR) = '0000-00-00 00:00:00' OR
                    CAST(" . $Platform->quoteSingleIdentifier("lastedit") . " AS CHAR) = ''"
            );

            $Connection->executeStatement(
                "UPDATE " . $quotedTable . "
                SET " . $Platform->quoteSingleIdentifier("expire") . " = NULL
                WHERE
                    CAST(" . $Platform->quoteSingleIdentifier("expire") . " AS CHAR) = '0000-00-00 00:00:00' OR
                    CAST(" . $Platform->quoteSingleIdentifier("expire") . " AS CHAR) = ''"
            );

            $Connection->executeStatement(
                "UPDATE " . $quotedTable . "
                SET " . $Platform->quoteSingleIdentifier("birthday") . " = NULL
                WHERE
                    CAST(" . $Platform->quoteSingleIdentifier("birthday") . " AS CHAR) = '0000-00-00 00:00:00' OR
                    CAST(" . $Platform->quoteSingleIdentifier("birthday") . " AS CHAR) = ''"
            );
        } catch (\Doctrine\DBAL\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }
    }

    /**
     * group installation stuff
     *
     * @throws ExceptionStack
     * @throws Exception
     * @throws QUI\Database\Exception
     */
    public static function groups(): void
    {
        // read database XML, because we need the newest groups db
        $dbFields = QUI\Utils\Text\XML::getDataBaseFromXml(OPT_DIR . 'quiqqer/core/database.xml');
        unset($dbFields['projects']);

        $dbFields['globals'] = array_filter($dbFields['globals'], static function (array $entry): bool {
            return $entry['suffix'] === 'groups';
        });

        QUI\Utils\Text\XML::importDataBase($dbFields);

        try {
            $Connection = QUI::getDataBaseConnection();
            $groupTable = QUI\Groups\Manager::table();
            $Platform = $Connection->getDatabasePlatform();
            $quotedGroupTable = $Platform->quoteSingleIdentifier($groupTable);

            self::ensureColumnDefinition($Connection, $groupTable, "parent", "string", [
                "length" => 50,
                "notnull" => false
            ]);

            if (!self::hasPrimaryKey($Connection, $groupTable)) {
                $Connection->executeStatement("ALTER TABLE $quotedGroupTable ADD PRIMARY KEY (id)");
            }

            if (!self::hasIndex($Connection, $groupTable, 'parent')) {
                $Connection->executeStatement("CREATE INDEX parent ON $quotedGroupTable (parent)");
            }


            // Guest
            $QueryBuilder = QUI::getQueryBuilder();
            $result = $QueryBuilder
                ->select('id')
                ->from($quotedGroupTable)
                ->where($QueryBuilder->expr()->eq('id', ':id'))
                ->setParameter('id', 0)
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();

            if (!$result) {
                QUI\System\Log::addNotice('Guest Group does not exist.');

                $Connection->insert($quotedGroupTable, [
                    'id' => 0,
                    'uuid' => 0,
                    'active' => 1,
                    'parent' => 0,
                    'name' => 'Guest'
                ]);

                QUI\System\Log::addNotice('Guest Group was created.');
            } else {
                $Connection->update($quotedGroupTable, [
                    'name' => 'Guest',
                    'active' => 1
                ], [
                    'id' => 0
                ]);

                QUI\System\Log::addNotice('Guest exists only updated');
            }


            // Everyone
            $QueryBuilder = QUI::getQueryBuilder();
            $result = $QueryBuilder
                ->select('id')
                ->from($quotedGroupTable)
                ->where($QueryBuilder->expr()->eq('id', ':id'))
                ->setParameter('id', 1)
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();

            if (!$result) {
                QUI\System\Log::addNotice('Everyone Group does not exist...');

                $Connection->insert($quotedGroupTable, [
                    'id' => 1,
                    'uuid' => 1,
                    'active' => 1,
                    'parent' => 0,
                    'name' => 'Everyone'
                ]);

                QUI\System\Log::addNotice('Everyone Group was created.');
            } else {
                $Connection->update($quotedGroupTable, [
                    'name' => 'Everyone',
                    'active' => 1
                ], [
                    'id' => 1
                ]);

                QUI\System\Log::addNotice('Everyone exists');
            }
        } catch (\Doctrine\DBAL\Exception $Exception) {
            throw new QUI\Database\Exception(
                $Exception->getMessage(),
                (int)$Exception->getCode()
            );
        }

        $SystemUser = QUI::getUsers()->getSystemUser();

        QUI::getUsers()->get(0)->save($SystemUser);
        QUI::getUsers()->get(5)->save($SystemUser);
        QUI::getUsers()->get(QUI::conf('globals', 'rootuser'))->save($SystemUser);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private static function ensureColumnDefinition(
        Connection $Connection,
        string $tableName,
        string $columnName,
        string $type,
        array $options
    ): void {
        $SchemaManager = $Connection->createSchemaManager();

        if (!$SchemaManager->tablesExist([$tableName])) {
            return;
        }

        $Table = $SchemaManager->introspectTable($tableName);
        $Column = new \Doctrine\DBAL\Schema\Column(
            $columnName,
            \Doctrine\DBAL\Types\Type::getType($type),
            $options
        );

        if (!$Table->hasColumn($columnName)) {
            $SchemaManager->alterTable(new \Doctrine\DBAL\Schema\TableDiff($Table, addedColumns: [$Column]));
            return;
        }

        $SchemaManager->alterTable(new \Doctrine\DBAL\Schema\TableDiff(
            $Table,
            changedColumns: [$columnName => new \Doctrine\DBAL\Schema\ColumnDiff($Table->getColumn($columnName), $Column)]
        ));
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private static function hasPrimaryKey(Connection $Connection, string $table): bool
    {
        $Platform = $Connection->getDatabasePlatform();

        if ($Platform instanceof AbstractMySQLPlatform) {
            return (int)$Connection->fetchOne(
                "SELECT COUNT(*)
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                    AND CONSTRAINT_TYPE = 'PRIMARY KEY'",
                [$table]
            ) > 0;
        }

        if ($Platform instanceof PostgreSQLPlatform) {
            return (int)$Connection->fetchOne(
                "SELECT COUNT(*)
                FROM pg_index i
                JOIN pg_class t ON t.oid = i.indrelid
                JOIN pg_namespace n ON n.oid = t.relnamespace
                WHERE n.nspname = current_schema()
                    AND t.relname = ?
                    AND i.indisprimary",
                [$table]
            ) > 0;
        }

        return false;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private static function hasIndex(Connection $Connection, string $table, string $index): bool
    {
        $Platform = $Connection->getDatabasePlatform();

        if ($Platform instanceof AbstractMySQLPlatform) {
            return (int)$Connection->fetchOne(
                "SELECT COUNT(*)
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                    AND INDEX_NAME = ?",
                [$table, $index]
            ) > 0;
        }

        if ($Platform instanceof PostgreSQLPlatform) {
            return (int)$Connection->fetchOne(
                "SELECT COUNT(*)
                FROM pg_indexes
                WHERE schemaname = current_schema()
                    AND tablename = ?
                    AND indexname = ?",
                [$table, $index]
            ) > 0;
        }

        return false;
    }
}
