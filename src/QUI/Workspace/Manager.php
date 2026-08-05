<?php

/**
 * this file contains \QUI\Workspace\Manager
 */

namespace QUI\Workspace;

use Exception;
use QUI;
use QUI\Interfaces\Users\User;
use QUI\Utils\Security\Orthos;

use function array_merge;
use function dirname;
use function file_get_contents;
use function json_decode;
use function json_encode;
use function mb_strlen;
use function stripos;

/**
 * Workspace Manager
 * Saves / Edit / Creates workspaces
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Manager
{
    /**
     * Setup for the user workspaces
     */
    public static function setup(): void
    {
        try {
            $SchemaManager = QUI::getSchemaManager();
            $tableName = self::table();

            if ($SchemaManager->tablesExist([$tableName])) {
                $Table = $SchemaManager->introspectTable($tableName);

                if (!$Table->hasColumn('data')) {
                    return;
                }

                if ($Table->getColumn('data')->getType() instanceof \Doctrine\DBAL\Types\TextType) {
                    return;
                }

                $SchemaManager->alterTable(new \Doctrine\DBAL\Schema\TableDiff(
                    $Table,
                    changedColumns: [
                        'data' => new \Doctrine\DBAL\Schema\ColumnDiff(
                            $Table->getColumn('data'),
                            new \Doctrine\DBAL\Schema\Column(
                                'data',
                                \Doctrine\DBAL\Types\Type::getType('text'),
                                ['notnull' => false]
                            )
                        )
                    ]
                ));

                return;
            }

            $Table = new \Doctrine\DBAL\Schema\Table($tableName);
            $Table->addColumn('id', 'integer', ['autoincrement' => true]);
            $Table->addColumn('uid', 'string', ['length' => 50]);
            $Table->addColumn('title', 'text', ['notnull' => false]);
            $Table->addColumn('data', 'text', ['notnull' => false]);
            $Table->addColumn('minHeight', 'integer', ['notnull' => false]);
            $Table->addColumn('minWidth', 'integer', ['notnull' => false]);
            $Table->addColumn('standard', 'smallint', ['notnull' => false]);
            $Table->setPrimaryKey(['id']);

            $SchemaManager->createTable($Table);
        } catch (Exception | \Doctrine\DBAL\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }
    }

    public static function table(): string
    {
        return QUI::getDBTableName('users_workspaces');
    }

    /**
     * Deletes all Workspaces from users which are not admin users
     *
     * @throws QUI\Exception
     */
    public static function cleanup(): void
    {
        try {
            $entries = QUI::getQueryBuilder()
                ->select('id', 'uid')
                ->from(QUI\Utils\Doctrine::quoteIdentifier(self::table()))
                ->executeQuery()
                ->fetchAllAssociative();
        } catch (QUI\Exception | \Doctrine\DBAL\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());

            return;
        }

        foreach ($entries as $entry) {
            try {
                $User = QUI::getUsers()->get($entry['uid']);

                if (!QUI\Permissions\Permission::isAdmin($User)) {
                    QUI::getDataBaseConnection()->delete(
                        QUI\Utils\Doctrine::quoteIdentifier(self::table()),
                        ['id' => $entry['id']]
                    );
                }
            } catch (QUI\Exception | \Doctrine\DBAL\Exception $Exception) {
                if ($Exception->getCode() === 404) {
                    QUI::getDataBaseConnection()->delete(
                        QUI\Utils\Doctrine::quoteIdentifier(self::table()),
                        ['id' => $entry['id']]
                    );

                    continue;
                }

                QUI\System\Log::addError($Exception->getMessage(), [
                    'user-id' => $entry['uid']
                ]);
            }
        }
    }

    /**
     * Add a workspace
     *
     * @param User $User
     * @param string $title - title of the workspace
     * @param string $data - Workspace profile
     * @param integer $minHeight - minimum height of the workspace
     * @param integer $minWidth - minimum width of the workspace
     *
     * @return integer - new Workspace ID
     *
     * @throws QUI\Exception
     */
    public static function addWorkspace(
        User $User,
        string $title,
        string $data,
        int $minHeight,
        int $minWidth
    ): int {
        if (!QUI::getUsers()->isUser($User)) {
            throw new QUI\Exception('No user given');
        }

        if (!$User->canUseBackend()) {
            throw new QUI\Exception('User is no administrator user');
        }

        $title = Orthos::clear($title);

        $Connection = QUI::getDataBaseConnection();
        $Connection->insert(QUI\Utils\Doctrine::quoteIdentifier(self::table()), [
            'uid' => $User->getUUID(),
            'title' => $title,
            'data' => $data,
            'minHeight' => $minHeight,
            'minWidth' => $minWidth
        ]);

        return (int)$Connection->lastInsertId();
    }

    /**
     * Delete a workspace
     *
     * @param integer $id - Workspace ID
     * @param User $User - User of the Workspace
     */
    public static function deleteWorkspace(int $id, User $User): void
    {
        try {
            QUI::getDataBaseConnection()->delete(
                QUI\Utils\Doctrine::quoteIdentifier(self::table()),
                [
                    'uid' => $User->getUUID(),
                    'id' => $id
                ]
            );
        } catch (QUI\Exception | \Doctrine\DBAL\Exception $Exception) {
            QUI\System\Log::addError($Exception, [
                'trace' => $Exception->getTraceAsString()
            ]);
        }
    }

    /**
     * @return string[]
     *
     * @throws QUI\Database\Exception
     * @throws QUI\Exception
     */
    public static function getWorkspacesTitlesByUser(QUI\Users\User $User): array
    {
        $workspaces = self::getWorkspacesByUser($User);
        $result = [];

        foreach ($workspaces as $entry) {
            $result[] = $entry['title'];
        }

        return $result;
    }

    /**
     * Return the workspaces list from a user
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws QUI\Database\Exception
     * @throws QUI\Exception
     */
    public static function getWorkspacesByUser(User $User): array
    {
        // only administrators have a workspace
        if (!$User->canUseBackend()) {
            return [];
        }

        $result = self::fetchWorkspacesByUser($User);

        if (empty($result)) {
            QUI::getUsers()->setDefaultWorkspacesForUsers($User);

            $result = self::fetchWorkspacesByUser($User);
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected static function fetchWorkspacesByUser(User $User): array
    {
        $QueryBuilder = QUI::getQueryBuilder();

        return $QueryBuilder
            ->select('*')
            ->from(QUI\Utils\Doctrine::quoteIdentifier(self::table()))
            ->where($QueryBuilder->expr()->eq('uid', ':uid'))
            ->setParameter('uid', $User->getUUID())
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Saves a workspace
     *
     * @param array<string, mixed> $data
     *
     * @throws QUI\Exception
     */
    public static function saveWorkspace(QUI\Users\User $User, int $id, array $data = []): void
    {
        $workspace = self::getWorkspaceById($id, $User);

        if (isset($data['title'])) {
            $workspace['title'] = Orthos::clear($data['title']);
        }

        if (isset($data['minHeight'])) {
            $workspace['minHeight'] = (int)$data['minHeight'];
        }

        if (isset($data['minWidth'])) {
            $workspace['minWidth'] = (int)$data['minWidth'];
        }

        if (isset($data['data'])) {
            $data['data'] = json_decode($data['data'], true);
            $workspace['data'] = json_encode($data['data']);

            // text = 65535 single bytes chars,
            // but we have utf8, so we use max 20000, not perfect but better than nothing
            if (mb_strlen((string)$workspace['data']) > 20000) {
                throw new QUI\Exception('Could not save the workspace. Workspace is to big.');
            }
        }

        QUI::getDataBaseConnection()->update(
            QUI\Utils\Doctrine::quoteIdentifier(self::table()),
            $workspace,
            [
                'id' => $id,
                'uid' => $User->getUUID()
            ]
        );

        if (!isset($data['standard'])) {
            return;
        }

        if ((int)$data['standard'] !== 1) {
            return;
        }

        self::setStandardWorkspace($User, $id);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws QUI\Exception
     */
    public static function getWorkspaceById(int $id, QUI\Users\User $User): array
    {
        $QueryBuilder = QUI::getQueryBuilder();
        $result = $QueryBuilder
            ->select('*')
            ->from(QUI\Utils\Doctrine::quoteIdentifier(self::table()))
            ->where($QueryBuilder->expr()->eq('id', ':id'))
            ->andWhere($QueryBuilder->expr()->eq('uid', ':uid'))
            ->setParameter('id', $id)
            ->setParameter('uid', $User->getUUID())
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAllAssociative();

        if (!isset($result[0])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.workspace.not.found'
                ),
                404
            );
        }

        return $result[0];
    }

    /**
     * Set the workspace to the standard workspace
     *
     * @throws QUI\Database\Exception
     */
    public static function setStandardWorkspace(User $User, int $id): void
    {
        if (!QUI::getUsers()->isUser($User)) {
            return;
        }

        if (!QUI\Permissions\Permission::isAdmin($User)) {
            return;
        }

        if (!$User->canUseBackend()) {
            return;
        }

        // all to no standard
        QUI::getDataBaseConnection()->update(
            QUI\Utils\Doctrine::quoteIdentifier(self::table()),
            ['standard' => 0],
            ['uid' => $User->getUUID()]
        );

        // standard
        QUI::getDataBaseConnection()->update(
            QUI\Utils\Doctrine::quoteIdentifier(self::table()),
            ['standard' => 1],
            [
                'id' => $id,
                'uid' => $User->getUUID()
            ]
        );
    }

    /**
     * Return the available panels
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAvailablePanels(): array
    {
        $cache = 'quiqqer/package/quiqqer/core/available-panels';

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception) {
        }

        $panels = [];
        $xmlFiles = array_merge(
            [SYS_DIR . 'panels.xml'],
            QUI::getPackageManager()->getPackageXMLFiles('panels.xml')
        );

        foreach ($xmlFiles as $file) {
            $panels = array_merge(
                $panels,
                QUI\Utils\Text\XML::getPanelsFromXMLFile($file)
            );
        }

        try {
            QUI\Cache\Manager::set($cache, $panels);
        } catch (Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        return $panels;
    }

    public static function getTwoColumnDefault(): string
    {
        return file_get_contents(dirname(__FILE__, 2) . '/Users/workspaces/twoColumns.js');
    }

    public static function getThreeColumnDefault(): string
    {
        return file_get_contents(dirname(__FILE__, 2) . '/Users/workspaces/threeColumns.js');
    }
}
