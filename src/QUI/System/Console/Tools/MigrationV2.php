<?php

namespace QUI\System\Console\Tools;

use Doctrine\DBAL\Exception;
use QUI;
use QUI\ExceptionStack;

use function array_chunk;
use function array_fill;
use function array_merge;
use function array_unique;
use function array_filter;
use function count;
use function explode;
use function implode;
use function is_array;
use function is_numeric;
use function json_decode;
use function sprintf;
use function trim;
use function var_dump;

use const OPT_DIR;

/**
 * MailQueue Console Manager
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
     * @throws Exception
     * @throws QUI\Database\Exception
     * @throws QUI\Exception
     * @throws ExceptionStack
     */
    public function execute(): void
    {
        // messages
        $this->writeLn('- Update messages table');
        QUI::getDataBaseConnection()->executeStatement(
            'ALTER TABLE `' . QUI::getDBTableName('messages') . '` CHANGE `uid` `uid` VARCHAR(50);'
        );

        // session
        $this->writeLn('- Update session table');
        QUI::getDataBaseConnection()->executeStatement(
            'ALTER TABLE `' . QUI::getDBTableName('sessions') . '` CHANGE `uid` `uid` VARCHAR(50);'
        );


        $this->users();
        $this->groups();
        $this->groupsInUsers();
        $this->projectSites();
        $this->media();
        $this->permissions();
        $this->workspaces();
        $this->loginLog();

        $this->writeLn('- Migrate root user and root group');

        $Config = QUI::getConfig('etc/conf.ini.php');
        $rootUser = $Config->getValue('globals', 'rootuser');
        $rootGroup = $Config->getValue('globals', 'root');

        try {
            $Config->setValue('globals', 'rootuser', QUI::getUsers()->get($rootUser)->getUUID());
        } catch (QUI\Exception) {
        }

        try {
            $Config->setValue('globals', 'root', QUI::getGroups()->get($rootGroup)->getUUID());
        } catch (QUI\Exception) {
        }

        $Config->save();

        QUI::getEvents()->fireEvent('quiqqerMigrationV2', [$this]);


        // migrate databases to innodb
        try {
            $this->writeLn('- Migrate database to MyISAM');

            $conn = QUI::getDataBaseConnection();
            $dbname = QUI::conf('db', 'database');

            // Alle MyISAM-Tabellen abrufen
            $sql = "
            SELECT table_name 
            FROM information_schema.tables 
            WHERE 
                table_schema = :dbname AND engine = 'MyISAM'
            ";

            $stmt = $conn->prepare($sql);
            $stmt->bindValue('dbname', $dbname);

            $result = $stmt->executeQuery();
            $tables = $result->fetchAllAssociative();

            // Speicher-Engine jeder Tabelle ändern
            foreach ($tables as $table) {
                try {
                    $tableName = $table['table_name'] ?? $table['TABLE_NAME'];
                    $conn->executeStatement("ALTER TABLE `$tableName` ENGINE=InnoDB;");
                    $this->writeLn('-> Converted ' . $tableName . ' to InnoDB');
                } catch (\Exception $exception) {
                    $this->writeLn('Error at table ' . $tableName, 'red');
                    $this->writeLn($exception->getMessage(), 'red');
                    $this->resetColor();
                }
            }

            $this->writeLn('-> Conversion complete');
        } catch (Exception $e) {
            $this->writeLn('An error occurred: ' . $e->getMessage());
        }

        $this->writeLn('Migration complete!', 'green');
        $this->resetColor();
    }

    public function users(): void
    {
        $this->writeLn('- Update users table');

        QUI\Users\Install::user();

        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();
        $userTable = QUI\Users\Manager::table();

        // uuid extreme indexes patch
        $columns = $Connection->executeQuery(
            'SHOW INDEXES FROM ' . QUI\Utils\Doctrine::quoteIdentifier($userTable) . "
            WHERE non_unique = 0 AND Key_name != 'PRIMARY'"
        )->fetchAllAssociative();
        $dropSql = [];

        foreach ($columns as $column) {
            if (str_starts_with($column['Key_name'], 'uuid_')) {
                $dropSql[] = 'ALTER TABLE ' . QUI\Utils\Doctrine::quoteIdentifier($userTable)
                    . ' DROP INDEX ' . $Platform->quoteSingleIdentifier($column['Key_name']);
            }
        }

        if (!empty($dropSql)) {
            try {
                foreach ($dropSql as $sql) {
                    $Connection->executeStatement($sql);
                }
            } catch (\Exception $Exception) {
                QUI\System\Log::writeRecursive($dropSql);
                QUI\System\Log::writeException($Exception);
            }
        }

        // users with no uuid
        $usersWithoutUuid = $this->fetchRows($userTable, ['uuid' => '']);

        foreach ($usersWithoutUuid as $entry) {
            $this->updateRows(
                $userTable,
                ['uuid' => QUI\Utils\Uuid::get()],
                ['id' => $entry['id']]
            );
        }

        $this->ensureUniqueColumn($userTable, 'uuid');

        // addresses
        $this->writeLn('- Migrate users addresses');

        $tableAddresses = QUI\Users\Manager::tableAddress();
        $setAddressUuidColumnToUnique = false;

        if (!$this->columnExists($tableAddresses, 'uuid')) {
            $this->addVarcharColumn($tableAddresses, 'uuid');
            $setAddressUuidColumnToUnique = true;
        }

        if (!$this->columnExists($tableAddresses, 'userUuid')) {
            $this->addVarcharColumn($tableAddresses, 'userUuid');
        }

        if (!$this->isColumnVarchar($userTable, 'address')) {
            $Connection->executeStatement(
                'ALTER TABLE ' . QUI\Utils\Doctrine::quoteIdentifier($userTable)
                . ' MODIFY ' . $Platform->quoteSingleIdentifier('address') . ' VARCHAR(50) NOT NULL'
            );
        }

        $addressesWithoutUuid = $this->fetchRows($tableAddresses, ['uuid' => ''], ['id']);

        $this->writeLn('-- Found addresses without UUID: ' . count($addressesWithoutUuid));
        $this->writeLn('-- Start migration ...');

        foreach ($addressesWithoutUuid as $entry) {
            $this->updateRows(
                $tableAddresses,
                ['uuid' => QUI\Utils\Uuid::get()],
                ['id' => $entry['id']]
            );
        }

        // MIGRATE DEFAULT ADDRESS
        $users = $this->fetchRows($userTable);

        foreach ($users as $user) {
            $standardAddress = $user['address'];

            if (is_numeric($standardAddress)) {
                $addressData = $this->fetchRows($tableAddresses, ['id' => $standardAddress], ['uuid'], 1);

                if (!count($addressData)) {
                    continue;
                }

                $this->updateRows(
                    $userTable,
                    ['address' => $addressData[0]['uuid']],
                    ['id' => $user['id']]
                );
            }
        }

        if ($setAddressUuidColumnToUnique) {
            $this->ensureUniqueColumn($tableAddresses, 'uuid');
        }

        $addressesWithoutUserUuid = $this->fetchRows(
            $tableAddresses,
            ['userUuid' => ''],
            ['id', 'uid']
        );

        $this->writeLn('-- Found addresses without user UUID: ' . count($addressesWithoutUserUuid));
        $this->writeLn('-- Start migration ...');

        foreach ($addressesWithoutUserUuid as $entry) {
            $result = $this->fetchRows($userTable, ['id' => $entry['uid']], ['uuid'], 1);

            if (empty($result)) {
                $this->writeLn(
                    "-> Found orphaned address ID #{$entry['id']}. User #{$entry['uid']} referenced by address does not exist.",
                    'yellow'
                );
                $this->resetColor();
                continue;
            }

            $this->updateRows(
                $tableAddresses,
                ['userUuid' => $result[0]['uuid']],
                ['id' => $entry['id']]
            );
        }
    }
    public function groups(): void
    {
        $this->writeLn('- Migrate groups table');
        try {
            QUI\Users\Install::groups();
        } catch (QUI\Exception $exception) {
            if ((int)$exception->getCode() !== 404) {
                throw $exception;
            }

            $this->writeLn($exception->getMessage(), 'yellow');
            $this->resetColor();
        }


        // migrate group parents
        $Root = QUI::getGroups()->get(QUI::conf('globals', 'root'));
        $rootUUID = $Root->getUUID();
        $groupTable = QUI\Groups\Manager::table();

        $result = $this->fetchRows($groupTable);

        foreach ($result as $entry) {
            $groupUUID = $entry['uuid'];

            if ($groupUUID == 0 || $groupUUID == 1 || $groupUUID == $rootUUID) {
                continue;
            }

            $parent = $entry['parent'];

            try {
                if ($parent == 0) {
                    $this->updateRows(
                        $groupTable,
                        ['parent' => $rootUUID],
                        ['id' => $entry['id']]
                    );
                } else {
                    $this->updateRows(
                        $groupTable,
                        ['parent' => QUI::getGroups()->get($parent)->getUUID()],
                        ['id' => $entry['id']]
                    );
                }
            } catch (QUI\Exception $exception) {
                $this->writeLn($exception->getMessage(), 'red');
                $this->resetColor();
            }
        }
    }

    public function groupsInUsers(): void
    {
        $this->writeLn('- Migrate groups in users');
        $table = QUI\Users\Manager::table();

        $result = $this->fetchRows($table);

        foreach ($result as $entry) {
            $userGroups = $entry['usergroup'];
            $newGroups = [];

            $userGroups = trim($userGroups, ',');
            $userGroups = explode(',', $userGroups);

            foreach ($userGroups as $groupId) {
                try {
                    $newGroups[] = QUI::getGroups()->get($groupId)->getUUID();
                } catch (QUI\Exception) {
                    $newGroups[] = $groupId;
                }
            }

            try {
                $this->updateRows(
                    $table,
                    ['usergroup' => ',' . implode(',', $newGroups) . ','],
                    ['id' => $entry['id']]
                );
            } catch (QUI\Exception) {
            }
        }
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
                $sites = $this->fetchRows($table);

                foreach ($sites as $site) {
                    $cUser = $site['c_user'];
                    $eUser = $site['e_user'];

                    if (is_numeric($cUser)) {
                        $cUser = $this->getUserHash($cUser);
                    }

                    if (is_numeric($eUser)) {
                        $eUser = $this->getUserHash($eUser);
                    }

                    $this->updateRows(
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

            $files = $this->fetchRows($table);

            foreach ($files as $file) {
                $cUser = $file['c_user'];
                $eUser = $file['e_user'];

                if (is_numeric($cUser)) {
                    $cUser = $this->getUserHash($cUser);
                }

                if (is_numeric($eUser)) {
                    $eUser = $this->getUserHash($eUser);
                }

                $this->updateRows(
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


        $permissions = $this->fetchRows($table2Users);

        foreach ($permissions as $entry) {
            if (!is_numeric($entry['user_id'])) {
                continue;
            }

            try {
                $userUUID = QUI::getUsers()->get($entry['user_id'])->getUUID();
            } catch (QUI\Exception) {
                // nutzer existiert nicht, kann als permission gelöscht werden
                QUI::getDataBaseConnection()->delete(QUI\Utils\Doctrine::quoteIdentifier($table2Users), [
                    'user_id' => $entry['user_id']
                ]);

                continue;
            }

            QUI::getDataBaseConnection()->insert(QUI\Utils\Doctrine::quoteIdentifier($table2Users), [
                'user_id' => $userUUID,
                'permissions' => $entry['permissions']
            ]);

            QUI::getDataBaseConnection()->delete(QUI\Utils\Doctrine::quoteIdentifier($table2Users), [
                'user_id' => $entry['user_id']
            ]);
        }


        $permissions = $this->fetchRows($table2Groups);

        foreach ($permissions as $entry) {
            if (!is_numeric($entry['group_id'])) {
                continue;
            }

            try {
                $groupUUID = QUI::getGroups()->get($entry['group_id'])->getUUID();
            } catch (\Exception) {
                // gruppe existiert nicht, kann als permission gelöscht werden

                QUI::getDataBaseConnection()->delete(QUI\Utils\Doctrine::quoteIdentifier($table2Groups), [
                    'group_id' => $entry['group_id']
                ]);
                continue;
            }

            if ($groupUUID == $entry['group_id']) {
                continue;
            }

            try {
                QUI::getDataBaseConnection()->insert(QUI\Utils\Doctrine::quoteIdentifier($table2Groups), [
                    'group_id' => $groupUUID,
                    'permissions' => $entry['permissions']
                ]);

                QUI::getDataBaseConnection()->delete(QUI\Utils\Doctrine::quoteIdentifier($table2Groups), [
                    'group_id' => $entry['group_id']
                ]);
            } catch (\Exception) {
            }
        }
    }

    /**
     * @throws Exception
     */
    public function workspaces(): void
    {
        $this->writeLn('- Migrate workspaces');
        $this->writeLn('> Cleanup workspaces');

        $workspaceTable = QUI\Workspace\Manager::table();
        $workspaceUsers = $this->getWorkspaceUsers($workspaceTable);
        $this->writeLn('>> Found workspace owners: ' . count($workspaceUsers));

        $userMap = $this->getWorkspaceUserMap($workspaceUsers);
        $invalidWorkspaceUsers = [];

        foreach ($workspaceUsers as $uid) {
            if (!isset($userMap[$uid])) {
                $invalidWorkspaceUsers[] = $uid;
                continue;
            }

            if ($userMap[$uid]['admin'] !== true) {
                $invalidWorkspaceUsers[] = $uid;
            }
        }

        $this->writeLn('>> Workspace owner analysis complete');
        $this->writeLn('>> Invalid workspace owners queued for cleanup: ' . count($invalidWorkspaceUsers));

        if (!empty($invalidWorkspaceUsers)) {
            $this->writeLn('>> Start deleting workspaces for invalid owners');
        }

        $deletedWorkspaces = $this->deleteWorkspacesByUids($workspaceTable, $invalidWorkspaceUsers);

        if ($deletedWorkspaces > 0) {
            $this->writeLn('>> Deleted workspaces: ' . $deletedWorkspaces);
        }

        $this->writeLn('> Upgrade workspaces');
        $table = QUI::getDBTableName('users_workspaces');

        QUI::getDataBaseConnection()->executeStatement(
            'ALTER TABLE `' . $table . '` CHANGE `uid` `uid` VARCHAR(50) NOT NULL;'
        );
        $userTable = QUI\Users\Manager::table();

        $updatedWorkspaces = QUI::getDataBaseConnection()->executeStatement(
            'UPDATE `' . $table . '` workspace
            INNER JOIN `' . $userTable . '` users
                ON workspace.`uid` = CAST(users.`id` AS CHAR)
            SET workspace.`uid` = users.`uuid`
            WHERE workspace.`uid` REGEXP \'^[0-9]+$\' AND workspace.`uid` != \'5\''
        );

        if ($updatedWorkspaces > 0) {
            $this->writeLn('>> Upgraded workspace owners: ' . $updatedWorkspaces);
        }
    }

    /**
     * @throws Exception
     */
    public function loginLog(): void
    {
        $this->writeLn('- Migrate login log table');
        QUI::getDataBaseConnection()->executeStatement(
            'ALTER TABLE `' . QUI::getDBTableName('login_log') . '` CHANGE `uid` `uid` VARCHAR(50) NOT NULL;'
        );
    }

    protected function fetchRows(
        string $table,
        array $where = [],
        array $select = ['*'],
        ?int $limit = null
    ): array {
        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();
        $QueryBuilder = $Connection->createQueryBuilder();
        $columns = [];

        foreach ($select as $column) {
            $columns[] = $column === '*' ? '*' : $Platform->quoteSingleIdentifier($column);
        }

        $QueryBuilder
            ->select(...$columns)
            ->from(QUI\Utils\Doctrine::quoteIdentifier($table));

        $index = 0;

        foreach ($where as $field => $value) {
            $parameter = 'where' . $index;
            $QueryBuilder
                ->andWhere($QueryBuilder->expr()->eq($Platform->quoteSingleIdentifier($field), ':' . $parameter))
                ->setParameter($parameter, $value);
            $index++;
        }

        if ($limit !== null) {
            $QueryBuilder->setMaxResults($limit);
        }

        return $QueryBuilder->executeQuery()->fetchAllAssociative();
    }

    protected function updateRows(string $table, array $data, array $where): void
    {
        QUI::getDataBaseConnection()->update(
            QUI\Utils\Doctrine::quoteIdentifier($table),
            $data,
            $where
        );
    }

    protected function columnExists(string $table, string $column): bool
    {
        return QUI::getSchemaManager()
            ->introspectTable($table)
            ->hasColumn($column);
    }

    protected function addVarcharColumn(string $table, string $column): void
    {
        $Platform = QUI::getDataBaseConnection()->getDatabasePlatform();

        QUI::getDataBaseConnection()->executeStatement(
            'ALTER TABLE ' . QUI\Utils\Doctrine::quoteIdentifier($table)
            . ' ADD ' . $Platform->quoteSingleIdentifier($column) . ' VARCHAR(50) NOT NULL'
        );
    }

    protected function ensureUniqueColumn(string $table, string $column): void
    {
        $Table = QUI::getSchemaManager()->introspectTable($table);

        foreach ($Table->getIndexes() as $Index) {
            if (!$Index->isUnique()) {
                continue;
            }

            if ($Index->getColumns() === [$column]) {
                return;
            }
        }

        $Platform = QUI::getDataBaseConnection()->getDatabasePlatform();
        $indexName = $table . '_' . $column . '_uniq';

        QUI::getDataBaseConnection()->executeStatement(
            'CREATE UNIQUE INDEX ' . $Platform->quoteSingleIdentifier($indexName)
            . ' ON ' . QUI\Utils\Doctrine::quoteIdentifier($table)
            . ' (' . $Platform->quoteSingleIdentifier($column) . ')'
        );
    }

    protected function isColumnVarchar(string $table, string $column): bool
    {
        $type = QUI::getSchemaManager()
            ->introspectTable($table)
            ->getColumn($column)
            ->getType();

        return $type instanceof \Doctrine\DBAL\Types\StringType || $type instanceof \Doctrine\DBAL\Types\TextType;
    }

    protected function getUserHash(int | string $userId): string | int
    {
        try {
            return QUI::getUsers()->get($userId)->getUUID();
        } catch (QUI\Exception) {
            return $userId;
        }
    }

    /**
     * @throws Exception
     */
    protected function getWorkspaceUsers(string $workspaceTable): array
    {
        $result = QUI::getDataBaseConnection()->executeQuery(
            'SELECT DISTINCT `uid` FROM `' . $workspaceTable . '`'
        );

        $workspaceUsers = [];

        while ($entry = $result->fetchAssociative()) {
            if (!isset($entry['uid'])) {
                continue;
            }

            $workspaceUsers[] = (string)$entry['uid'];
        }

        return array_values(array_unique($workspaceUsers));
    }

    /**
     * @throws Exception
     */
    protected function getWorkspaceUserMap(array $workspaceUsers): array
    {
        $workspaceUsers = array_values(array_unique(array_map('strval', $workspaceUsers)));

        if (empty($workspaceUsers)) {
            return [];
        }

        $numericUserIds = [];
        $uuidUserIds = [];

        foreach ($workspaceUsers as $uid) {
            if ($uid === '5') {
                continue;
            }

            if (is_numeric($uid)) {
                $numericUserIds[] = (string)$uid;
                continue;
            }

            $uuidUserIds[] = $uid;
        }

        $users = $this->fetchWorkspaceUsers($numericUserIds, $uuidUserIds);
        $adminUserUUIDs = $this->getAdminWorkspaceUserUUIDs($users);
        $userMap = [
            '5' => [
                'uuid' => '5',
                'admin' => true
            ]
        ];

        foreach ($users as $user) {
            $isAdmin = (int)$user['su'] === 1 || isset($adminUserUUIDs[$user['uuid']]);

            $payload = [
                'uuid' => $user['uuid'],
                'admin' => $isAdmin
            ];

            $userMap[(string)$user['id']] = $payload;
            $userMap[$user['uuid']] = $payload;
        }

        return $userMap;
    }

    /**
     * @throws Exception
     */
    protected function fetchWorkspaceUsers(array $numericUserIds, array $uuidUserIds): array
    {
        $userTable = QUI\Users\Manager::table();
        $conn = QUI::getDataBaseConnection();
        $users = [];
        $processedNumericUserIds = 0;
        $totalUserIds = count(array_unique($numericUserIds));
        $totalUserUUIDs = count(array_unique($uuidUserIds));

        if ($totalUserIds > 0) {
            $this->writeLn('>> Load workspace owners by numeric id');
        }

        foreach (array_chunk(array_values(array_unique($numericUserIds)), 1000) as $idChunk) {
            if (empty($idChunk)) {
                continue;
            }

            $placeholders = implode(',', array_fill(0, count($idChunk), '?'));
            $result = $conn->executeQuery(
                'SELECT `id`, `uuid`, `usergroup`, `su`
                FROM `' . $userTable . '`
                WHERE `id` IN (' . $placeholders . ')',
                $idChunk
            );

            while ($entry = $result->fetchAssociative()) {
                $users[$entry['uuid']] = $entry;
            }

            $processedNumericUserIds += count($idChunk);

            $this->writeLn(
                sprintf(
                    '>> Processed numeric owner ids: %d/%d',
                    min($processedNumericUserIds, $totalUserIds),
                    $totalUserIds
                )
            );
        }

        $processedUuidUsers = 0;

        if ($totalUserUUIDs > 0) {
            $this->writeLn('>> Load workspace owners by uuid');
        }

        foreach (array_chunk(array_values(array_unique($uuidUserIds)), 1000) as $uuidChunk) {
            if (empty($uuidChunk)) {
                continue;
            }

            $placeholders = implode(',', array_fill(0, count($uuidChunk), '?'));
            $result = $conn->executeQuery(
                'SELECT `id`, `uuid`, `usergroup`, `su`
                FROM `' . $userTable . '`
                WHERE `uuid` IN (' . $placeholders . ')',
                $uuidChunk
            );

            while ($entry = $result->fetchAssociative()) {
                $users[$entry['uuid']] = $entry;
            }

            $processedUuidUsers += count($uuidChunk);

            $this->writeLn(
                sprintf(
                    '>> Processed uuid owners: %d/%d',
                    min($processedUuidUsers, $totalUserUUIDs),
                    $totalUserUUIDs
                )
            );
        }

        return $users;
    }

    protected function getAdminWorkspaceUserUUIDs(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $adminUsers = [];
        $userUUIDs = [];
        $groupUUIDs = [];
        $groupsByUser = [];

        foreach ($users as $user) {
            $userUUID = $user['uuid'];
            $userUUIDs[] = $userUUID;

            if ((int)$user['su'] === 1) {
                $adminUsers[$userUUID] = true;
            }

            $groupsByUser[$userUUID] = $this->parseUserGroups($user['usergroup'] ?? '');
            $groupUUIDs = array_merge($groupUUIDs, $groupsByUser[$userUUID]);
        }

        $this->writeLn('>> Start admin owner resolution for ' . count($users) . ' users');
        $this->writeLn('>> Check direct admin permissions for users');

        $adminUsers = array_merge(
            $adminUsers,
            $this->getAdminSubjects(
                QUI::getDBTableName('permissions2users'),
                'user_id',
                $userUUIDs
            )
        );

        $this->writeLn('>> Check admin permissions for groups');

        $adminGroups = $this->getAdminSubjects(
            QUI::getDBTableName('permissions2groups'),
            'group_id',
            $groupUUIDs
        );

        foreach ($groupsByUser as $userUUID => $groups) {
            if (isset($adminUsers[$userUUID])) {
                continue;
            }

            foreach ($groups as $groupUUID) {
                if (!isset($adminGroups[$groupUUID])) {
                    continue;
                }

                $adminUsers[$userUUID] = true;
                break;
            }
        }

        $this->writeLn('>> Resolved admin workspace owners: ' . count($adminUsers));

        return $adminUsers;
    }

    protected function getAdminSubjects(
        string $table,
        string $idColumn,
        array $ids
    ): array {
        $ids = array_values(array_unique(array_filter($ids)));

        if (empty($ids)) {
            return [];
        }

        $conn = QUI::getDataBaseConnection();
        $adminSubjects = [];
        $resolvedIds = 0;
        $totalIds = count($ids);

        foreach (array_chunk($ids, 1000) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $result = $conn->executeQuery(
                'SELECT `' . $idColumn . '`, `permissions`
                FROM `' . $table . '`
                WHERE `' . $idColumn . '` IN (' . $placeholders . ')',
                $chunk
            );

            $resolvedIds += count($chunk);

            while ($entry = $result->fetchAssociative()) {
                if (!$this->hasAdminPermission($entry['permissions'] ?? '')) {
                    continue;
                }

                $adminSubjects[$entry[$idColumn]] = true;
            }

            $this->writeLn(
                sprintf(
                    '>> Checked %s permissions: %d/%d',
                    $idColumn,
                    min($resolvedIds, $totalIds),
                    $totalIds
                )
            );
        }

        return $adminSubjects;
    }

    protected function hasAdminPermission(string $permissions): bool
    {
        $permissions = json_decode($permissions, true);

        if (!is_array($permissions)) {
            return false;
        }

        return !empty($permissions['quiqqer.admin']);
    }

    protected function parseUserGroups(string $userGroups): array
    {
        $userGroups = trim($userGroups, ',');

        if ($userGroups === '') {
            return [];
        }

        return array_values(array_filter(explode(',', $userGroups)));
    }

    protected function deleteWorkspacesByUids(string $workspaceTable, array $uids): int
    {
        $uids = array_values(array_unique(array_filter($uids)));

        if (empty($uids)) {
            return 0;
        }

        $conn = QUI::getDataBaseConnection();
        $deleted = 0;
        $processed = 0;
        $total = count($uids);

        $this->writeLn('>> Cleanup delete batches: ' . $total . ' owner ids');

        foreach (array_chunk($uids, 500) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));

            $deleted += $conn->executeStatement(
                'DELETE FROM `' . $workspaceTable . '`
                WHERE `uid` IN (' . $placeholders . ')',
                $chunk
            );

            $processed += count($chunk);

            $this->writeLn(
                sprintf(
                    '>> Cleanup batches: %d/%d owner ids processed, %d workspaces deleted',
                    min($processed, $total),
                    $total,
                    $deleted
                )
            );
        }

        return $deleted;
    }
}
