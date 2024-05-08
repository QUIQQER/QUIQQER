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
        $Table = QUI::getDataBase()->table();

        if ($Table->existColumnInTable(self::table(), 'data')) {
            $column = $Table->getColumn(self::table(), 'data');

            if (stripos($column['Type'], 'text') !== false) {
                return;
            }
        }

        try {
            $Table->addColumn(self::table(), [
                'id' => 'int(11) NOT NULL',
                'uid' => 'int(11) NOT NULL',
                'title' => 'text',
                'data' => 'text',
                'minHeight' => 'int',
                'minWidth' => 'int',
                'standard' => 'int(1)'
            ]);

            $Table->setAutoIncrement(self::table(), 'id');
            $Table->setPrimaryKey(self::table(), 'id');
        } catch (Exception $Exception) {
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
            $entries = QUI::getDataBase()->fetch([
                'from' => self::table()
            ]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());

            return;
        }

        foreach ($entries as $entry) {
            try {
                $User = QUI::getUsers()->get($entry['uid']);

                if (!QUI\Permissions\Permission::isAdmin($User)) {
                    QUI::getDataBase()->delete(self::table(), [
                        'id' => $entry['id']
                    ]);
                }
            } catch (QUI\Exception $Exception) {
                if ($Exception->getCode() === 404) {
                    QUI::getDataBase()->delete(self::table(), [
                        'id' => $entry['id']
                    ]);

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

        $title = Orthos::clear($title);

        QUI::getDataBase()->insert(self::table(), [
            'uid' => $User->getUUID(),
            'title' => $title,
            'data' => $data,
            'minHeight' => $minHeight,
            'minWidth' => $minWidth
        ]);

        return QUI::getDataBase()->getPDO()->lastInsertId('id');
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
            QUI::getDataBase()->delete(self::table(), [
                'uid' => $User->getUUID(),
                'id' => $id
            ]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception, [
                'trace' => $Exception->getTraceAsString()
            ]);
        }
    }

    /**
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
     * @throws QUI\Database\Exception
     * @throws QUI\Exception
     */
    public static function getWorkspacesByUser(User $User): array
    {
        $result = QUI::getDataBase()->fetch([
            'from' => self::table(),
            'where' => [
                'uid' => $User->getUUID()
            ]
        ]);

        if (empty($result) && QUI\Permissions\Permission::isAdmin($User)) {
            QUI::getUsers()->setDefaultWorkspacesForUsers($User);

            $result = QUI::getDataBase()->fetch([
                'from' => self::table(),
                'where' => [
                    'uid' => $User->getUUID()
                ]
            ]);
        }

        return $result;
    }

    /**
     * Saves a workspace
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
            if (mb_strlen($workspace['data']) > 20000) {
                throw new QUI\Exception('Could not save the workspace. Workspace is to big.');
            }
        }

        QUI::getDataBase()->update(self::table(), $workspace, [
            'id' => $id,
            'uid' => $User->getUUID()
        ]);


        if (isset($data['standard']) && (int)$data['standard'] === 1) {
            self::setStandardWorkspace($User, $id);
        }
    }

    /**
     * @throws QUI\Exception
     */
    public static function getWorkspaceById(int $id, QUI\Users\User $User): array
    {
        $result = QUI::getDataBase()->fetch([
            'from' => self::table(),
            'where' => [
                'id' => $id,
                'uid' => $User->getUUID()
            ],
            'limit' => 1
        ]);

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

        // all to no standard
        QUI::getDataBase()->update(
            self::table(),
            ['standard' => 0],
            ['uid' => $User->getUUID()]
        );

        // standard
        QUI::getDataBase()->update(
            self::table(),
            ['standard' => 1],
            [
                'id' => $id,
                'uid' => $User->getUUID()
            ]
        );
    }

    /**
     * Return the available panels
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
