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
        return QUI_DB_PRFX . 'users_workspaces';
    }

    /**
     * Setup for the user workespaces
     */
    public static function setup()
    {
        $Table = QUI::getDataBase()->table();

        $Table->addColumn(self::table(), array(
            'id'        => 'int(11) NOT NULL',
            'uid'       => 'int(11) NOT NULL',
            'title'     => 'text',
            'data'      => 'text',
            'minHeight' => 'int',
            'minWidth'  => 'int',
            'standard'  => 'int(1)'
        ));

        $Table->setAutoIncrement(self::table(), 'id');
        $Table->setPrimaryKey(self::table(), 'id');
    }

    /**
     * Add a workspace
     *
     * @param \QUI\USers\User $User
     * @param string $title - title of the workspace
     * @param string $data - Workspace profile
     * @param integer $minHeight - minimum height of the workspace
     * @param integer $minWidth - minimum width of the workspace
     *
     * @return integer - new Workspace ID
     */
    public static function addWorkspace($User, $title, $data, $minHeight, $minWidth)
    {
        $title     = Orthos::clear($title);
        $minHeight = (int)$minHeight;
        $minWidth  = (int)$minWidth;

        QUI::getDataBase()->insert(self::table(), array(
            'uid'       => $User->getId(),
            'title'     => $title,
            'data'      => $data,
            'minHeight' => $minHeight,
            'minWidth'  => $minWidth
        ));

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
        QUI::getDataBase()->delete(self::table(), array(
            'uid' => $User->getId(),
            'id'  => (int)$id
        ));
    }

    /**
     * Return the workspaces list from an user
     *
     * @param \QUI\Users\User $User
     *
     * @return array
     */
    public static function getWorkspacesByUser(QUI\Users\User $User)
    {
        $result = QUI::getDataBase()->fetch(array(
            'from'  => self::table(),
            'where' => array(
                'uid' => $User->getId()
            )
        ));

        return $result;
    }

    /**
     * Return a workspace by its id
     *
     * @throws \QUI\Exception
     *
     * @param integer $id - id of the workspace
     * @param \QUI\Users\User $User
     *
     * @return array
     */
    public static function getWorkspaceById($id, $User)
    {
        $result = QUI::getDataBase()->fetch(array(
            'from'  => self::table(),
            'where' => array(
                'id'  => $id,
                'uid' => $User->getId()
            ),
            'limit' => 1
        ));

        if (!isset($result[0])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
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
        $result     = array();

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
     */
    public static function saveWorkspace(QUI\Users\User $User, $id, $data = array())
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
            $data['data']      = json_decode($data['data'], true);
            $workspace['data'] = json_encode($data['data']);
        }

        \QUI::getDataBase()->update(
            self::table(),
            $workspace,
            array(
                'id'  => $id,
                'uid' => $User->getId()
            )
        );


        if (isset($data['standard']) && (int)$data['standard'] === 1) {
            self::setStandardWorkspace($User, $id);
        }
    }

    /**
     * Set the workspace to the standard workspace
     *
     * @param \QUI\Users\User $User
     * @param integer $id
     */
    public static function setStandardWorkspace(QUI\Users\User $User, $id)
    {
        // all to no standard
        QUI::getDataBase()->update(
            self::table(),
            array('standard' => 0),
            array('uid' => $User->getId())
        );

        // standard
        QUI::getDataBase()->update(
            self::table(),
            array('standard' => 1),
            array(
                'id'  => $id,
                'uid' => $User->getId()
            )
        );
    }

    /**
     * Return the available panels
     *
     * @return array
     */
    public static function getAvailablePanels()
    {
        $cache = 'quiqqer/panels/list';

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception $Exception) {
        }

        $panels   = array();
        $xmlFiles = array(SYS_DIR . 'panels.xml');

        foreach ($xmlFiles as $file) {
            $panels = array_merge(
                $panels,
                QUI\Utils\Text\XML::getPanelsFromXMLFile($file)
            );
        }

        QUI\Cache\Manager::set($cache, $panels);

        return $panels;
    }
}
