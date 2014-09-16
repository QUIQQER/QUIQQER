<?php

/**
 * this file contains \QUI\Workspace\Manager
 */

namespace QUI\Workspace;

use QUI\Utils\Security\Orthos;

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
     * Add a workspace
     *
     * @param \QUI\USers\User $User
     * @param String $title - title of the workspace
     * @param String $data - Workspace profile
     * @param Integer $minHeight - minimum height of the workspace
     * @param Integer $minWidth - minimum width of the workspace
     */
    static function addWorkspace($User, $title, $data, $minHeight, $minWidth)
    {
        $title     = Orthos::clear( $title );
        $minHeight = (int)$minHeight;
        $minWidth  = (int)$minWidth;

        \QUI::getDataBase()->insert( self::Table(), array(
            'uid'       => $User->getId(),
            'title'     => $title,
            'data'      => $data,
            'minHeight' => $minHeight,
            'minWidth'  => $minWidth
        ));

        return \QUI::getDataBase()->getPDO()->lastInsertId( 'id' );
    }

    /**
     * Return the workspaces list from an user
     *
     * @param \QUI\Users\User $User
     * @return Array
     */
    static function getWorkspacesByUser(\QUI\Users\User $User)
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
     * Return a workspace by its id
     *
     * @throws \QUI\Exception
     * @param Integer $id - id of the workspace
     * @return Array
     */
    static function getWorkspaceById($id, $User)
    {
        $result = \QUI::getDataBase()->fetch(array(
            'from'  => self::Table(),
            'where' => array(
                'id'  => $id,
                'uid' => $User->getId()
            ),
            'limit' => 1
        ));

        if ( !isset( $result[ 0 ] ) )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.workspace.not.found'
                ),
                404
            );
        }

        return $result[ 0 ];
    }

    /**
     * Return the titles of the users workspaces
     *
     * @param \QUI\Users\User $User
     * @return Array
     */
    static function getWorkspacesTitlesByUser(\QUI\Users\User $User)
    {
        $workspaces = self::getWorkspacesByUser( $User );
        $result     = array();

        foreach ( $workspaces as $entry ) {
            $result[] = $entry['title'];
        }

        return $result;
    }

    /**
     * Saves a workspace
     *
     * @param \QUI\Users\User $User
     * @param Integer $id
     * @param Array $data
     */
    static function saveWorkspace(\QUI\Users\User $User, $id, $data=array())
    {
        $workspace = self::getWorkspaceById( $id, $User );

        if ( isset( $data['title'] ) ) {
            $workspace['title'] = Orthos::clear( $data['title'] );
        }

        if ( isset( $data['minHeight'] ) ) {
            $workspace['minHeight'] = (int)$data['minHeight'];
        }

        if ( isset( $data['minWidth'] ) ) {
            $workspace['minWidth'] = (int)$data['minWidth'];
        }

        if ( isset( $data['data'] ) )
        {
            $data['data']      = json_decode( $data['data'], true );
            $workspace['data'] = json_encode( $data['data'] );
        }

        \QUI::getDataBase()->update(
            self::Table(),
            $workspace,
            array(
                'id'  => $id,
                'uid' => $User->getId()
            )
        );


        if ( isset( $data['standard'] ) && (int)$data['standard'] === 1 ) {
            self::setStandardWorkspace( $User, $id );
        }
    }

    /**
     * Set the workspace to the standard workspace
     *
     * @param \QUI\Users\User $User
     * @param Integer $id
     */
    static function setStandardWorkspace(\QUI\Users\User $User, $id)
    {
        // all to no standard
        \QUI::getDataBase()->update(
            self::Table(),
            array( 'standard' => 0 ),
            array( 'uid' => $User->getId() )
        );

        // standard
        \QUI::getDataBase()->update(
            self::Table(),
            array( 'standard' => 1 ),
            array(
                'id'  => $id,
                'uid' => $User->getId()
            )
        );
    }

    /**
     * Return the available panels
     *
     * @return Array
     */
    static function getAvailablePanels()
    {
        $cache = 'quiqqer/panels/list';

        try
        {
            return \QUI\Cache\Manager::get( $cache );

        } catch ( \QUI\Exception $Exception )
        {

        }

        $panels   = array();
        $xmlFiles = array( SYS_DIR .'panels.xml' );

        foreach ( $xmlFiles as $file )
        {
            $panels = array_merge(
                $panels,
                \QUI\Utils\XML::getPanelsFromXMLFile( $file )
            );
        }

        \QUI\Cache\Manager::set( $cache, $panels );

        return $panels;
    }
}
