<?php

/**
 * Cache Manager
 * Einfacher Zugriff auf verschiedenen Cachearten.
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.system.cache
 */

class System_Cache_Manager
{
    /**
     * Cache Manager Configs
     * @var \QUI\Config
     */
    static $Config = null;

    /**
     * Stash Object
     * @var Stash\Pool
     */
    static $Stash = null;

    /**
     * the stash multihandler
     * @var Stash\Driver\MultiHandler
     */
    static $Handler = null;

    /**
     * all stash cache objects
     * @var array
     */
    static $handlers = null;

    /**
     * Cache Settings
     *
     * @return \QUI\Config
     */
    static function getConfig()
    {
        if ( !self::$Config )
        {
            try
            {
                self::$Config = QUI::getConfig( 'etc/cache.ini' );

            } catch ( \QUI\Exception $Exception )
            {
                file_put_contents( CMS_DIR .'etc/cache.ini', '' );

                self::$Config = QUI::getConfig( 'etc/cache.ini' );
            }
        }

        return self::$Config;
    }

    /**
     * Create the Stash Cache Handler
     *
     * @param String $key - cache name, cache key
     * @return Stash\Item
     */
    static function getStash($key=false)
    {
        // pfad erstellen falls nicht erstellt ist
        if ( !is_dir( VAR_DIR .'cache/stack/' ) ) {
            \QUI\Utils\System\File::mkdir( VAR_DIR .'cache/stack/' );
        }

        if ( $key !== false ) {
            $key = 'qui/'. $key;
        }

        if ( !is_null( self::$Stash ) ) {
            return self::$Stash->getItem( $key );
        }


        $Config = self::getConfig();

        $handlers     = array();
        $confhandlers = $Config->get( 'handlers' );

        if ( empty( $confhandlers ) ) {
            $confhandlers['filesystem'] = 1;
        }

        foreach ( $confhandlers as $confhandler => $bool )
        {
            if ( !$bool ) {
                continue;
            }

            switch ( $confhandler )
            {
                case 'apc':
                    $conf   = $Config->get( 'apc' );
                    $params = array(
                        'namespace' => 'pcsg'
                    );

                    if ( isset( $conf['namespace'] ) ) {
                        $params['namespace'] = $conf['namespace'];
                    }

                    if ( isset($conf['ttl']) ) {
                        $params['ttl'] = $conf['ttl'];
                    }

                    try
                    {
                        array_unshift( $handlers, new Stash\Driver\Apc( $params ) );
                    } catch (StashError $e)
                    {

                    }

                break;

                case 'filesystem':
                    $conf   = $Config->get('filesystem');
                    $params = array(
                        'path' => VAR_DIR .'cache/stack/'
                    );

                    if (!empty($conf['path']) && is_dir($conf['path'])) {
                        $params['path'] = $conf['path'];
                    }

                    try
                    {
                        $handlers[] = new Stash\Driver\FileSystem( $params );
                    } catch (StashError $e)
                    {

                    }
                break;

                case 'sqlite':
                    $conf   = $Config->get('sqlite');
                    $params = array(
                        'path' => VAR_DIR .'cache/stack/'
                    );

                    if ( !empty( $conf['path'] ) && is_dir( $conf['path'] ) ) {
                        $params['path'] = $conf['path'];
                    }

                    try
                    {
                        $handlers[] = new Stash\Driver\Sqlite( $params );
                    } catch (StashError $e)
                    {

                    }
                break;

                case 'memcache':
                    // defaults
                    $options = array(
                        'prefix_key'           => 'pcsg',
                        'libketama_compatible' => true,
                        'cache_lookups'        => true,
                        'serializer'           => 'json'
                    );

                    // servers
                    $scount  = $Config->get( 'memcache', 'servers' );
                    $servers = array();

                    for ( $i = 1; $i <= $scount; $i++ )
                    {
                        $section = 'memcache'.$i;

                        $servers[] = array(
                            $Config->get( $section, 'host' ),
                            $Config->get( $section, 'port' ),
                            $Config->get( $section, 'weight' )
                        );
                    }

                    $options['servers'] = $servers;

                    $conf = $Config->get('memcache');

                    if ( isset($conf['prefix_key']) && !empty($conf['prefix_key']) ) {
                        $options['prefix_key'] = $conf['prefix_key'];
                    }

                    if ( isset($conf['libketama_compatible']) && !empty($conf['libketama_compatible']) ) {
                        $options['libketama_compatible'] = $conf['libketama_compatible'];
                    }

                    if ( isset($conf['cache_lookups']) && !empty($conf['cache_lookups']) ) {
                        $options['cache_lookups'] = $conf['cache_lookups'];
                    }

                    if ( isset($conf['serializer']) && !empty($conf['serializer']) ) {
                        $options['serializer'] = $conf['serializer'];
                    }

                    try
                    {
                        array_unshift( $handlers, new Stash\Driver\Memcached( $params ) );
                    } catch ( StashError $e )
                    {

                    }
                break;
            }
        }

        $Handler = new Stash\Driver\Composite(array(
            'drivers' => $handlers
        ));

        $Stash = new Stash\Pool( $Handler );


        self::$Stash    = $Stash;
        self::$handlers = $handlers;

        return self::$Stash->getItem( $key );
    }

    /**
     * Gibt den Stash\Driver\Composite oder den Stash\Driver zurück
     *
     * @param String $type = optional: bestimmten Cache Handler bekommen
     * @param String $key - cache key, optional
     *
     * @return Stash\Driver\Composite | Stash\Driver | false
     */
    static function getHandler($type=false, $key=false)
    {
        if ( $type != false )
        {
            $handlers = self::$handlers;

            foreach ( $handlers as $Handler )
            {
                if ( get_class( $Handler ) == $type ) {
                    return $Handler;
                }
            }

            return false;
        }

        if ( !is_null( self::$Handler ) ) {
            return self::$Handler;
        }

        return self::$Handler;
    }

    /**
     * Daten in den Cache setzen
     *
     * @param String $name
     * @param String $data
     * @param int|DateTime|null $time -> sekunden oder datetime
     *
     * @return Bool
     */
    static function set($name, $data, $time=null)
    {
        return self::getStash( $name )->set( $data, $time );
    }

    /**
     * Daten aus dem Cache bekommen
     *
     * @param String $name
     * @return unknown_type
     *
     * @throws System_Cache_Exception
     */
    static function get($name)
    {
        $Item = self::getStash( $name );
        $data = $Item->get();

        if ( $Item->isMiss() ) {
            throw new \System_Cache_Exception( 'Cache existiert nicht', 404 );
        }

        return $data;
    }

    /**
     * Cache leeren
     *
     * @param String $key - optional, falls kein Key übergeben wird, wird komplett geleert
     */
    static function clear($key=false)
    {
        self::getStash( $key )->clear();
    }

    /**
     * The purge function removes stale data from the cache backends while leaving current data intact.
     * Depending on the size of the cache and the specific drivers in use this can take some time,
     * so it is best called as part of a separate maintenance task or as part of a cron job.
     */
    static function purge()
    {
        self::$Stash->purge();
    }

    /**
     * Löscht den kompletten CMS Cache
     */
    static function clearAll()
    {
        \QUI\Utils\System\File::unlink( VAR_DIR .'cache/' );

        self::clear();
    }
}

?>