<?php

/**
 * this file contains \QUI\Workspace\Manager
 */

namespace QUI\Workspace;

use QUI;
use QUI\Utils\Security\Orthos;

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
     * Return the table string
     *
     * @return string
     */
    public static function table()
    {
        return QUI::getDBTableName('users_workspaces');
    }

    /**
     * Setup for the user workspaces
     */
    public static function setup()
    {
        $Table  = QUI::getDataBase()->table();
        $column = $Table->getColumn(self::table(), 'data');

        if (\strpos($column['Type'], 'longtext') === false) {
            return;
        }

        try {
            $Table->addColumn(self::table(), [
                'id'        => 'int(11) NOT NULL',
                'uid'       => 'int(11) NOT NULL',
                'title'     => 'text',
                'data'      => 'longtext',
                'minHeight' => 'int',
                'minWidth'  => 'int',
                'standard'  => 'int(1)'
            ]);

            $Table->setAutoIncrement(self::table(), 'id');
            $Table->setPrimaryKey(self::table(), 'id');
        } catch (\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }
    }

    /**
     * Add a workspace
     *
     * @param \QUI\Interfaces\Users\User $User
     * @param string $title - title of the workspace
     * @param string $data - Workspace profile
     * @param integer $minHeight - minimum height of the workspace
     * @param integer $minWidth - minimum width of the workspace
     *
     * @return integer - new Workspace ID
     *
     * @throws QUI\Exception
     */
    public static function addWorkspace($User, $title, $data, $minHeight, $minWidth)
    {
        if (!QUI::getUsers()->isUser($User)) {
            throw new QUI\Exception('No user given');
        }

        $title     = Orthos::clear($title);
        $minHeight = (int)$minHeight;
        $minWidth  = (int)$minWidth;

        QUI::getDataBase()->insert(self::table(), [
            'uid'       => $User->getId(),
            'title'     => $title,
            'data'      => $data,
            'minHeight' => $minHeight,
            'minWidth'  => $minWidth
        ]);

        return QUI::getDataBase()->getPDO()->lastInsertId('id');
    }

    /**
     * Delete a workspace
     *
     * @param integer $id - Workspace ID
     * @param \QUI\Users\User $User - User of the Workspace
     */
    public static function deleteWorkspace($id, $User)
    {
        QUI::getDataBase()->delete(self::table(), [
            'uid' => $User->getId(),
            'id'  => (int)$id
        ]);
    }

    /**
     * Return the workspaces list from an user
     *
     * @param \QUI\Interfaces\Users\User $User
     *
     * @return array
     */
    public static function getWorkspacesByUser(QUI\Interfaces\Users\User $User)
    {
        $result = QUI::getDataBase()->fetch([
            'from'  => self::table(),
            'where' => [
                'uid' => $User->getId()
            ]
        ]);

        if (empty($result) && QUI\Permissions\Permission::isAdmin($User)) {
            QUI::getUsers()->setDefaultWorkspacesForUsers($User);

            $result = QUI::getDataBase()->fetch([
                'from'  => self::table(),
                'where' => [
                    'uid' => $User->getId()
                ]
            ]);
        }

        return $result;
    }

    /**
     * Return a workspace by its id
     *
     * @param integer $id - id of the workspace
     * @param \QUI\Users\User $User
     *
     * @return array
     * @throws \QUI\Exception
     *
     */
    public static function getWorkspaceById($id, $User)
    {
        $result = QUI::getDataBase()->fetch([
            'from'  => self::table(),
            'where' => [
                'id'  => $id,
                'uid' => $User->getId()
            ],
            'limit' => 1
        ]);

        if (!isset($result[0])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.workspace.not.found'
                ),
                404
            );
        }

        return $result[0];
    }

    /**
     * Return the titles of the users workspaces
     *
     * @param \QUI\Users\User $User
     *
     * @return array
     */
    public static function getWorkspacesTitlesByUser(QUI\Users\User $User)
    {
        $workspaces = self::getWorkspacesByUser($User);
        $result     = [];

        foreach ($workspaces as $entry) {
            $result[] = $entry['title'];
        }

        return $result;
    }

    /**
     * Saves a workspace
     *
     * @param \QUI\Users\User $User
     * @param integer $id
     * @param array $data
     *
     * @throws QUI\Exception
     */
    public static function saveWorkspace(QUI\Users\User $User, $id, $data = [])
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
            $data['data']      = \json_decode($data['data'], true);
            $workspace['data'] = \json_encode($data['data']);
        }

        QUI::getDataBase()->update(self::table(), $workspace, [
            'id'  => $id,
            'uid' => $User->getId()
        ]);


        if (isset($data['standard']) && (int)$data['standard'] === 1) {
            self::setStandardWorkspace($User, $id);
        }
    }

    /**
     * Set the workspace to the standard workspace
     *
     * @param QUI\Interfaces\Users\User $User
     * @param integer $id
     */
    public static function setStandardWorkspace(QUI\Interfaces\Users\User $User, int $id)
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
            ['uid' => $User->getId()]
        );

        // standard
        QUI::getDataBase()->update(
            self::table(),
            ['standard' => 1],
            [
                'id'  => $id,
                'uid' => $User->getId()
            ]
        );
    }

    /**
     * Return the available panels
     *
     * @return array
     */
    public static function getAvailablePanels()
    {
        $cache = 'quiqqer/package/quiqqer/quiqqer/available-panels';

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception $Exception) {
        }

        $panels   = [];
        $xmlFiles = \array_merge(
            [SYS_DIR.'panels.xml'],
            QUI::getPackageManager()->getPackageXMLFiles('panels.xml')
        );

        foreach ($xmlFiles as $file) {
            $panels = \array_merge(
                $panels,
                QUI\Utils\Text\XML::getPanelsFromXMLFile($file)
            );
        }

        try {
            QUI\Cache\Manager::set($cache, $panels);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        return $panels;
    }

    /**
     * Return the two column workspace default
     *
     * @return string
     */
    public static function getTwoColumnDefault()
    {
        return \file_get_contents(\dirname(\dirname(__FILE__)).'/Users/workspaces/twoColumns.js');
    }

    /**
     * Return the three column workspace default
     *
     * @return string
     */
    public static function getThreeColumnDefault()
    {
        return \file_get_contents(\dirname(\dirname(__FILE__)).'/Users/workspaces/threeColumns.js');
    }
}
