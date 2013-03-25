<?php

/**
 * This file contains QUI_Package_Manager
 */

/**
 * Package Manager for the QUIQQER System
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 */

class QUI_Package_Manager
{
    /**
     * Package Directory
     * @var String
     */
    protected $_dir;

    /**
     * VAR Directory for composer
     * eq: here are the cache and the quiqqer composer.json file
     *
     * @var String
     */
    protected $_vardir;

    /**
     * Packaglist - installed packages
     * @var Array
     */
    protected $_composer_json;

    /**
     * exec command to the composer.phar file
     * @var String
     */
    protected $_composer_exec;

    /**
     * Packaglist - installed packages
     * @var Array
     */
    protected $_list = false;

    /**
     * Composer exec
     * @var String
     */
    protected $_exec = false;

    /**
     * temporary require packages
     * @var Array
     */
    protected $_require = array();

    /**
     * constructor
     */
    public function __construct()
    {
        $this->_dir    = CMS_DIR .'packages/';
        $this->_vardir = VAR_DIR .'composer/';

        $this->_composer_json = $this->_vardir .'composer.json';

        Utils_System_File::mkdir( $this->_vardir );

        // exec
        $this->_composer_exec = 'cd '. CMS_DIR .'; php composer.phar';

        $exec = $this->_composer_exec .' --working-dir="'. $this->_vardir .'" ';
        exec( $exec, $result );

        if ( count( $result ) ) {
            $this->_exec = $exec;
        }

        if ( !file_exists( $this->_composer_json ) ) {
            $this->_createComposerJSON();
        }
    }

    /**
     * Create the composer.json file for the system
     */
    protected function _createComposerJSON()
    {
        $template = file_get_contents(
            dirname( __FILE__ ) .'/composer.tpl'
        );

        // make the repository list
        $servers      = $this->getServerList();
        $repositories = array();

        foreach ( $servers as $server => $params )
        {
            if ( $server == 'packagist' ) {
                continue;
            }

            if ( !isset($params['active']) || $params['active'] != 1 ) {
                continue;
            }

               $repositories[] = array(
                   'type' => $params['type'],
                   'url'  => $server
               );
        }

        if ( isset( $servers['packagist'] ) &&
             $servers['packagist']['active'] == 0 )
        {
            $repositories[] = array(
                'packagist' => false
            );
        }


        $template = str_replace(
            '{$repositories}',
            json_encode( $repositories ),
            $template
        );

        $template = str_replace(
            '{$PACKAGE_DIR}',
            OPT_DIR,
            $template
        );

        $template = str_replace(
            '{$VAR_COMPOSER_DIR}',
            $this->_vardir,
            $template
        );

        $template = str_replace(
            '{$LIB_DIR}',
            LIB_DIR,
            $template
        );

        // standard require
        $list    = $this->_getList();
        $require = $this->_require;

        $require["php"] = ">=5.3.2";

        foreach ( $list as $entry ) {
            $require[ $entry['name'] ] = $entry['version'];
        }

        $template = str_replace(
            '{$REQUIRE}',
            json_encode( $require ),
            $template
        );

        if ( file_exists( $this->_composer_json ) ) {
            unlink( $this->_composer_json );
        }

        file_put_contents( $this->_composer_json, $template );
    }

    /**
     * Package Methods
     */

    /**
     * internal get list method
     * return all installed packages and create the internal package list cache
     *
     * @return Array
     */
    protected function _getList()
    {
        if ( $this->_list ) {
            return $this->_list;
        }

        $installed_file = $this->_dir .'composer/installed.json';

        if ( !file_exists( $installed_file ) ) {
            return array();
        }

        $data = file_get_contents( $installed_file );
        $list = json_decode( $data, true );

        $this->_list = array();

        if ( is_array( $list ) ) {
            $this->_list = $list;
        }

        return $this->_list;
    }

    /**
     * Return the installed packages
     *
     * @param {Array} $params - [optional] search / limit params
     * @return Array
     */
    public function getInstalled($params=array())
    {
        $list   = $this->_getList();
        $result = array();

        foreach ( $list as $package )
        {
            if ( isset( $params['type'] ) &&
                 !empty( $params['type'] ) &&
                 $params['type'] != $package['type'] )
            {
                continue;
            }

            $result[] = $package;
        }

        if ( isset( $params['limit'] ) && isset( $params['page'] ) )
        {
            $limit = (int)$params['limit'];
            $page  = (int)$params['page'];

            return Utils_Grid::getResult( $result, $page, $limit );
        }

        return $result;
    }

    /**
     * Install Package
     *
     * @param String $package
     */
    public function install($package)
    {
        $this->_require[ $package ] = 'dev-master';
        $this->_createComposerJSON();

        if ( $this->_exec ) {
            exec( $this->_exec .'update "'. $package .'" 2>&1', $exec_result );
        }
    }

    /**
     * Return the params of an installed package
     *
     * @param String $package
     * @return Array
     */
    public function getPackage($package)
    {
        $list = $this->_getList();

        foreach ( $list as $pkg )
        {
            if ( !isset( $pkg['name'] ) ) {
                continue;
            }

            if ( $pkg['name'] == $package )
            {
                $pkg['dependencies'] = $this->getDependencies( $package );

                return $pkg;
            }
        }

        // show command
        if ( $this->_exec )
        {
            $params  = array();
            $package = Utils_Security_Orthos::clearShell( $package );

            exec( $this->_exec .'show "'. $package .'"', $exec_result );

            foreach ( $exec_result as $key => $line )
            {
                if ( strpos( $line, '[InvalidArgumentException]' ) !== false ) {
                    break;
                }

                if ( strpos( $line, 'Fatal error' ) !== false ) {
                    break;
                }

                if ( strpos( $line, ':' ) )
                {
                    $parts = explode( ':', $line );

                    $key   = trim( $parts[0] );
                    $value = trim( $parts[1] );

                    if ( $key == 'descrip.' ) {
                        $key = 'description';
                    }

                    $params[ $key ] = $value;
                }

                if ( $line == 'requires' )
                {
                    $_temp = $exec_result;

                    $params[ 'require' ] = array_slice($_temp, $key+1);
                }
            }

            return $params;
        }


        return array();
    }

    /**
     * Return the dependencies of a package
     *
     * @param String $package - package name
     */
    public function getDependencies($package)
    {
        $list   = $this->_getList();
        $result = array();

        foreach ( $list as $pkg )
        {
            if ( !isset( $pkg['require'] ) ||
                 empty( $pkg['require'] ) )
            {
                continue;
            }

            if ( isset( $pkg['require'][ $package ] ) ) {
                $result[] = $pkg['name'];
            }
        }

        return $result;
    }

    /**
     * Search a string in the repository
     *
     * @param String $str - search string
     * @return Array
     */
    public function searchPackage($str)
    {
        $result = array();
        $str    = Utils_Security_Orthos::clearShell( $str );
        $list   = $this->_getList();

        if ( $this->_exec )
        {
            exec( $this->_exec .'search "'. $str .'"', $exec_result );

            foreach ( $exec_result as $entry )
            {
                $expl = explode( ' ', $entry, 2 );

                if ( isset( $expl[0] ) && isset( $expl[1] ) ) {
                    $result[ $expl[0] ] = $expl[1];
                }
            }
        }

        return $result;
    }

    /**
     * Update Server Methods
     */

    /**
     * Refresh the server list in the var dir
     */
    public function refreshServerList()
    {
        $this->_createComposerJSON();
    }

    /**
     * Return the server list
     *
     * @return Array
     */
    public function getServerList()
    {
        try
        {
            return QUI::getConfig( 'etc/source.list.ini' )->toArray();

        } catch ( QException $Exception )
        {

        }

        return array();
    }

    /**
     * Activate or Deactivate a server
     *
     * @param String $server
     * @param Bool $status
     */
    public function setServerStatus($server, $status)
    {
        $Config  = QUI::getConfig( 'etc/source.list.ini' );
        $status = (bool)$status ? 1 : 0;

        $Config->setValue( $server, 'active', $status );
        $Config->save();

        $this->_createComposerJSON();
    }

    /**
     * Add a server to the update-server list
     *
     * @param String $server - Server, IP, Host
     * @params Array $params - Server Parameter
     */
    public function addServer($server, $params=array())
    {
        if ( empty( $server ) ) {
            return;
        }

        if ( !is_array( $params ) ) {
            return;
        }


        $Config = QUI::getConfig( 'etc/source.list.ini' );
        $Config->setValue( $server, 'active', 0 );

        if ( isset( $params['type'] ) ) {
            $Config->setValue( $server, 'type', $params['type'] );
        }

        $Config->save();

        $this->_createComposerJSON();
    }

    /**
     * Remove a Server completly from the update-server list
     *
     * @param String|Array $server
     */
    public function removeServer($server)
    {
        $Config = QUI::getConfig( 'etc/source.list.ini' );

        if ( is_array( $server ) )
        {
            foreach ( $server as $entry ) {
                $Config->del( $entry );
            }
        } else
        {
            $Config->del( $server );
        }

        $Config->save();

        $this->_createComposerJSON();
    }

/**
 * Update methods
 */

    /**
     * Check for updates
     * @throws \QEXception
     */
    public function checkUpdates()
    {
        if ( $this->_exec )
        {
            exec( $this->_exec .'update  --dry-run', $exec_result );

            $last = end( $exec_result );

            if ( $last == 'Nothing to install or update' ) {
                return array();
            }

            $packages = array();

            foreach ( $exec_result as $line )
            {
                if ( strpos( $line, '-' ) === false ||
                     strpos( $line, '/' ) === false ||
                     strpos( $line, '(' ) === false )
                {
                    continue;
                }

                preg_match( '#Updating ([^ ]*) #i', $line, $package );
                preg_match_all( '#\(([^\)]*)\)#', $line, $versions );

                if ( isset( $package[1] ) ) {
                    $package = $package[1];
                }

                $from = '';
                $to   = '';

                if ( isset( $versions[ 1 ] ) )
                {
                    if ( isset( $versions[ 1 ][ 0 ] ) ) {
                        $from = $versions[ 1 ][ 0 ];
                    }

                    if ( isset( $versions[ 1 ][ 1 ] ) ) {
                        $to = $versions[ 1 ][ 1 ];
                    }
                }

                $packages[] = array(
                    'package' => $package,
                    'from'    => $from,
                    'to'      => $to
                );
            }

            return $packages;
        }

        throw new \QEXception(
            \QUI::getLocale()->get(
                'quiqqer/system',
                'exception.packages.update.check'
            )
        );
    }

    /**
     * Update a package or the entire system
     *
     * @param String|false $package - optional, package name, if false, it updates the complete system
     * @param Bool $overwrite_uncomit_changes - default=false, if changes on the filesystem exist, than overwrite it or not
     *
     * @throws QException
     *
     * @todo if exception uncommited changes -> own error message
     * @todo if exception uncommited changes -> interactive mode
     */
    public function update($package=false)
    {
        if ( $this->_exec )
        {
            $exec = $this->_exec .'update 2>&1';

            if ( $package )
            {
                $package = Utils_Security_Orthos::clearShell( $package );
                $exec    = $this->_exec .'update "'. $package .'" 2>&1';
            }

            \System_Log::write( 'Execute: '. $exec );

            exec( $exec, $output );

            // exception?
            foreach ( $output as $key => $msg )
            {
                if ( strpos( $msg, 'Exception' ) )
                {
                    throw new QException(
                        $output[ $key+1 ]
                    );
                }
            }

            return true;
        }
    }

    /**
     * Update a packe or the entire system from a package archive
     *
     * @param String $packagepath - path to the ZIP archive
     * @throws \QEXception
     */
    public function updatePackage($packagepath)
    {
        if ( !file_exists( $packagepath ) )
        {
            throw new \QException(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.packages.update.archive.not.found'
                )
            );
        }

        // extract the archive
        $folder = \QUI::getTemp()->createFolder();

        \Utils_Packer_Zip::unzip( $packagepath, $folder );

        // read composer file
        $composer     = $folder .'composer.json';
        $repositories = VAR_DIR .'repository/bin/';

        if ( !file_exists( $composer ) )
        {
            throw new \QException(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.no.quiqqer.update.archive'
                )
            );
        }

        $package     = json_decode( file_get_contents( $composer ), true );
        $package_dir = $repositories . $package['name'] .'/';
        $update_file = $package_dir . $package['version'] .'.zip';

        if ( file_exists( $update_file ) ) {
            unlink( $update_file );
        }

        \Utils_System_File::mkdir( $package_dir );
        \Utils_System_File::move( $packagepath, $update_file );


        // create packages.json
        $server_json     = $repositories .'packages.json';
        $server_packages = '';

        if ( file_exists( $server_json ) ) {
            $server_packages = json_decode( $server_json, true );
        }

        if ( !is_array( $server_packages ) ||
             !isset( $server_packages[ 'packages' ] ) )
        {
            $server_packages = array(
                'packages' => array()
            );
        }

        $version = $package['version'];

        $server_packages[ 'packages' ] = array(
            $package['name'] => array(
                $version => array(
                    "name" => $package[ 'name' ],
                    "version" => $version,
                    "dist" => array(
                        "url"  => HOST .'/'. str_replace( VAR_DIR, URL_VAR_DIR, $update_file ),
                        "type" => "zip"
                    ),

                    "require"     => $package['require'],
                    "type"        => $package['type'],
                    "description" => $package['description']
                )
            )
        );

        file_put_contents( $server_json, json_encode( $server_packages ) );

        // create composer json file for working dir
        $template = file_get_contents(
            dirname( __FILE__ ) .'/composer.tpl'
        );

        // make the repository list
        $list = array(
            'packagist' => false,
            array(
                "type" => "composer",
                "url"  => HOST .'/'. str_replace( VAR_DIR, URL_VAR_DIR, $repositories )
            )
        );

        $template = str_replace(
            '{$repositories}',
            json_encode( $list ),
            $template
        );

        $template = str_replace(
            '{$PACKAGE_DIR}',
            OPT_DIR,
            $template
        );

        $template = str_replace(
            '{$VAR_COMPOSER_DIR}',
            $this->_vardir,
            $template
        );

        $template = str_replace(
            '{$LIB_DIR}',
            LIB_DIR,
            $template
        );


        if ( file_exists( $repositories .'composer.json' ) ) {
            unlink( $repositories .'composer.json' );
        }

        file_put_contents( $repositories .'composer.json', $template );

        // make an update from the repository archive source
        $exec = $this->_composer_exec .' --working-dir="'. $repositories .'" ';
        exec( $exec, $result );

        if ( !count( $result ) )
        {
            throw new \QException(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.packages.exec.not.found.composer'
                )
            );
        }

        exec( $exec .'update  --dry-run', $exec_result );
        $last = end( $exec_result );


        if ( $last == 'Nothing to install or update' )
        {
            throw new QException(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.packages.update.version.not.found'
                )
            );
        }

        exec( $exec .'update', $exec_result );
    }
}

?>