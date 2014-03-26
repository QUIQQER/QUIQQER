<?php

/**
 * This file contains \QUI\Editor\Manager
 */

namespace QUI\Editor;

/**
 * Wysiwyg manager
 *
 * manages all wysiwyg editors and the settings for them
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 *
 * @todo docu translation
 */

class Manager
{
    /**
     * WYSIWYG editor config
     * @var \QUI\Config
     */
    static $Config = null;

    /**
     * Editor plugins
     * @var array
     */
    protected $_plugins = array();

    /**
     * Setup
     */
    static function setup()
    {
        \QUI\Utils\System\File::mkdir( self::getToolbarsPath() );

        if ( !file_exists( CMS_DIR .'etc/wysiwyg/conf.ini' ) ) {
            file_put_contents( CMS_DIR .'etc/wysiwyg/conf.ini', '' );
        }

        if ( !file_exists( CMS_DIR .'etc/wysiwyg/editors.ini' ) ) {
            file_put_contents( CMS_DIR .'etc/wysiwyg/editors.ini', '' );
        }
    }

    /**
     * Pfad zu den XML Dateien
     * @return String
     */
    static function getPath()
    {
        return CMS_DIR .'etc/wysiwyg/';
    }

    /**
     * Return the path to the toolbars
     * @return String
     */
    static function getToolbarsPath()
    {
        return self::getPath() .'toolbars/';
    }

    /**
     * Return the main editor manager (wyiswyg) config object
     *
     * @return \QUI\Config
     */
    static function getConf()
    {
        if ( !self::$Config ) {
            self::$Config = \QUI::getConfig( 'etc/wysiwyg/conf.ini' );
        }

        return self::$Config;
    }

    /**
     * Return all settings of the manager
     *
     * @return Array
     */
    static function getConfig()
    {
        $config = self::getConf()->toArray();
        $config['toolbars'] = self::getToolbars();
        $config['editors']  = array();
        $config['editors']  = \QUI::getConfig( 'etc/wysiwyg/editors.ini' )->toArray();

        return $config;
    }

    /**
     * Register a js editor
     *
     * @param String $name - name of the editor
     * @param String $package - js modul/package name
     */
    static function registerEditor($name, $package)
    {
        $Conf = \QUI::getConfig( 'etc/wysiwyg/editors.ini' );
        $Conf->setValue( $name, null, $package );
        $Conf->save();
    }

    /**
     * Bereitet HTML für den Editor
     * URL bei Bildern richtig setzen damit diese im Admin angezeigt werden
     *
     * @param String $html
     */
    public function load($html)
    {
        // Bilder umschreiben
        $html = preg_replace_callback(
            '#(src)="([^"]*)"#',
            array($this, "cleanAdminSrc"),
            $html
        );

        foreach ( $this->_plugins as $p )
        {
            if ( method_exists( $p, 'onLoad' ) ) {
                $html = $p->onLoad( $html );
            }
        }

        return $html;
    }

    /**
     * Alle Toolbars bekommen, welche zur Verfügung stehen
     *
     * @return array
     */
    static function getToolbars()
    {
        $folder = self::getToolbarsPath();
        $files  = \QUI\Utils\System\File::readDir( $folder, true );

//         try
//         {
//             $Conf     = self::getConf();
//             $toolbars = $Conf->getSection( 'toolbars' );

//             if ( !is_array( $toolbars ) ) {
//                 return $files;
//             }

//             foreach ( $toolbars as $toolbar => $btns ) {
//                 array_unshift( $files, $toolbar );
//             }

//         } catch ( \QUI\Exception $Exception )
//         {

//         }

        return $files;
    }

    /**
     * Delete a toolbar
     *
     * @param String $toolbar - Name of the tools (toolbar.xml)
     */
    static function deleteToolbar($toolbar)
    {
        \QUI\Rights\Permission::hasPermission(
            'quiqqer.editors.toolbar.delete'
        );

        $folder = self::getToolbarsPath();
        $path   = $folder . $toolbar;

        $path = \QUI\Utils\Security\Orthos::clearPath( $path );

        if ( file_exists( $path ) ) {
            unlink( $path );
        }
    }

    /**
     * Add a new toolbar
     *
     * @param String $toolbar - Name of the tools (myNewToolbar)
     */
    static function addToolbar($toolbar)
    {
        \QUI\Rights\Permission::hasPermission(
            'quiqqer.editors.toolbar.add'
        );

        $toolbar = str_replace( '.xml', '', $toolbar );

        $folder = self::getToolbarsPath();
        $file   = $folder . $toolbar .'.xml';

        if ( file_exists( $file ) )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.editor.toolbar.exist'
                )
            );
        }

        \QUI\Utils\System\File::mkfile( $file );
    }

    /**
     * Save the Toolbar
     *
     * @param String $toolbar - toolbar name
     * @param String $xml - toolbar xml
     */
    static function saveToolbar($toolbar, $xml)
    {
        \QUI\Rights\Permission::hasPermission(
            'quiqqer.editors.toolbar.save'
        );

        $toolbar = str_replace( '.xml', '', $toolbar );

        $folder = self::getToolbarsPath();
        $file   = $folder . $toolbar .'.xml';

        if ( !file_exists( $file ) )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.editor.toolbar.not.exist'
                )
            );
        }

        // check the xml
        libxml_use_internal_errors( true );

        $Doc = new \DOMDocument('1.0', 'utf-8');
        $Doc->loadXML( $xml );

        $errors = libxml_get_errors();

        if ( !empty( $errors ) )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.editor.toolbar.xml.error',
                    array( 'error' => $errors[0]->message )
                )
            );
        }

        file_put_contents( $file, $xml );
    }

    /**
     * Buttonliste vom aktuellen Benutzer bekommen
     *
     * @return array
     */
    static function getToolbarButtonsFromUser()
    {
        // Erste Benutzer spezifische Toolbar
        $Users = \QUI::getUsers();
        $User  = $Users->getUserBySession();

        $toolbar = $User->getExtra( 'wysiwyg-toolbar' );

        if ( !empty( $toolbar ) )
        {
            $toolbar = self::getToolbarsPath() . $User->getExtra( 'wysiwyg-toolbar' );

            if ( file_exists( $toolbar ) ) {
                return self::parseXmlFileToArray( $toolbar );
            }
        }

        // Dann Gruppenspezifische Toolbar
        $groups = $User->getGroups();
        $Group  = end( $groups );

        $toolbar = $Group->getAttribute( 'toolbar' );

        if ( !empty( $toolbar ) )
        {
            $toolbar = self::getToolbarsPath() . $Group->getAttribute( 'toolbar' );

            if ( file_exists( $toolbar ) ) {
                return self::parseXmlFileToArray( $toolbar );
            }
        }

        $Config  = self::getConf();
        $toolbar = $Config->get( 'toolbars', 'standard' );

        // standard
        if ( $toolbar === false ) {
            return array();
        }

        if ( strpos( $toolbar, '.xml' ) !== false )
        {
            if ( file_exists( self::getToolbarsPath() . $toolbar ) ) {
                return self::parseXmlFileToArray( self::getToolbarsPath() . $toolbar );
            }
        }

        return explode( ',', $Config->get( 'toolbars', 'standard' ) );
    }

    /**
     * Toolbar auslesen
     *
     * @param unknown_type $file
     * @return array
     */
    static function parseXmlFileToArray($file)
    {
        $cache = 'editor/xml/file/'. md5( $file );

        try
        {
            return \QUI\Cache\Manager::get( $cache );

        } catch ( \QUI\Exception $Exception )
        {

        }

        \QUI\System\Log::write( $file );

        $Dom     = \QUI\Utils\XML::getDomFromXml( $file );
        $toolbar = $Dom->getElementsByTagName( 'toolbar' );

        if ( !$toolbar->length ) {
            return array();
        }

        $children = $toolbar->item( 0 )->childNodes;
        $result   = array();

        for ( $i = 0; $i < $children->length; $i++ )
        {
            $Param = $children->item( $i );

            if ( $Param->nodeName == '#text' ) {
                continue;
            }

            if ( $Param->nodeName == 'line' ) {
                $result['lines'][] = self::parseXMLLineNode( $Param );
            }

            if ( $Param->nodeName == 'group' ) {
                $result['groups'][] = self::parseXMLGroupNode( $Param );
            }
        }

        \QUI\Cache\Manager::set( $cache, $result );

        return $result;
    }

    /**
     * Parse an XML <line> node
     *
     * @param DOMNode $Node
     * @return boolean|array
     */
    static function parseXMLLineNode($Node)
    {
        if ( $Node->nodeName != 'line' ) {
            return false;
        }

        $children = $Node->childNodes;
        $result   = array();

        for ( $i = 0; $i < $children->length; $i++ )
        {
            $Param = $children->item( $i );

            if ( $Param->nodeName == '#text' ) {
                continue;
            }

            if ( $Param->nodeName == 'group' ) {
                $result[] = self::parseXMLGroupNode( $Param );
            }
        }

        return $result;
    }

    /**
     * Parse an XML <group> node
     *
     * @param DOMNode $Node
     * @return boolean|array
     */
    static function parseXMLGroupNode($Node)
    {
        if ( $Node->nodeName != 'group' ) {
            return false;
        }

        $children = $Node->childNodes;
        $result   = array();

        for ( $i = 0; $i < $children->length; $i++ )
        {
            $Param = $children->item( $i );

            if ( $Param->nodeName == 'seperator' )
            {
                $result[] = array(
                    'type' => 'seperator'
                );

                continue;
            }

            if ( $Param->nodeName == 'button' )
            {
                $result[] = array(
                    'type'   => 'button',
                    'button' => trim( $Param->nodeValue )
                );
            }
        }

        return $result;
    }

    /**
     * Clean up methods
     */

    /**
     * Cleanup HTML - Saubermachen des HTML Codes
     *
     * @uses Tidy, if enabled
     * @param String $html
     */
    public function cleanHTML($html)
    {
        $html = preg_replace( '/<!--\[if gte mso.*?-->/s', '', $html );

        $search = array(
            'font-family: Arial',
             'class="MsoNormal"'
        );

        $html = str_ireplace( $search, '', $html );

        if ( class_exists( 'tidy' ) )
        {
            $Tidy = new Tidy();

            $config = array(
                "char-encoding"     => "utf8",
                'output-xhtml'      => true,
                'indent-attributes' => false,
                'wrap'              => 0,
                'word-2000'         => 1,

                // html 5 Tags registrieren
                'new-blocklevel-tags' => 'header, footer, article, section, hgroup, nav, figure'
            );

            $Tidy->parseString( $html, $config, 'utf8' );
            $Tidy->cleanRepair();
            $html = $Tidy;
        }

        return $html;
    }

    /**
     * HTML Speichern
     *
     * @param String $html
     * @return String
     */
    public function prepareHTMLForSave($html)
    {
        // Bilder umschreiben
        $html = preg_replace_callback(
            '#(src)="([^"]*)"#',
            array($this, "cleanSrc"),
            $html
        );

        $html = preg_replace_callback(
            '#(href)="([^"]*)"#',
            array($this, "cleanHref"),
            $html
           );

           foreach ( $this->_plugins as $p )
        {
            if ( method_exists( $p, 'onSave' ) ) {
                $html = $p->onSave( $html );
            }
        }

        $html = $this->cleanHTML( $html );

        // Zeilenumbrüche in HTML löschen
        $html = preg_replace_callback(
            '#(<)(.*?)(>)#',
            array( $this, "_deleteLineBreaksInHtml" ),
            $html
           );

        return $html;
    }

    /**
     * Entfernt Zeilenumbrüche in HTML
     *
     * @param unknown_type $params
     * @return unknown
     */
    protected function _deleteLineBreaksInHtml($params)
    {
        if ( !isset( $params[0] ) ) {
            return $params[0];
        }

        return str_replace(
            array("\r\n", "\n", "\r"),
            "",
            $params[0]
        );
    }

    /**
     * Image Src sauber machen
     *
     * @param unknown_type $html
     * @return unknown
     */
    public function cleanSrc($html)
    {
        if ( isset( $html[2]) &&
             strpos( $html[2], 'image.php' ) !== false )
        {
            $html[2] = str_replace( '&amp;','&', $html[2] );
            $src_    = explode( 'image.php?', $html[2] );

            return ' '. $html[1] .'="image.php?'. $src_[1] .'"';
        }

        return $html[0];
    }

    /**
     * HREF Src sauber machen
     *
     * @param unknown_type $html
     * @return unknown
     */
    public function cleanHref($html)
    {
        if ( isset( $html[2] ) && strpos( $html[2], 'index.php' ) !== false )
        {
            $index = explode( 'index.php?', $html[2] );

            return $html[1] .'="index.php?'.$index[1]. '"';
        }


        if( isset( $html[2] ) && strpos( $html[2], 'image.php' ) !== false )
        {
            $index = explode( 'image.php?', $html[2] );

            return ' '. $html[1] .'="image.php?'. $index[1] .'"';
        }

        return $html[0];
    }

    /**
     * Bereitet HTML für den Editor
     *
     * @param unknown_type $html
     * @return unknown
     */
    public function cleanAdminSrc($html)
    {
        if ( isset($html[2]) && strpos( $html[2], 'image.php' ) !== false )
        {
            $src_ = explode( 'image.php?', $html[2] );

            return ' '. $html[1] .'="'. URL_DIR .'image.php?'. $src_[1] .'" ';
        }

        return $html[0];
    }
}
