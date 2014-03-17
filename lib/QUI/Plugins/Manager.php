<?php

/**
 * This file contains \QUI\Plugins\Manager
 */

namespace QUI\Plugins;

/**
 * Plugin Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.plugins
 *
 * @todo Plugin Methoden Benennung sollte bisschen überdacht werden
 */

class Manager extends \QUI\QDOM
{
    /**
     * loaded plugins
     * @var array
     */
    protected $_plugins = array();

    /**
     * plugin config
     * @var \QUI\Config
     */
    protected $_Config = null;

    /**
     * Loaded flag
     * @var Bool
     */
    protected $_loaded = false;

    /**
     * loaded group extentions
     * @var array|Bool
     */
    protected $_groupplugins = false;


    /**
     * Konstruktor
     * Ließt Plugin Config ein
     */
    public function __construct()
    {
        $this->_Config = \QUI::getConfig( 'etc/plugins.ini' );
    }

    /**
     * Führt das Setup aller aktiven Plugins auf das Projekt aus
     *
     * @param \QUI\Projects\Project $Project
     */
    public function setup(\QUI\Projects\Project $Project)
    {
        $plugins = $this->get();

        foreach ( $plugins as $Plugin ) {
            $Plugin->install( $Project );
        }
    }

    /**
     * Aktiviert ein Plugin und führt die Installation durch
     *
     * @param Plugin $Plugin
     */
    public function activate(Plugin $Plugin)
    {
        $Plugin->install();

        $Config = $this->_Config;
        $Config->set( $Plugin->getAttribute('name'), null, 1 );
        $Config->save();

        $this->clearCache();
    }

    /**
     * Deaktiviert ein Plugin
     *
     * Löscht die Tabellen nicht,
     * dies wird erst bei einem Löschen des Plugins gemacht (uninstall)
     *
     * @param Plugin $Plugin
     */
    public function deactivate(Plugin $Plugin)
    {
        $Config = $this->_Config; /* @var $Config Config */

        $Config->del( $Plugin->getAttribute( 'name' ) );
        $Config->save();

        $this->clearCache();
    }

    /**
     * Gibt alle Plugins zurück die verfügbar sind
     *
     * @param Bool $order		- Sortiert bekommen (optional)
     * @param Bool $onlynames	- Nur Namen, keine Objekte (optional)
     *
     * @return Array
     */
    public function getAvailablePlugins($order=false, $onlynames=false)
    {
        $list   = \QUI\Utils\System\File::readDir( OPT_DIR );
        $result = array();

        foreach ( $list as $dir )
        {
            if ( !is_dir( OPT_DIR . $dir ) || strpos( $dir, '.' ) === 0 ) {
                continue;
            }

            if ( $onlynames )
            {
                $result[] = $dir;
                continue;
            }

            $result[] = $this->get( $dir );
        }

        if ( $order )
        {
            if ( $onlynames )
            {
                sort( $result );
            } else
            {
                $_result = array();

                foreach ( $result as $Plugin )
                {
                    $c = $Plugin->getAttribute( 'config' );
                    $_result[ $c['name'] ] = $Plugin;
                }

                ksort( $_result );
                $result = array();

                foreach ( $_result as $Plugin ) {
                    $result[] = $Plugin;
                }
            }
        }

        return $result;
    }

    /**
     * Return all inactive Plugins
     *
     * @param Bool $order - get the list ordered
     * @return Array
     */
    public function getInactivePlugins($order=false)
    {
        $Config = $this->_Config; /* @var $Config \QUI\Config */
        $list   = $this->getAvailablePlugins( $order );

        $result = array();

        foreach ( $list as $Plugin )
        {
            if ( $Config->getSection( $Plugin->getAttribute('name') ) === false ) {
                $result[] = $Plugin;
            }
        }

        return $result;
    }

    /**
     * Gibt alle Seitentypen zurück die verfügbar sind
     *
     * @param \QUI\Projects\Project $Project - optional
     * @return Array
     */
    public function getAvailableTypes($Project=false)
    {
        $types     = array();
        $installed = \QUI::getPackageManager()->getInstalled();

        foreach ( $installed as $package )
        {
            $name    = $package['name'];
            $siteXml = OPT_DIR . $name .'/site.xml';

            if ( !file_exists( $siteXml ) ) {
                continue;
            }

            $typeList = \QUI\Utils\XML::getTypesFromXml( $siteXml );

            foreach ( $typeList as $Type )
            {
                $types[ $name ][] = array(
                    'type' => $name .':'. $Type->getAttribute('type'),
                    'icon' => $Type->getAttribute('icon')
                );
            }
        }

        ksort( $types );

        // standard to top
        $types = array_reverse( $types, true );

        $types['standard'] = array(
            'type' => 'standard',
            'icon' => 'icon-file-alt'
        );

        $types = array_reverse( $types, true );

        return $types;
    }

    /**
     * Löscht den Plugin Cache plus den Projekt Cache
     */
    static function clearCache()
    {
        \QUI\Cache\Manager::clearAll();
    }

    /**
     * Erzeugt ein Cachefile vom Plugin
     *
     * @param unknown_type $class
     * @param unknown_type $Plugin
     */
    protected function _createCache($class, $Plugin)
    {
        // Kein Cache für Standard Plugins
        if ( $class == 'QUI\\Plugins\\Plugin' ) {
            return false;
        }

        \QUI\Cache\Manager::set( 'plugin-'. $class, $Plugin->getAttributes() );
    }

    /**
     * Gibt das Plugin zurück wenn ein Cachefile existiert
     *
     * @param unknown_type $class
     * @return unknown
     *
     * @todo mal überdenken
     */
    protected function _getCache($class)
    {
        try
        {
            $attributes = \QUI\Cache\Manager::get( 'plugin-'. $class );

            if ( empty( $attributes ) ) {
                return false;
            }

        } catch ( \QUI\Cache\Exception $e )
        {
            return false;
        }

        if ( isset($attributes['_file_']) &&
             !class_exists($class) &&
             file_exists($attributes['_file_']) )
        {
            require_once $attributes['_file_'];
        }

        if ( !class_exists( $class ) ) {
            throw new \QUI\Exception('Konnte Plugin '. $class .' nicht laden');
        }

        $Plugin = new $class();
        $Plugin->setAttributes( $attributes );

        return $Plugin;
    }

    /**
     * Plugin bekommen
     *
     * @param String $name - Name des Plugins
     * @param String $type - Seitentype
     *
     * @return Plugin
     * @todo Unbedingt ändern, get gibt nur aktiv Plugins zurück -> überarbeiten
     */
    public function get($name=false, $type=false)
    {
        if ( $name === false ) {
            return $this->_getAll();
        }

        $class = 'Plugin_'. $name;

        // Falls das Plugin schon mal gehohlt wurde, dann gleich zurück geben
        if ( isset( $this->_plugins[ $class ] ) ) {
            return $this->_plugins[ $class ];
        }

        // Falls Plugin schon im Cache steckt
        $Plugin = $this->_getCache( $class );

        if ( $Plugin )
        {
            $this->_plugins[ $class ] = $Plugin;
            return $Plugin;
        }

        $dir = str_replace( 'Plugin_', '', $name );
        $dir = explode( '_', $dir );

        $last = end( $dir );
        $dir  = implode( $dir, '/' );

        // Pluginfile laden falls noch nicht getan
        $f_plg = OPT_DIR . $dir .'/'. ucfirst( $last ) .'.php';

        if ( !class_exists( $class ) && file_exists( $f_plg ) ) {
            require_once $f_plg;
        }

        if ( !class_exists( $class ) &&
             file_exists( OPT_DIR . $name .'/'. ucfirst( $name ) .'.php' ) )
        {
            $f_plg = OPT_DIR . $name .'/'. ucfirst($name).'.php';
            require_once $f_plg;
        }

        if ( !class_exists( $class ) ) {
            $class = '\\QUI\\Plugins\\Plugin';
        }

        $Plugin = new $class();
        $Plugin->setAttribute( 'name', $name );
        $Plugin->setAttribute( '_file_', $f_plg );
        $Plugin->setAttribute( '_folder_', OPT_DIR . $dir .'/' );

        $config = $this->_Config->toArray();

        if ( isset($config[$name]) && $config[$name] == 1 ) {
            $Plugin->setAttribute( 'active', 1 );
        }

        $Plugin->load();

        $this->_plugins[ $class ] = $Plugin;

        // Cache fürs Plugin erzeugen
        $this->_createCache( $class, $Plugin );

        return $Plugin;
    }

    /**
     * Befindet sich das Plugin im System
     *
     * @param unknown_type $plugin
     * @throws \QUI\Exception
     */
    public function existPlugin($plugin)
    {
        if ( is_dir( OPT_DIR . $plugin ) ) {
            return true;
        }

        throw new \QUI\Exception( 'Plugin nicht gefunden', 404 );
    }

    /**
     * Gibt dir das Plugin zurück wenn es verfügbar ist
     *
     * @param String $plugin
     * @return Plugin
     *
     * @throws \QUI\Exception
     */
    public function getPlugin($plugin)
    {
        $Plugin = $this->get( $plugin );

        if ( $Plugin->getAttribute('active') ) {
            return $Plugin;
        }

        throw new \QUI\Exception( 'Plugin nicht verfügbar', 403 );
    }

    /**
     * Ist das Plugin aktiv?
     *
     * @param unknown_type $plugin
     * @return Bool
     */
    public function isAvailable($plugin)
    {
        $config = $this->_Config->toArray();

        if ( isset( $config[ $plugin ] ) && $config[ $plugin ] == 1 ) {
            return true;
        }

        return false;
    }

    /**
     * Gibt das zuständige Plugin über den Seitetyp zurück
     *
     * @param String $type
     * @return Plugin
     */
    public function getPluginByType($type)
    {
        return $this->get(
           str_replace( '/', '_', $type ),
           $type
        );
    }

    /**
     * Get the full Type name
     *
     * @param String $type - site type
     * @return String
     */
    public function getTypeName($type)
    {
        if ( $type == 'standard' || empty( $type ) ) {
            return 'Standard';
        }

        \QUI\System\Log::write( $type );


        return $type;

        /*
        $type    = explode( '/', $type );
        $plugins = $this->get();

        foreach ( $plugins as $Plugin )
        {
            if ( $Plugin->getAttribute( 'name' ) != $type[ 0 ] ) {
                continue;
            }

            if ( !isset( $type[1] ) ) {
                return $Plugin->getAttribute('name');
            }

            $types  = $Plugin->getAttribute('types');
            $config = $Plugin->getAttribute('config');

            return $config['name'] .' / '. $types[ $type[1] ]['name'];
        }

        throw new \QUI\Exception( 'Type not found', 404 );
        */
    }

    /**
     * Gibt alle Plugins zurück
     *
     * @return Array
     */
    public function _getAll()
    {
        if ( $this->_loaded ) {
            return $this->_plugins;
        }

        $config = $this->_Config->toArray();

        foreach ( $config as $key => $value ) {
            $this->get( $key );
        }

        $this->_loaded = true;
        return $this->_plugins;
    }

    /**
     * Gibt die Plugin Gruppen Erweiterungen zurück
     *
     * @return Array
     * @deprecated
     */
    public function getListOfGroupPlugins()
    {
        return array();


        if ($this->_groupplugins) {
            return $this->_groupplugins;
        }

        $this->_groupplugins = array();

        $config = $this->_Config->toArray();

        foreach ($config as $entry => $value)
        {
            if (!file_exists(OPT_DIR . $entry .'/Groups.php')) {
                continue;
            }

            require_once OPT_DIR . $entry .'/Groups.php';

            $class = 'Plugin'. ucfirst($entry) .'GroupExtend';

            if (!class_exists($class)) {
                continue;
            }

            $this->_groupplugins[] = new $class();
        }

        return $this->_groupplugins;
    }
}
