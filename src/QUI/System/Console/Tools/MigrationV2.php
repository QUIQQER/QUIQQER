<?php

/**
 *
 * @author hen
 *
 */

namespace QUI\System\Console\Tools;

use Doctrine\DBAL\Exception;
use QUI;

use function array_filter;
use function count;
use function is_numeric;

/**
 * MailQueue Console Manager
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class MigrationV2 extends QUI\System\Console\Tool
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setName('quiqqer:migration-v2')
            ->setDescription('Migration QUIQQER V1 to V2');
    }

    /**
     * @throws Exception|QUI\Database\Exception
     */
    public function execute(): void
    {
        // messages
        $this->writeLn('- Update messages table');
        QUI::getDataBaseConnection()->executeStatement(
            'ALTER TABLE `' . QUI::getDBTableName('messages') . '` CHANGE `uid` `uid` VARCHAR(50) NULL DEFAULT NULL;'
        );

        // session
        $this->writeLn('- Update session table');
        QUI::getDataBaseConnection()->executeStatement(
            'ALTER TABLE `' . QUI::getDBTableName('sessions') . '` CHANGE `uid` `uid` VARCHAR(50) NULL DEFAULT NULL;'
        );


        $this->users();
        $this->groups();
        $this->projectSites();
        $this->media();
        $this->permissions();
        $this->workspaces();
        $this->loginLog();

        QUI::getEvents()->fireEvent('quiqqerMigrationV2', [$this]);
    }

    public function users(): void
    {
        $this->writeLn('- Update users table');

        $DataBase = QUI::getDataBase();
        $table = QUI\Users\Manager::table();

        // Patch strict
        $DataBase->getPDO()->exec(
            "ALTER TABLE `$table` 
            CHANGE `lastedit` `lastedit` DATETIME NULL DEFAULT NULL,
            CHANGE `expire` `expire` DATETIME NULL DEFAULT NULL,
            CHANGE `password` `password` VARCHAR(255) NOT NULL DEFAULT '',
            CHANGE `birthday` `birthday` DATE NULL DEFAULT NULL;
            "
        );

        try {
            $DataBase->getPDO()->exec(
                "
                UPDATE `$table` 
                SET lastedit = NULL 
                WHERE 
                    lastedit = '0000-00-00 00:00:00' OR 
                    lastedit = '';

                UPDATE `$table` 
                SET expire = NULL 
                WHERE 
                    expire = '0000-00-00 00:00:00' OR 
                    expire = '';

                UPDATE `$table` 
                SET birthday = NULL 
                WHERE 
                    birthday = '0000-00-00' OR 
                    birthday = '';
            "
            );
        } catch (\Exception) {
        }

        // uuid extreme indexes patch
        $Stmt = $DataBase->getPDO()->prepare(
            "SHOW INDEXES FROM `$table`
            WHERE 
                non_unique = 0 AND Key_name != 'PRIMARY';"
        );

        $Stmt->execute();

        $columns = $Stmt->fetchAll();
        $dropSql = [];

        foreach ($columns as $column) {
            if (str_starts_with($column['Key_name'], 'uuid_')) {
                $dropSql[] = "ALTER TABLE `users` DROP INDEX `{$column['Key_name']}`;";
            }
        }

        if (!empty($dropSql)) {
            try {
                // foreach because of PDO::MYSQL_ATTR_USE_BUFFERED_QUERY
                foreach ($dropSql as $sql) {
                    $Stmt = $DataBase->getPDO()->prepare($sql);
                    $Stmt->execute();
                }
            } catch (\Exception $Exception) {
                QUI\System\Log::writeRecursive($dropSql);
                QUI\System\Log::writeException($Exception);
            }
        }

        // users with no uuid
        $addressesWithoutUuid = QUI::getDataBase()->fetch([
            'from' => $table,
            'where' => [
                'uuid' => ''
            ]
        ]);

        foreach ($addressesWithoutUuid as $entry) {
            $DataBase->update($table, [
                'uuid' => QUI\Utils\Uuid::get()
            ], [
                'id' => $entry['id']
            ]);
        }

        $DataBase->table()->setUniqueColumns($table, 'uuid');

        // addresses
        $this->writeLn('- Migrate users addresses');

        $tableAddresses = QUI::getUsers()->tableAddress();
        $setAddressUuidColumnToUnique = false;

        if (!$DataBase->table()->existColumnInTable($tableAddresses, 'uuid')) {
            $DataBase->table()->addColumn(
                $tableAddresses,
                ['uuid' => 'VARCHAR(50) NOT NULL']
            );

            $setAddressUuidColumnToUnique = true;
        }

        if (!$DataBase->table()->existColumnInTable($tableAddresses, 'userUuid')) {
            $DataBase->table()->addColumn(
                $tableAddresses,
                ['userUuid' => 'VARCHAR(50) NOT NULL']
            );
        }

        $usersAddressColumn = $DataBase->table()->getColumn($table, 'address');

        if (!str_contains($usersAddressColumn['Type'], 'varchar')) {
            $sql = "ALTER TABLE `$table` MODIFY `address` VARCHAR(50) NOT NULL";
            $DataBase->execSQL($sql);
        }

        $addressesWithoutUuid = QUI::getDataBase()->fetch([
            'select' => ['id'],
            'from' => $tableAddresses,
            'where' => [
                'uuid' => ''
            ]
        ]);

        $this->writeLn('-- Found addresses without UUID: ' . count($addressesWithoutUuid));
        $this->writeLn('-- Start migration ...');

        foreach ($addressesWithoutUuid as $entry) {
            $addressUuid = QUI\Utils\Uuid::get();

            $DataBase->update($tableAddresses, [
                'uuid' => $addressUuid
            ], [
                'id' => $entry['id']
            ]);

            // Update references in users table
            $DataBase->update(
                $table,
                ['address' => $addressUuid],
                ['address' => $entry['id']]
            );
        }

        if ($setAddressUuidColumnToUnique) {
            $DataBase->table()->setUniqueColumns($tableAddresses, 'uuid');
        }

        $addressesWithoutUserUuid = QUI::getDataBase()->fetch([
            'select' => ['id', 'uid'],
            'from' => $tableAddresses,
            'where' => [
                'userUuid' => ''
            ]
        ]);

        $this->writeLn('-- Found addresses without user UUID: ' . count($addressesWithoutUserUuid));
        $this->writeLn('-- Start migration ...');

        foreach ($addressesWithoutUserUuid as $entry) {
            $result = $DataBase->fetch([
                'select' => ['uuid'],
                'from' => $table,
                'where' => [
                    'id' => $entry['uid']
                ],
                'limit' => 1
            ]);

            if (empty($result)) {
                $this->writeLn(
                    "-> Found orphaned address ID #{$entry['id']}. User #{$entry['uid']}" . " referenced by address does not exist.",
                    'yellow'
                );
                $this->resetColor();
                continue;
            }

            // Update user uuid
            $DataBase->update(
                $tableAddresses,
                ['userUuid' => $result[0]['uuid']],
                ['id' => $entry['id']]
            );
        }
    }

    public function groups(): void
    {
        $this->writeLn('- Migrate users table');


        // read database xml, because we need the newest groups db
        $dbFields = QUI\Utils\Text\XML::getDataBaseFromXml(OPT_DIR . 'quiqqer/core/database.xml');
        unset($dbFields['projects']);

        $dbFields['globals'] = array_filter($dbFields['globals'], static function ($entry): bool {
            return $entry['suffix'] === 'groups';
        });

        QUI\Utils\Text\XML::importDataBase($dbFields);

        $DataBase = QUI::getDataBase();
        $DataBase->execSQL(
            "ALTER TABLE `" . QUI\Groups\Manager::table() . "` CHANGE `parent` `parent` VARCHAR(50) NULL DEFAULT NULL;"
        );

        $Table = $DataBase->table();
        $Table->setPrimaryKey(QUI\Groups\Manager::table(), 'id');
        $Table->setIndex(QUI\Groups\Manager::table(), 'parent');


        // Guest
        $result = QUI::getDataBase()->fetch([
            'from' => QUI\Groups\Manager::table(),
            'where' => [
                'id' => 0
            ]
        ]);

        if (!isset($result[0])) {
            QUI\System\Log::addNotice('Guest Group does not exist.');

            QUI::getDataBase()->insert(QUI\Groups\Manager::table(), [
                'id' => 0,
                'name' => 'Guest'
            ]);

            QUI\System\Log::addNotice('Guest Group was created.');
        } else {
            QUI::getDataBase()->update(QUI\Groups\Manager::table(), [
                'name' => 'Guest'
            ], [
                'id' => 0
            ]);

            QUI\System\Log::addNotice('Guest exists only updated');
        }


        // Everyone
        $result = QUI::getDataBase()->fetch([
            'from' => QUI\Groups\Manager::table(),
            'where' => [
                'id' => 1
            ]
        ]);

        if (!isset($result[0])) {
            QUI\System\Log::addNotice('Everyone Group does not exist...');

            QUI::getDataBase()->insert(QUI\Groups\Manager::table(), [
                'id' => 1,
                'name' => 'Everyone'
            ]);

            QUI\System\Log::addNotice('Everyone Group was created.');
        } else {
            QUI::getDataBase()->update(QUI\Groups\Manager::table(), [
                'name' => 'Everyone'
            ], [
                'id' => 1
            ]);

            QUI\System\Log::addNotice('Everyone exists');
        }

        QUI::getUsers()->get(0)->save();
        QUI::getUsers()->get(5)->save();
        QUI::getUsers()->get(QUI::conf('globals', 'rootuser'))->save();
    }

    public function projectSites(): void
    {
        $this->writeLn('- Migrate project & sites');
        $projects = QUI::getProjectManager()->getProjects(true);

        /* @var $Project QUI\Projects\Project */
        foreach ($projects as $Project) {
            $Project->setup([
                'executePackagesSetup' => false
            ]);

            $languages = $Project->getLanguages();
            $name = $Project->getName();

            foreach ($languages as $language) {
                $table = QUI::getProject($name, $language)->table();
                $sites = QUI::getDataBase()->fetch([
                    'from' => $table
                ]);

                foreach ($sites as $site) {
                    $cUser = $site['c_user'];
                    $eUser = $site['e_user'];

                    if (is_numeric($cUser)) {
                        $cUser = $this->getUserHash($cUser);
                    }

                    if (is_numeric($eUser)) {
                        $eUser = $this->getUserHash($eUser);
                    }

                    QUI::getDataBase()->update(
                        $table,
                        [
                            'c_user' => $cUser,
                            'e_user' => $eUser,
                        ],
                        ['id' => $site['id']]
                    );
                }
            }
        }
    }

    public function media(): void
    {
        $this->writeLn('- Migrate media');
        $projects = QUI::getProjectManager()->getProjects(true);

        /* @var $Project QUI\Projects\Project */
        foreach ($projects as $Project) {
            $Media = $Project->getMedia();
            $table = $Media->getTable();

            $files = QUI::getDataBase()->fetch([
                'from' => $table
            ]);

            foreach ($files as $file) {
                $cUser = $file['c_user'];
                $eUser = $file['e_user'];

                if (is_numeric($cUser)) {
                    $cUser = $this->getUserHash($cUser);
                }

                if (is_numeric($eUser)) {
                    $eUser = $this->getUserHash($eUser);
                }

                QUI::getDataBase()->update(
                    $table,
                    [
                        'c_user' => $cUser,
                        'e_user' => $eUser,
                    ],
                    ['id' => $file['id']]
                );
            }
        }
    }

    /**
     * @throws Exception
     * @throws QUI\Database\Exception
     */
    public function permissions(): void
    {
        $this->writeLn('- Migrate permissions');

        $table2Users = QUI::getDBTableName('permissions2users');
        $table2Groups = QUI::getDBTableName('permissions2groups');

        QUI::getDataBaseConnection()->executeStatement(
            'ALTER TABLE `' . $table2Users . '` CHANGE `user_id` `user_id` VARCHAR(50) NOT NULL DEFAULT \'0\';'
        );

        QUI::getDataBaseConnection()->executeStatement(
            'ALTER TABLE `' . $table2Groups . '` CHANGE `group_id` `group_id` VARCHAR(50) NOT NULL DEFAULT \'0\';'
        );


        $permissions = QUI::getDataBase()->fetch([
            'from' => $table2Users
        ]);

        foreach ($permissions as $entry) {
            if (!is_numeric($entry['user_id'])) {
                continue;
            }

            try {
                $userUUID = QUI::getUsers()->get($entry['user_id'])->getUUID();
            } catch (QUI\Exception) {
                // nutzer existiert nicht, kann als permission gelöscht werden
                QUI::getDataBase()->delete($table2Users, [
                    'user_id' => $entry['user_id']
                ]);

                continue;
            }

            QUI::getDataBase()->insert($table2Users, [
                'user_id' => $userUUID,
                'permissions' => $entry['permissions']
            ]);

            QUI::getDataBase()->delete($table2Users, [
                'user_id' => $entry['user_id']
            ]);
        }


        $permissions = QUI::getDataBase()->fetch([
            'from' => $table2Groups
        ]);

        foreach ($permissions as $entry) {
            if (!is_numeric($entry['group_id'])) {
                continue;
            }

            try {
                $groupUUID = QUI::getGroups()->get($entry['group_id'])->getUUID();
            } catch (\Exception) {
                // gruppe existiert nicht, kann als permission gelöscht werden

                QUI::getDataBase()->delete($table2Groups, [
                    'group_id' => $entry['group_id']
                ]);
                continue;
            }

            if ($groupUUID == $entry['group_id']) {
                continue;
            }

            QUI::getDataBase()->insert($table2Groups, [
                'group_id' => $groupUUID,
                'permissions' => $entry['permissions']
            ]);

            QUI::getDataBase()->delete($table2Groups, [
                'group_id' => $entry['group_id']
            ]);
        }
    }

    /**
     * @throws Exception
     * @throws QUI\Database\Exception
     */
    public function workspaces(): void
    {
        $this->writeLn('- Migrate workspaces');

        $table = QUI::getDBTableName('users_workspaces');

        QUI::getDataBaseConnection()->executeStatement(
            'ALTER TABLE `' . $table . '` CHANGE `uid` `uid` VARCHAR(50) NOT NULL;'
        );

        $workspaces = QUI::getDataBase()->fetch([
            'from' => $table
        ]);

        foreach ($workspaces as $workspace) {
            try {
                $uuid = QUI::getUsers()->get($workspace['uid'])->getUUID();
            } catch (QUI\Exception) {
                QUI::getDataBase()->delete($table, [
                    'id' => $workspace['id']
                ]);
                continue;
            }

            QUI::getDataBase()->update(
                $table,
                ['uid' => $uuid],
                ['id' => $workspace['id']]
            );
        }
    }

    public function loginLog(): void
    {
        $this->writeLn('- Migrate login log table');
        QUI::getDataBaseConnection()->executeStatement(
            'ALTER TABLE `' . QUI::getDBTableName('login_log') . '` CHANGE `uid` `uid` VARCHAR(50) NOT NULL;'
        );
    }

    protected function getUserHash(int|string $userId): string|int
    {
        try {
            return QUI::getUsers()->get($userId)->getUUID();
        } catch (QUI\Exception) {
            return $userId;
        }
    }
}
