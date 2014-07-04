<?php

/**
 * This file contains the \QUI\Utils\XML
 */

namespace QUI\Utils;

/**
 * QUIQQER XML Util class
 *
 * Provides methods to read and work with QUIQQER XML files
 *
 * @author www.pcsg.de (Henning Leutz)
 */

class XML
{
    /**
     * Add a menu.xml file to a contextmenu bar item
     *
     * @param \QUI\Controls\Contextmenu\Bar $Menu - Menu Object
     * @param String $file - Path to XML File
     */
    static function addXMLFileToMenu(\QUI\Controls\Contextmenu\Bar $Menu, $file)
    {
        if ( !file_exists( $file ) ) {
            return;
        }

        // read the xml
        $items = self::getMenuItemsXml( $file );

        foreach ( $items as $Item )
        {
            if ( !$Item->getAttribute( 'parent' ) ) {
                continue;
            }

            $params = array(
                'text'    => \QUI\Utils\DOM::getTextFromNode( $Item ),
                'name'    => $Item->getAttribute( 'name' ),
                'icon'    => \QUI\Utils\DOM::parseVar( $Item->getAttribute( 'icon' ) ),
                'require' => $Item->getAttribute( 'require' ),
                'exec'    => $Item->getAttribute( 'exec' ),
                'onClick' => 'QUI.Menu.menuClick'
                //'click'   => $Item->getAttribute( 'onclick' )
            );

            $Parent = $Menu;

            if ( $Item->getAttribute( 'parent' ) == '/' )
            {
                $MenuItem = new \QUI\Controls\Contextmenu\Baritem( $params );

            } else
            {
                $MenuItem    = new \QUI\Controls\Contextmenu\Menuitem( $params );
                $parent_path = explode( '/', trim( $Item->getAttribute( 'parent' ), '/' ) );

                foreach ( $parent_path as $parent )
                {
                    if ( $Parent ) {
                        $Parent = $Parent->getElementByName( $parent );
                    }
                }
            }

            if ( $Item->getAttribute( 'type' ) == 'seperator' ) {
                $MenuItem = new \QUI\Controls\Contextmenu\Seperator( $params );
            }

            if ( $Item->getAttribute( 'disabled' ) == 1 ) {
                $MenuItem->setDisable();
            }

            if ( $Parent ) {
                $Parent->appendChild( $MenuItem );
            }
        }
    }

    /**
     * Read the config parameter of an *.xml file and
     * create a \QUI\Config if not exist or read the \QUI\Config
     *
     * @param String $file - path to the xml file
     * @return \QUI\Config|false
     */
    static function getConfigFromXml($file)
    {
        $Dom      = self::getDomFromXml( $file );
        $settings = $Dom->getElementsByTagName( 'settings' );

        if ( !$settings->length ) {
            return false;
        }

        $Settings = $settings->item( 0 );
        $configs  = $Settings->getElementsByTagName( 'config' );

        if ( !$configs->length ) {
            return false;
        }

        if ( !$configs->item( 0 )->getAttribute( 'name' ) ) {
            return false;
        }


        $Conf     = $configs->item( 0 );
        $ini_file = CMS_DIR .'etc/' . $Conf->getAttribute( 'name' ) .'.ini.php';

        if ( !$Conf->getAttribute( 'name' ) ) {
            $ini_file .= $Conf->getAttribute( 'name' ) .'.ini.php';
        }


        \QUI\Utils\System\File::mkdir( dirname( $ini_file ) );

        if ( !file_exists( $ini_file ) ) {
            file_put_contents( $ini_file, '' );
        }

        $Config = new \QUI\Config( $ini_file );
        $params = self::getConfigParamsFromXml( $file );

        foreach ( $params as $section => $key )
        {
            if ( isset( $key['default'] ) )
            {
                if ( $Config->existValue( $section ) === false ) {
                    $Config->setValue( $section, $key['default'] );
                }

                continue;
            }

            foreach ( $key as $value => $entry )
            {
                if ( $Config->existValue( $section, $value ) === false ) {
                    $Config->setValue( $section, $value, $entry['default'] );
                }
            }
        }

        return $Config;
    }

    /**
     * Reads the config parameter from an *.xml
     *
     * @param String $file - path to xml file
     * @return DOMNode|false
     */
    static function getConfigParamsFromXml($file)
    {
        return \QUI\Utils\DOM::getConfigParamsFromDOM(
            self::getDomFromXml( $file )
        );
    }

    /**
     * Reads the tools list from an *.xml
     *
     * @param String $file - path to xml file
     * @return Array
     */
    static function getConsoleToolsFromXml($file)
    {
        $Dom     = self::getDomFromXml( $file );
        $console = $Dom->getElementsByTagName( 'console' );

        if ( !$console->length ) {
            return array();
        }

        $Console = $console->item( 0 );
        $tools   = $Console->getElementsByTagName( 'tool' );

        if ( !$tools->length ) {
            return array();
        }

        $list = array();

        for ( $i = 0; $i < $tools->length; $i++ )
        {
            $exec = $tools->item( $i )->getAttribute('exec');

            if ( !empty( $exec ) ) {
                $list[] = $exec;
            }
        }

        return $list;
    }

    /**
     * Reads the css file list from an *.xml
     *
     * @param String $file
     * @return Array
     */
    static function getWysiwygCSSFromXml($file)
    {
        $Dom  = self::getDomFromXml( $file );
        $Path = new \DOMXPath( $Dom );

        $CSSList = $Path->query( "//wysiwyg/css" );
        $files   = array();

        for ( $i = 0; $i < $CSSList->length; $i++ ) {
             $files[] = $CSSList->item( $i )->getAttribute( 'src' );
        }

        return $files;
    }

    /**
     * Reads the database entries from an *.xml
     *
     * @param String $file - path to the xml file
     * @return Array
     */
    static function getDataBaseFromXml($file)
    {
        $Dom      = self::getDomFromXml( $file );
        $database = $Dom->getElementsByTagName( 'database' );

        if ( !$database->length ) {
            return array();
        }

        $dbfields = array();

        $global  = $database->item( 0 )->getElementsByTagName( 'global' );
        $project = $database->item( 0 )->getElementsByTagName( 'projects' );

        // global
        if ( $global && $global->length )
        {
            $tables = $global->item(0)->getElementsByTagName( 'table' );

            for ( $i = 0; $i < $tables->length; $i++ )
            {
                $dbfields['globals'][] = \QUI\Utils\DOM::dbTableDomToArray(
                    $tables->item( $i )
                );
            }

            if ( $global->item(0)->getAttribute( 'execute' ) ) {
                $dbfields['execute'][] = $global->item(0)->getAttribute( 'execute' );
            }
        }

        // projects
        if ( $project && $project->length )
        {
            $tables = $project->item(0)->getElementsByTagName( 'table' );

            for ( $i = 0; $i < $tables->length; $i++ )
            {
                $dbfields['projects'][] = \QUI\Utils\DOM::dbTableDomToArray(
                    $tables->item( $i )
                );
            }
        }

        return $dbfields;
    }

    /**
     * Liefer das XML als DOMDocument zurück
     *
     * @param String $filename
     * @return DOMDocument
     */
    static function getDomFromXml($filename)
    {
        if ( strpos( $filename, '.xml' ) === false ) {
            return new \DOMDocument();
        }

        if ( !file_exists( $filename ) ) {
            return new \DOMDocument();
        }

        $Dom = new \DOMDocument();
        $Dom->load( $filename );

        return $Dom;
    }

    /**
     * Reads the events from an *.xml
     * Return all <event>
     *
     * @param String $file
     * @return Array
     */
    static function getEventsFromXml($file)
    {
        $Dom    = self::getDomFromXml( $file );
        $events = $Dom->getElementsByTagName( 'events' );

        if ( !$events->length ) {
            return array();
        }

        $Event = $events->item(0);
        $list  = $Event->getElementsByTagName( 'event' );

        $result = array();

        for ( $i = 0, $len = $list->length; $i < $len; $i++ ) {
            $result[] = $list->item( $i );
        }

        return $result;
    }

    /**
     * Sucht die Übersetzungsgruppe aus einem DOMDocument Objekt
     *
     * @param DOMDocument $Dom
     * @return Array array(
     *      array(
     * 		    'groups'   => 'group.name',
     * 		    'locales'  => array(),
     * 	        'datatype' => 'js'
     *      ),
     *      array(
     * 		    'groups'   => 'group.name',
     * 		    'locales'  => array(),
     * 	        'datatype' => ''
     *      ),
     *  );
     */
    static function getLocaleGroupsFromDom(\DOMDocument $Dom)
    {
        $locales = $Dom->getElementsByTagName( 'locales' );

        if ( !$locales->length ) {
            return array();
        }

        $Locales = $locales->item(0);
        $groups  = $Locales->getElementsByTagName( 'groups' );

        if ( !$groups->length ) {
            return array();
        }

        $result = array();

        for ( $g = 0, $glen = $groups->length; $g < $glen; $g++ )
        {
            $Group      = $groups->item( $g );
            $localelist = $Group->getElementsByTagName( 'locale' );

            $locales = array(
                'group'    => $Group->getAttribute( 'name' ),
                'locales'  => array(),
                'datatype' => $Group->getAttribute( 'datatype' )
            );

            for ( $c = 0; $c < $localelist->length; $c++ )
            {
                $Locale = $localelist->item( $c );

                if ( $Locale->nodeName == '#text' ) {
                    continue;
                }

                $params = array(
                    'name' => $Locale->getAttribute( 'name' )
                );

                $translations = $Locale->childNodes;

                for ( $i = 0; $i < $translations->length; $i++ )
                {
                    $Translation = $translations->item( $i );

                    if ( $Translation->nodeName == '#text' ) {
                        continue;
                    }

                    $params[ $Translation->nodeName ] = $Translation->nodeValue;
                }

                $locales[ 'locales' ][] = $params;
            }

            $result[] = $locales;
        }

        return $result;
    }

    /**
     * Reads the menu items from an *.xml
     *
     * @param String $file
     * @return Array
     */
    static function getMenuItemsXml($file)
    {
        $Dom  = self::getDomFromXml( $file );
        $menu = $Dom->getElementsByTagName( 'menu' );

        if ( !$menu->length ) {
            return array();
        }

        $Menu  = $menu->item( 0 );
        $items = $Menu->getElementsByTagName( 'item' );

        if ( !$items->length ) {
            return array();
        }

        $result = array();

        for ( $c = 0; $c < $items->length; $c++ )
        {
            $Item = $items->item( $c );

            if ( $Item->nodeName == '#text' ) {
                continue;
            }

            $result[] = $Item;
        }

        return $result;
    }

    /**
     * Read the permissions from an *.xml file
     *
     * @param String $file - path to the xml file
     * @return array
     */
    static function getPermissionsFromXml($file)
    {
        $Dom         = self::getDomFromXml( $file );
        $permissions = $Dom->getElementsByTagName( 'permissions' );

        if ( !$permissions || !$permissions->length ) {
            return array();
        }

        $Permissions = $permissions->item( 0 );
        $permission  = $Permissions->getElementsByTagName( 'permission' );

        if ( !$permission || !$permission->length ) {
            return array();
        }

        $result = array();

        for ( $i = 0; $i < $permission->length; $i++ )
        {
            $result[] = \QUI\Utils\DOM::parsePermissionToArray(
                $permission->item( $i )
            );
        }

        return $result;
    }

    /**
     * Reads the settings window from an *.xml and search the category
     *
     * @param String $file - path to xml file
     * @param String $name - Category name
     * @return DOMNode|false
     */
    static function getSettingCategoriesFromXml($file, $name)
    {
        $Dom      = self::getDomFromXml( $file );
        $settings = $Dom->getElementsByTagName( 'settings') ;

        if ( !$settings->length ) {
            return false;
        }

        $Settings = $settings->item( 0 );
        $winlist  = $Settings->getElementsByTagName( 'window' );

        if ( !$winlist->length ) {
            return false;
        }

        $Window     = $winlist->item( 0 );
        $categories = $Window->getElementsByTagName( 'categories' );

        if ( !$categories->length ) {
            return false;
        }

        $Categories = $categories->item(0)->childNodes;

        for ( $c=0; $c < $Categories->length; $c++ )
        {
            $Category = $Categories->item( $c );

            if ( $Category->nodeName == '#text' ) {
                continue;
            }

            if ( $Category->getAttribute('name') == $name ) {
                return $Category;
            }
        }

        return false;
    }

    /**
     * Return the settings window from an *.xml
     *
     * @param String $file
     * @return Array
     */
    static function getSettingWindowsFromXml($file)
    {
        $Dom  = self::getDomFromXml( $file );
        $Path = new \DOMXPath( $Dom );

        $windows = $Path->query( "//quiqqer/settings/window" );

        if ( !$windows->length ) {
            return array();
        }

        $result = array();

        for ( $i = 0, $len = $windows->length; $i < $len; $i++ ) {
            $result[] = $windows->item( $i );
        }

        return $result;
    }

    /**
     * Return the project settings window from an *.xml
     *
     * @param String $file
     * @return Array
     */
    static function getProjectSettingWindowsFromXml($file)
    {
        $Dom  = self::getDomFromXml( $file );
        $Path = new \DOMXPath( $Dom );

        $windows = $Path->query( "//quiqqer/project/settings/window" );

        if ( !$windows->length ) {
            return array();
        }

        $result = array();

        for ( $i = 0, $len = $windows->length; $i < $len; $i++ ) {
            $result[] = $windows->item( $i );
        }

        return $result;
    }

    /**
     * Return the site types from a xml file
     * https://dev.quiqqer.com/quiqqer/quiqqer/wikis/Site-Xml
     *
     * @param unknown $file
     * @return boolean|array
     */
    static function getTypesFromXml($file)
    {
        $Dom   = self::getDomFromXml( $file );
        $sites = $Dom->getElementsByTagName( 'site' );

        if ( !$sites->length ) {
            return false;
        }


        $Sites = $sites->item( 0 );
        $types = $Sites->getElementsByTagName( 'types' );

        if ( !$types->length ) {
            return false;
        }

        $Types    = $types->item( 0 );
        $typeList = $Types->getElementsByTagName( 'type' );

        $result = array();

        for ( $c = 0; $c < $typeList->length; $c++ )
        {
            $Type = $typeList->item( $c );

            if ( $Type->nodeName == '#text' ) {
                continue;
            }

            $result[] = $Type;
        }

        return $result;
    }

    /**
     * Reads the tabs from an *.xml
     *
     * @param String $file
     * @return Array
     */
    static function getTabsFromXml($file)
    {
        return self::getTabsFromDom(
            self::getDomFromXml( $file )
        );
    }

    /**
     * Return the tabs from a DOMDocument
     *
     * @param DOMDocument $Dom
     * @return Array
     */
    static function getTabsFromDom(\DOMDocument $Dom)
    {
        $window = $Dom->getElementsByTagName( 'window' );

        if ( !$window->length ) {
            return array();
        }

        $Settings = $window->item(0);
        $tablist  = $Settings->getElementsByTagName( 'tab' );

        if ( !$tablist->length ) {
            return array();
        }

        $tabs = array();

        for ( $c = 0; $c < $tablist->length; $c++ )
        {
            $Tab = $tablist->item( $c );

            if ( $Tab->nodeName == '#text' ) {
                continue;
            }

            $tabs[] = $Tab;
        }

        return $tabs;
    }

    /**
     * Reads the template_engines from an *.xml
     *
     * @param String $file
     * @return Array
     */
    static function getTemplateEnginesFromXml($file)
    {
        $Dom      = self::getDomFromXml( $file );
        $template = $Dom->getElementsByTagName( 'template_engines' );

        if ( !$template->length ) {
            return array();
        }

        $Template = $template->item( 0 );
        $engines  = $Template->getElementsByTagName( 'engine' );

        if ( !$engines->length ) {
            return array();
        }

        $result = array();

        for ( $c = 0; $c < $engines->length; $c++ )
        {
            $Engine = $engines->item( $c );

            if ( $Engine->nodeName == '#text' ) {
                continue;
            }

            $result[] = $Engine;
        }

        return $result;
    }

    /**
     * Reads the editor from an *.xml
     *
     * @param String $file
     * @return Array
     */
    static function getWysiwygEditorsFromXml($file)
    {
        $Dom     = self::getDomFromXml( $file );
        $editors = $Dom->getElementsByTagName( 'editors' );

        if ( !$editors->length ) {
            return array();
        }

        $Editors = $editors->item(0);
        $list    = $Editors->getElementsByTagName( 'editor' );

        if ( !$list->length ) {
            return array();
        }

        $result = array();

        for ( $c = 0; $c < $list->length; $c++ )
        {
            $Editor = $list->item( $c );

            if ( $Editor->nodeName == '#text' ) {
                continue;
            }

            $result[] = $Editor;
        }

        return $result;
    }

    /**
     * Reads the widgets from an *.xml
     *
     * @param String $file
     * @return Array
     */
    static function getWidgetsFromXml($file)
    {
        $Dom     = self::getDomFromXml( $file );
        $widgets = $Dom->getElementsByTagName( 'widgets' );

        if ( !$widgets->length ) {
            return array();
        }

        $result = array();

        for ( $w = 0; $w < $widgets->length; $w++ )
        {
            $Widgets = $widgets->item( $w );

            if ( $Widgets->nodeName == '#text' ) {
                continue;
            }

            $list = $Widgets->getElementsByTagName( 'widget' );

            for ( $c = 0; $c < $list->length; $c++ )
            {
                $Widget = $list->item( $c );

                if ( $Widget->nodeName == '#text' ) {
                    continue;
                }

                // widget on another location
                if ( $Widget->getAttribute( 'src' ) )
                {
                    $file   = $Widget->getAttribute( 'src' );
                    $file   = \QUI\Utils\DOM::parseVar( $file );
                    $Widget = self::getWidgetFromXml( $file );

                    if ( $Widget ) {
                        $result[] = $Widget;
                    }

                    continue;
                }

                $Widget->setAttribute( 'name', md5( $file . $c ) );

                $result[] = $Widget;
            }
        }

        return $result;
    }

    /**
     * Reads the widget from an *.xml file
     *
     * @param String $file - path to the xml file
     * @return boolean|DOMNode
     */
    static function getWidgetFromXml($file)
    {
        $Dom    = self::getDomFromXml( $file );
        $widget = $Dom->getElementsByTagName( 'widget' );

        if ( !$widget->length ) {
            return false;
        }

        $Widget = $widget->item( 0 );
        $Widget->setAttribute( 'name', md5( $file ) );

        return $Widget;
    }

    /**
     * Save the setting to a xml specified config file
     *
     * @param unknown_type $file
     * @param unknown_type $params
     */
    static function setConfigFromXml($file, $params)
    {
        if ( \QUI::getUserBySession()->isSU() === false )
        {
            throw new \QUI\Exception(
                'You have no rights to edit the configuration.'
            );
        }

        // defaults prüfen
        $defaults = self::getConfigParamsFromXml( $file );
        $Config   = self::getConfigFromXml( $file );

        foreach ( $params as $section => $param )
        {
            foreach ( $param as $key => $value )
            {
                if ( !isset( $defaults[ $section ] ) ) {
                    continue;
                }

                if ( !isset( $defaults[ $section ][ $key ] ) ) {
                    continue;
                }

                $default = $defaults[ $section ][ $key ];

                if ( empty( $value ) ) {
                    $value = $default['default'];
                }

                // typ prüfen
                switch ( $default['type'] )
                {
                    case 'bool':
                        $value = \QUI\Utils\Bool::JSBool( $value );

                        if ( $value )
                        {
                            $value = 1;
                        } else
                        {
                            $value = 0;
                        }
                    break;

                    case 'int':
                        $value = (int)$value;
                    break;

                    case 'string':
                        $value = \QUI\Utils\Security\Orthos::cleanHTML( $value );
                    break;
                }

                $Config->set( $section, $key, $value );
            }
        }

        $Config->save();
    }

    /**
     * Import a xml array to the database
     * the Array must come from self::getDataBaseFromXml
     *
     * @param Array $dbfields - array with db fields
     */
    static function importDataBase($dbfields)
    {
        $Table    = \QUI::getDataBase()->Table();
        $projects = \QUI\Projects\Manager::getConfig()->toArray();

        // globale tabellen erweitern / anlegen
        if ( isset( $dbfields['globals'] ) )
        {
            foreach ( $dbfields['globals'] as $table )
            {
                $tbl = \QUI::getDBTableName( $table['suffix'] );

                $Table->appendFields( $tbl, $table['fields'] );

                if ( isset( $table['primary'] ) ) {
                    $Table->setPrimaryKey( $tbl, $table['primary'] );
                }

                if ( isset( $table['index'] ) ) {
                    $Table->setIndex( $tbl, $table['index'] );
                }

                if ( isset( $table[ 'auto_increment' ] ) ) {
                    $Table->setAutoIncrement( $tbl, $table[ 'auto_increment' ] );
                }
            }
        }

        // projekt tabellen erweitern / anlegen
        if ( isset( $dbfields['projects'] ) )
        {
            foreach ( $dbfields['projects'] as $table )
            {
                if ( !isset( $table['suffix'] ) ) {
                    continue;
                }

                $suffix = $table['suffix'];
                $fields = $table['fields'];

                $fields = array(
                    'id' => 'bigint(20) NOT NULL'
                ) + $fields;


                // Projekte durchgehen
                foreach ( $projects as $name => $params )
                {
                    $langs = explode( ',', $params['langs'] );

                    foreach ( $langs as $lang )
                    {
                        $tbl = \QUI::getDBTableName( $name .'_'. $lang .'_'. $suffix );

                        $Table->appendFields( $tbl, $fields );

                        if ( isset( $table['primary'] ) ) {
                            $Table->setPrimaryKey( $tbl, $table['primary'] );
                        }

                        if ( isset( $table['index'] ) ) {
                            $Table->setIndex( $tbl, $table['index'] );
                        }

                        if ( isset( $table[ 'auto_increment' ] ) ) {
                            $Table->setAutoIncrement( $tbl, $table[ 'auto_increment' ] );
                        }
                    }
                }
            }
        }

        // php executes
        if ( isset( $dbfields['execute'] ) )
        {
            foreach ( $dbfields['execute'] as $exec )
            {
                $exec = str_replace( '\\\\', '\\', $exec );

                if ( !is_callable( $exec ) )
                {
                    \QUI\System\Log::write( $exec .' not callable', 'error' );
                    continue;
                }

                call_user_func( $exec );
            }
        }
    }

    /**
     * Import a database.xml
     *
     * @param String $xmlfile - Path to the file
     */
    static function importDataBaseFromXml($xmlfile)
    {
        $dbfields = self::getDataBaseFromXml( $xmlfile );

        if ( !count( $dbfields ) ) {
            return;
        }

        self::importDataBase( $dbfields );
    }

    /**
     * Import a permissions.xml
     *
     * @param String $xmlfile - Path to the file
     * @param String $src - [optional] the source for the permissions
     */
    static function importPermissionsFromXml($xmlfile, $src='')
    {
        $Manager = \QUI::getPermissionManager();
        $Manager->importPermissionsFromXml( $xmlfile, $src );
    }
}
