<?php

/**
 * This file contains \QUI\Package\Manager
 */

namespace QUI\Package;

// Use the Composer classes
if ( !defined('STDIN') ) {
    define( 'STDIN', fopen("php://stdin","r") );
}

use Composer\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;


/**
 * Package Manager for the QUIQQER System
 *
 * @author www.pcsg.de (Henning Leutz)
 * @event onOutput [ String $message ]
 */

class Manager
{
    const CACHE_NAME_TYPES = 'qui/packages/types';

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
     * Can composer execute via bash? shell?
     * @var Bool
     */
    protected $_exec = false;

    /**
     * temporary require packages
     * @var Array
     */
    protected $_require = array();

    /**
     *
     * @var String
     */
    protected $_stability = 'stable';

    /**
     * Composer Application
     * @var Application
     */
    protected $_Application;

    /**
     * internal event manager
     * @var \QUI\Events\Manager
     */
    public $Events;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->_dir    = OPT_DIR; // CMS_DIR .'packages/';
        $this->_vardir = VAR_DIR .'composer/';

        $this->_composer_json = $this->_vardir .'composer.json';

        $this->Events = new \QUI\Events\Manager();
    }

    /**
     * Return the Composer Application
     * @return \Composer\Console\Application
     */
    protected function _getApplication()
    {
        if ( $this->_Application ) {
            return $this->_Application;
        }

        // Create the application and run it with the commands
        $this->_Application = new Application();
        $this->_Application->setAutoExit( false );

        \QUI\Utils\System\File::mkdir( $this->_vardir );

        putenv( "COMPOSER_HOME=". $this->_vardir );

        return $this->_Application;
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

        $template = str_replace( '{$stability}', $this->_stability, $template );
        $template = str_replace( '{$PACKAGE_DIR}', OPT_DIR, $template );
        $template = str_replace( '{$VAR_COMPOSER_DIR}', $this->_vardir, $template );
        $template = str_replace( '{$LIB_DIR}', LIB_DIR, $template );

        $template = str_replace(
            '{$repositories}',
            json_encode( $repositories ),
            $template
        );

        // standard require
        $list    = $this->_getList();
        $require = $this->_require;

        $quiqqerVersion = '1.*';

        if ( \QUI::conf( 'globals', 'quiqqer_version' ) ) {
            $quiqqerVersion = \QUI::conf( 'globals', 'quiqqer_version' );
        }

        $require["php"] = ">=5.3.2";
        $require["quiqqer/quiqqer"] = $quiqqerVersion;
        $require["tedivm/stash"] = "0.11.*";
        $require["symfony/http-foundation"] = "*";

        foreach ( $list as $entry )
        {
            $version = $entry['version'];

            // so, we get newer versions
            if ( !preg_match( "/[\<\>\=\*]/", $version ) &&
                  preg_match( "/[0-9]/", $version ) )
            {
                $version = '>='. $version;
            }

            $require[ $entry['name'] ] = $version;
        }

        // composer and component installer should not be overwritten
        $require["composer/composer"] = "1.0.*@dev";
        $require["robloach/component-installer"] = "*";

        $template = str_replace(
            '{$REQUIRE}',
            json_encode( $require ),
            $template
        );

        \QUI\System\Log::addDebug( $template );

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

        try
        {
            $this->_list = \QUI\Cache\Manager::get( self::CACHE_NAME_TYPES );

            return $this->_list;

        } catch ( \QUI\Exception $Exception )
        {

        }

        $installed_file = $this->_dir .'composer/installed.json';

        if ( !file_exists( $installed_file ) ) {
            return array();
        }

        $data = file_get_contents( $installed_file );
        $list = json_decode( $data, true );

        $result = array();

        if ( is_array( $list ) )
        {
            foreach ( $list  as $entry )
            {
                if ( !isset( $entry['type'] ) )
                {
                    $result[] = $entry;
                    continue;
                }

                if ( $entry['type'] != 'quiqqer-library' )
                {
                    $result[] = $entry;
                    continue;
                }

                $path = OPT_DIR . $entry['name'] .'/';

                if ( file_exists( $path .'settings.xml' ) ) {
                    $entry['_settings'] = 1;
                }

                if ( file_exists( $path .'permissions.xml' ) ) {
                    $entry['_permissions'] = 1;
                }

                if ( file_exists( $path .'database.xml' ) ) {
                    $entry['_database'] = 1;
                }

                $result[] = $entry;
            }

            $this->_list = $result;
        }

        \QUI\Cache\Manager::set( self::CACHE_NAME_TYPES , $this->_list );

        return $this->_list;
    }

    /**
     * Refreshed the installed package list
     * If some packages are uploaded, sometimes the package versions and data are not correct
     *
     * this method correct it
     */
    protected function _refreshInstalledList()
    {
        $installed_file = $this->_dir .'composer/installed.json';

        if ( !file_exists( $installed_file ) ) {
            return;
        }


        $data = file_get_contents( $installed_file );
        $list = json_decode( $data, true );

        foreach ( $list as $key => $entry )
        {
            $cf = $this->_dir . $entry['name'] .'/composer.json';

            if ( !file_exists( $cf ) ) {
                continue;
            }

            $data = json_decode( file_get_contents( $cf ), true );

            if ( !is_array( $data ) ) {
                continue;
            }

            if ( !isset( $data['version'] ) ) {
                continue;
            }

            /*
            $list[ $key ]['version'] = $data['version'];

            // is that right?
            $list[ $key ]["version_normalized"] = str_replace(
                array('x', '*'),
                9999999,
                $data['version']
            );
            */
        }

        $this->_list = array();

        if ( is_array( $list ) ) {
            $this->_list = $list;
        }
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
        $result = $list;

        if ( isset( $params['type'] ) )
        {
            $result = array();

            foreach ( $list as $package )
            {
                if ( !isset( $package['type'] ) ) {
                    continue;
                }

                if ( !empty( $params['type'] ) &&
                     $params['type'] != $package['type'] )
                {
                    continue;
                }

                $result[] = $package;
            }
        }

        if ( isset( $params['limit'] ) && isset( $params['page'] ) )
        {
            $limit = (int)$params['limit'];
            $page  = (int)$params['page'];

            return \QUI\Utils\Grid::getResult( $result, $page, $limit );
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

        $result = $this->_execComposer('update', array(
            'packages' => array($package)
        ));

        \QUI\System\Log::writeRecursive( $result );
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
            $package = \QUI\Utils\Security\Orthos::clear( $package );

            $exec_result = $this->_execComposer('show', array(
               'tokens' => $package
            ));

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

                    $params[ 'require' ] = array_slice( $_temp, $key + 1 );
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
        $str    = \QUI\Utils\Security\Orthos::clearShell( $str );
        $list   = $this->_getList();

        $list = $this->_execComposer('search', array(
            'tokens' => array( $str )
        ));

        foreach ( $list as $entry )
        {
            $expl = explode( ' ', $entry, 2 );

            if ( isset( $expl[0] ) && isset( $expl[1] ) ) {
                $result[ $expl[0] ] = $expl[1];
            }
        }

        return $result;
    }

    /**
     * Execute a setup for a package
     *
     * @param String $package
     */
    public function setup($package)
    {
        $dir = OPT_DIR . $package .'/';

        \QUI\Update::importDatabase( $dir .'database.xml' );
        \QUI\Update::importTemplateEngines( $dir .'engines.xml' );
        \QUI\Update::importEditors( $dir .'wysiwyg.xml' );
        \QUI\Update::importMenu( $dir .'menu.xml' );
        \QUI\Update::importPermissions( $dir .'permissions.xml', $package );
        \QUI\Update::importEvents( $dir .'events.xml' );
        \QUI\Update::importMenu( $dir .'menu.xml' );

        // settings
        if ( !file_exists( $dir .'settings.xml' ) ) {
            return;
        }

        $defaults = \QUI\Utils\XML::getConfigParamsFromXml( $dir .'settings.xml' );
        $Config   = \QUI\Utils\XML::getConfigFromXml( $dir .'settings.xml' );

        $Config->save();
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
            return \QUI::getConfig( 'etc/source.list.ini' )->toArray();

        } catch ( \QUI\Exception $Exception )
        {

        }

        return array();
    }

    /**
     * Activate or Deactivate a server
     *
     * @param String $server - Server, IP, Host
     * @param Bool $status - 1 = active, 0 = disabled
     */
    public function setServerStatus($server, $status)
    {
        $Config  = \QUI::getConfig( 'etc/source.list.ini' );
        $status = (bool)$status ? 1 : 0;

        $Config->setValue( $server, 'active', $status );
        $Config->save();

        $this->_createComposerJSON();
    }

    /**
     * Add a server to the update-server list
     *
     * @param String $server - Server, IP, Host
     * @param Array $params - Server Parameter
     */
    public function addServer($server, $params=array())
    {
        if ( empty( $server ) ) {
            return;
        }

        if ( !is_array( $params ) ) {
            return;
        }


        $Config = \QUI::getConfig( 'etc/source.list.ini' );
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
        $Config = \QUI::getConfig( 'etc/source.list.ini' );

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
     * @throws \\QUI\Exception
     */
    public function checkUpdates()
    {
        $this->_createComposerJSON();

        $result = $this->_execComposer( 'update', array(
            '--dry-run' => true
        ));

        foreach ( $result as $line )
        {
            if ( strpos( $line, '-' ) === false ||
                 strpos( $line, '/' ) === false ||
                 strpos( $line, '(' ) === false )
            {
                continue;
            }

            if ( strpos($line, 'Installing') !== false )
            {
                preg_match( '#Installing ([^ ]*) #i', $line, $package );

            } else
            {
                preg_match( '#Updating ([^ ]*) #i', $line, $package );
            }

            preg_match_all( '#\(([^\)]*)\)#', $line, $versions );

            if ( isset( $package[1] ) ) {
                $package = $package[1];
            }

            $from = '';
            $to   = '';

            if ( isset( $versions[ 1 ] ) )
            {
                if ( isset( $versions[ 1 ][ 0 ] ) )
                {
                    $from = $versions[ 1 ][ 0 ];
                    $to   = $versions[ 1 ][ 0 ]; // if to is not set
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

    /**
     * Update a package or the entire system
     *
     * @param String|false $package - optional, package name, if false, it updates the complete system
     *
     * @throws \QUI\Exception
     *
     * @todo if exception uncommited changes -> own error message
     * @todo if exception uncommited changes -> interactive mode
     */
    public function update($package=false)
    {
        if ( $package )
        {
            $output = $this->_execComposer('update', array(
                'packages' => array($package)
            ));

        } else
        {
            $output = $this->_execComposer('update');
        }

        // exception?
        foreach ( $output as $key => $msg )
        {
            // if not installed
            if ( strpos( $msg, $package ) !== false &&
                 strpos( $msg, 'not installed' ) !== false )
            {
                $this->install( $package );
            }

            if ( strpos( $msg, 'Exception' ) )
            {
                throw new \QUI\Exception(
                    $output[ $key + 1 ]
                );
            }
        }

        \QUI\System\Log::addInfo( implode("\n", $output) );
    }

    /**
     * Update a package or the entire system from a package archive
     *
     * @param String $packagepath - path to the ZIP archive
     * @throws \\QUI\Exception
     */
    public function updatePackage($packagepath)
    {
        if ( !file_exists( $packagepath ) )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.packages.update.archive.not.found'
                )
            );
        }

        // extract the archive
        $folder = \QUI::getTemp()->createFolder();

        \QUI\Archiver\Zip::unzip( $packagepath, $folder );

        // read composer file
        $composer     = $folder .'composer.json';
        $repositories = VAR_DIR .'repository/bin/';

        if ( !file_exists( $composer ) )
        {
            throw new \QUI\Exception(
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

        \QUI\Utils\System\File::mkdir( $package_dir );
        \QUI\Utils\System\File::move( $packagepath, $update_file );


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
        $result = $this->_execComposer('', array(
            '--working-dir' => $repositories
        ));

        if ( !count( $result ) )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.packages.exec.not.found.composer'
                )
            );
        }

        $result = $this->_execComposer('update', array(
            '--dry-run' => true
        ));

        $last = end( $result );

        if ( $last == 'Nothing to install or update' )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.packages.update.version.not.found'
                )
            );
        }

        $result = $this->_execComposer('update');
    }

    /**
     * Execute a composer command
     *
     * @param String $command
     * @param Array $params
     */
    protected function _execComposer($command, $params=array())
    {
        \QUI\System\Log::addDebug( $command );
        \QUI\System\Log::addDebug( print_r($params, true) );

        // composer output some warnings that composer/cache is not empty
        try
        {
            \QUI::getTemp()->moveToTemp( $this->_vardir .'cache' );

        } catch ( \QUI\Exception $Exception )
        {
            \QUI\System\Log::addInfo( $Exception->getMessage() );
        }

        if ( !isset( $params['--working-dir'] ) ) {
            $params['--working-dir'] = $this->_vardir;
        }

        $params = array_merge(array(
            'command' => $command
        ), $params);

        $Input  = new ArrayInput( $params );
        $Output = new \QUI\Package\Output();

        // output events
        $PackageManager = $this;

        $Output->Events->addEvent('onOutput', function($message) use ($PackageManager) {
            $PackageManager->Events->fireEvent( 'output', array( $message ) );
        });

        // run application
        $this->_getApplication()->run( $Input, $Output );

        \QUI\Cache\Manager::clear( self::CACHE_NAME_TYPES );

        return $Output->getMessages();


//         $exec_var = str_replace( CMS_DIR, '', $this->_vardir );

//         $this->_composer_exec  = 'cd '. CMS_DIR .';';
//         $this->_composer_exec .= ' php '. $exec_var .'composer.phar';
    }
}
