<?php

/**
 * this file contains \QUI\Workspace\Manager
 */

namespace QUI\Workspace;

/**
 *
 *
 * @author www.pcsg.de (Henning Leutz)
 */

class Manager
{
    /**
     * Return the table string
     * @return string
     */
    static function Table()
    {
        return QUI_DB_PRFX .'users_workspaces';
    }

    /**
     * Setup for the user workespaces
     */
    static function setup()
    {
        $Table = \QUI::getDataBase()->Table();

        $Table->appendFields(self::Table(), array(
            'id'        => 'int(11) NOT NULL',
            'uid'       => 'int(11) NOT NULL',
            'title'     => 'text',
            'data'      => 'text',
            'minHeight' => 'int',
            'minWidth'  => 'int',
            'standard'  => 'int(1)'
        ));

        $Table->setAutoIncrement( self::Table(), 'id' );
        $Table->setPrimaryKey( self::Table(), 'id' );
    }

    /**
     * Return the workspaces list from an user
     *
     * @param \QUI\Users\User $User
     * @return Array
     */
    static function getWorkspacesFromUser(\QUI\Users\User $User)
    {
        $result = \QUI::getDataBase()->fetch(array(
            'from'  => self::Table(),
            'where' => array(
                'uid' => $User->getId()
            )
        ));

        return $result;
    }

    /**
     *
     * @param \QUI\Users\User $User
     */
    static function getWorkspacesTitlesFromUser(\QUI\Users\User $User)
    {
        $result = \QUI::getDataBase()->fetch(array(
            'from'  => self::Table(),
            'where' => array(
                'uid' => $User->getId()
            )
        ));
    }

    static function saveWorkspace($User, $id, $data)
    {

    }
}
