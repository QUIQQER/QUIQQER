<?php

/**
 * This file contains QUI_Desktop_Manager
 */

/**
 * Desktop Manager
 *
 * Save and manage the desktops for the users
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 */

class QUI_Desktop_Manager
{
    const TABLE = 'desktops';

    /**
     * Get the Desktop
     *
     * @throws QException
     * @return QUI_Desktop
     */
    public function get($desktopid)
    {
        $User     = \QUI::getUserBySession();
        $Database = \QUI::getDataBase();

        $result = $Database->fetch(array(
            'from' => \QUI::getDBTableName( self::TABLE ),
            'where' => array(
                'id'  => (int)$desktopid,
                'uid' => $User->getId()
            ),
            'limit' => 1
        ));

        if ( !isset( $result[ 0 ] ) )
        {
            throw new \QException(
                'Desktop not found',
                404
            );
        }

        return new \QUI_Desktop( $result[ 0 ] );
    }

    /**
     * Return all Desktops from the session user
     *
     * @return array
     */
    public function getDesktopsFromUser()
    {
        $User     = \QUI::getUserBySession();
        $Database = \QUI::getDataBase();

        $result = $Database->fetch(array(
            'from' => \QUI::getDBTableName( self::TABLE ),
            'where' => array(
                'uid' => $User->getId()
            )
        ));

        $list = array();

        foreach ( $result as $entry ) {
            $list[] = new \QUI_Desktop( $entry );
        }

        return $list;
    }

    /**
     * Saves a Desktop with its widgets
     *
     * @param QUI_Desktop $Desktop
     * @param array $widgets - new widgets
     */
    public function save(QUI_Desktop $Desktop, $widgets=array())
    {
        $User     = \QUI::getUserBySession();
        $Database = \QUI::getDataBase();

        // check if the desktop exist and is for the user
        $this->get( $Desktop->getId() );

        // @todo check widgets

        $Database->update(
            \QUI::getDBTableName( self::TABLE ),
            array(
                'widgets' => json_encode( $widgets )
            ),
            array(
                'id'  => $Desktop->getId(),
                'uid' => $User->getId()
            )
        );
    }

    /**
     * Create a new Desktop for the user
     *
     * @throws QException
     * @return QUI_Desktop
     */
    public function create($title='')
    {
        $User     = \QUI::getUserBySession();
        $Database = \QUI::getDataBase();

        $title = \Utils_Security_Orthos::clear( $title );

        $Database->insert(
            \QUI::getDBTableName( self::TABLE ),
            array(
                'uid'   => $User->getId(),
                'title' => $title
            )
        );

        return $this->get( $Database->getPDO()->lastInsertId() );
    }

    /**
     * Setup for Desktops
     */
    static function setup()
    {
        $DBTable = \QUI::getDataBase()->Table();
        $table   = \QUI::getDBTableName( self::TABLE );

        // Haupttabelle anlegen
        $DBTable->appendFields( $table, array(
            'id'      => 'int(11) NOT NULL',
            'uid'     => 'int(11) NOT NULL',
            'widgets' => 'text',
            'title'   => 'varchar(200)'
        ));

        $DBTable->setIndex($table, array(
            'id', 'uid'
        ));

        $DBTable->setAutoIncrement($table, 'id');
    }

    /**
     * parse an Widget DOM-Node to an QUI_Desktop_Widget
     *
     * @param DOMNode $Node
     * @return QUI_Desktop_Widget
     */
    static function DOMToWidget(DOMNode $Node)
    {
        $Widget = new \QUI_Desktop_Widget();

        $atributes = $Node->getElementsByTagName( 'attributes' );
        $require   = $Node->getElementsByTagName( 'require' );
        $content   = $Node->getElementsByTagName( 'content' );
        $title     = $Node->getElementsByTagName( 'title' );

        $Widget->setAttribute( 'name', $Node->getAttribute( 'name' ) );

        if ( $atributes->length )
        {
            $Attributes = $atributes->item( 0 );

            if ( $Attributes->getAttribute( 'height' ) ) {
                $Widget->setAttribute( 'height', $Attributes->getAttribute( 'height' ) );
            }

            if ( $Attributes->getAttribute( 'width' ) ) {
                $Widget->setAttribute( 'width', $Attributes->getAttribute( 'width' ) );
            }

            if ( $Attributes->getAttribute( 'icon' ) ) {
                $Widget->setAttribute( 'icon', $Attributes->getAttribute( 'icon' ) );
            }

            if ( $Attributes->getAttribute( 'refresh' ) ) {
                $Widget->setAttribute( 'refresh', $Attributes->getAttribute( 'refresh' ) );
            }
        }

        if ( $require->length )
        {
            $requires = array();

            for ( $i = 0, $len = $require->length; $i < $len; $i++ ) {
                $requires[] = $require->item( $i )->getAttribute('src');
            }

            $Widget->setAttribute( 'require', $requires );
        }

        if ( $content->length )
        {
            $Content = $content->item( 0 );

            $Widget->setAttribute(
                'content',
                array(
                    'type'    => $Content->getAttribute('type'),
                    'func'    => $Content->getAttribute('func'),
                    'content' => trim( $Content->nodeValue )
                )
            );
        }

        if ( $title->length )
        {
            $Widget->setAttribute(
                'title',
                trim( $title->item( 0 )->nodeValue )
            );
        }

        return $Widget;
    }

    /**
     * Read all Widgets Files and return all Widget DOM Nodes
     *
     * @return Array
     */
    static function readWidgetsFiles()
    {
        // system widgets
        $dir   = SYS_DIR .'widgets/';
        $files = \Utils_System_File::readDir( $dir );

        $result = array();

        foreach ( $files as $file )
        {
            $result = array_merge(
                $result,
                \Utils_Xml::getWidgetsFromXml( $dir .'/'. $file )
            );
        }

        return $result;
    }

    /**
     * Return all Widgets
     *
     * @return Array
     */
    static function getWidgetList()
    {
        $result = array();
        $list   = self::readWidgetsFiles();

        foreach ( $list as $DOM ) {
            $result[] = self::DOMToWidget( $DOM );
        }

        return $result;
    }
}
