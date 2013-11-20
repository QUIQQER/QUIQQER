<?php

/**
 * This file contains System_Console
 */

/**
 * The QUIQQER Console
 *
 * With the console you can start tools / crons in the shell
 *
 * @author www.pcsg.de (Henning Leutz)
 * @author www.pcsg.de (Moritz Scholz)
 *
 * @package com.pcsg.qui.system.console
 */

class System_Console
{
    /**
     * All available console tools
     * @var array
     */
	private $_tools = array();

	/**
	 * Console parameter
	 * @var array
	 */
	private $_argv;

	/**
	 * The current text color
	 * @var String
	 */
	protected $_current_color = false;

    /**
     * the current background color
     * @var String
     */
	protected $_current_bg = false;

	/**
	 * All available text colors
	 * @var array
	 */
	protected $_colors = array(
        'black'        => '0;30',
        'dark_gray'    => '1;30',
        'blue'         => '0;34',
        'light_blue'   => '1;34',
        'green'        => '0;32',
        'light_green'  => '1;32',
        'cyan'         => '0;36',
        'light_cyan'   => '1;36',
        'red'          => '0;31',
        'light_red'    => '1;31',
        'purple'       => '0;35',
        'light_purple' => '1;35',
        'brown'        => '0;33',
        'yellow'       => '1;33',
        'light_gray'   => '0;37',
        'white'        => '1;37',
        'black_u'      => '4;30',
        'red_u'        => '4;31',
        'green_u'      => '4;32',
        'yellow_u'     => '4;33',
        'blue_u'       => '4;34',
        'purple_u'     => '4;35',
        'cyan_u'       => '4;36',
        'white_u'      => '4;37'
    );

    /**
     * All available background colors
     * @var array
     */
    protected $_bg = array(
        'black'      => '40',
        'red'        => '41',
        'green'      => '42',
        'yellow'     => '43',
        'blue'       => '44',
        'magenta'    => '45',
        'cyan'       => '46',
        'light_gray' => '47'
    );

	/**
	 * constructor
	 */
	public function __construct()
	{
        $this->title();

	    if ( !isset( $_SERVER['argv'] ) )
        {
        	$this->writeLn( "Cannot use Consoletools" );
        	exit;
        }

        $params = $this->_read_argv();

		if ( isset( $params[ '--help' ] ) && !isset( $params[ '--tool' ] ) )
		{
			$this->help();
			exit;
		}

		if ( isset( $params[ '-u' ] ) && isset( $params[ '-p' ] ) )
		{
            $params[ '--username' ] = $params[ '-u' ];
            $params[ '--password' ] = $params[ '-p' ];
		}

		if ( !isset( $params[ '--username' ] ) )
		{
			$this->writeLn( "Please enter your username and password", 'red' );
			$this->writeLn( "Username:", 'green' );

			$params[ '--username' ] = $this->readInput();

			$this->writeLn( "Password:", 'green' );
			$params[ '--password' ] = $this->readInput();
		}

		if ( !isset( $params[ '--password' ] ) )
		{
			$this->writeLn( "Password:", 'green' );
			$params[ '--password' ] = $this->readInput();
		}

		try
		{
    		$User = \QUI::getUsers()->login(
    		    $params[ '--username' ],
    		    $params[ '--password' ]
	        );

		} catch ( \QUI\Exception $e )
        {
			$this->writeLn( $e->getMessage() ."\n\n", 'red' );
			exit;
		}

		if ( !$User->getId() )
		{
			$this->writeLn( "Login incorrect\n\n", 'red' );
			exit;
		}

		if ( !$User->isSU() )
		{
			$this->writeLn( "Missing rights to use the console\n\n", 'red' );
			exit;
		}

		// Login
		$this->_argv = $params;
		$this->_read();

		if ( isset( $params[ '--listtools' ] ) )
		{
			$this->title();
			$this->writeLn( "Tools\n" );

			$tools = $this->get( true );

			foreach ( $tools as $tool => $obj ) {
				$this->writeLn( " - ". $tool ."\n" );
			}

			$this->writeLn( "\n" );
		}

		if ( !isset( $params['--tool'] ) && !isset( $params['--listtools'] ) ) {
            $this->readToolFromShell();
		}
	}

	/**
	 * Read the argv params
	 *
	 * @return array
	 */
	protected function _read_argv()
	{
        // Vars löschen die Probleme bereiten können
        $_REQUEST = array();
        $_POST    = array();
        $_GET     = array();

        if ( isset( $_SERVER['argv'][0] ) ) {
        	unset( $_SERVER['argv'][0] );
        }

        $params = array();

        // Parameter auslesen
        foreach ( $_SERVER['argv'] as $argv )
        {
        	if ( strpos( $argv, '=' ) !== false)
        	{
        		$var = explode( '=', $argv );

        		if ( isset( $var[0] ) && isset( $var[1] ) ) {
        			$params[ $var[0] ] = $var[1];
        		}

        	} else
        	{
        		$params[ $argv ] = true;
        	}
        }

        return $params;
	}

	/**
	 * List all tools in the shell for selection
	 */
	public function readToolFromShell()
	{
        $this->writeLn( "\nAvailable Tools\n" );

        $tools = $this->get( true );

        foreach ( $tools as $tool => $obj ) {
            $this->writeLn( " - ". $tool ."\n" );
        }

        $this->writeLn( "\n" );
        $this->writeLn( "Please select a tool from the list\n", 'red' );
        $this->writeLn( "Tool:", 'green' );

        $tool = $this->readInput();

        if ( isset( $this->_tools[ $tool ] ) )
        {
            $this->_argv[ '--tool' ] = $tool;
            $this->clear();
        }

        if ( $tool = $this->get( $this->_argv[ '--tool' ] ) )
        {
            $tool->execute();
        } else
        {
            $this->readToolFromShell();
        }
    }

	/**
	 * Return a tool
	 *
	 * @param Bool|String $tool - Bool true = all Tools | String = specific tool
	 * @return Array|System_Console_Tool
	 */
	public function get($tool)
	{
		if ( isset( $this->_tools[ $tool ] ) &&
		     is_object( $this->_tools[ $tool ] ) )
        {
			return $this->_tools[ $tool ];
		}

		if ( $tool == true ) {
			return $this->_tools;
		}

		return false;
	}

	/**
	 * Start the console, if a tool is selected, execute the tool
	 */
	public function start()
	{
		if ( !isset( $this->_argv[ '--tool' ] ) ) {
			return;
		}

		if ( $tool = $this->get( $this->_argv[ '--tool' ] ) ) {
			$tool->execute();
		}
	}

	/**
	 * Read all tools and include it
	 */
	private function _read()
	{
		/**
		 * Standard Konsoletools
		 */
		$path  = LIB_DIR .'system/console/tools/';
		$files = \QUI\Utils\System\File::readDir( $path, true );

		for ( $i = 0, $len = count( $files ); $i < $len; $i++ )
		{
			if ( !file_exists( $path . $files[ $i ] ) ) {
				continue;
			}

			$this->_includeClasses( $files[ $i ], $path );
		}

		/**
		 * Plugins console tools
		 */
		$plugins_dir = \QUI\Utils\System\File::readDir( OPT_DIR );

		for ( $i = 0, $len = count( $plugins_dir ); $i < $len; $i++)
		{
			if ( !file_exists( OPT_DIR . $plugins_dir[ $i ] .'/Console.php' ) ) {
				continue;
			}

			require_once OPT_DIR . $plugins_dir[ $i ] .'/Console.php';

			$class = 'Console'. ucfirst( $plugins_dir[ $i ] );

			if ( class_exists( $class ) )
			{
				$tool = new $class( $this->_argv );

				$tool->setAttribute( 'parent', $this );
				$this->_tools[ $class ] = $tool;
			}
		}

		/**
		 * Projects console tools
		 */
		$projects_dir = \QUI\Utils\System\File::readDir( USR_DIR .'lib/' );

		for ( $i = 0, $len = count( $projects_dir ); $i < $len; $i++ )
		{
			$dir = USR_DIR .'lib/'. $projects_dir[ $i ] .'/console/';

			if ( !is_dir( $dir ) ) {
				continue;
			}

			$c_dir = \QUI\Utils\System\File::readDir( $dir, true );

			for ( $c = 0, $clen = count( $c_dir ); $c < $clen; $c++ )
			{
				if ( file_exists( $dir . $c_dir[ $c ] ) ) {
					$this->_includeClasses( $c_dir[ $c ], $dir );
				}
			}
		}
	}

	/**
	 * Include the tool class
	 *
	 * @param String $file
	 * @param String $dir
	 */
	protected function _includeClasses($file, $dir)
	{
		require_once $dir . $file;

		$class = str_replace( '.php', '', $file );

		if ( class_exists( $class ) )
		{
			$tool = new $class( $this->_argv );

			$tool->setAttribute( 'parent', $this );
			$this->_tools[ $class ] = $tool;
		}
	}

	/**
	 * Output the help
	 *
	 * @param String $msg - [optional] extra text
	 */
	public function help($msg='')
	{
		$this->title();
		$this->clearMsg();

		$this->writeLn();
		$this->writeLn( " Call" );
		$this->writeLn( " quiqqer.php --username=[USERNAME] --password=[PASSWORD] --tool=[TOOLNAME] [--PARAMS]", 'red' );

		$this->clearMsg();
		$this->writeLn( "" );
		$this->writeLn( " Required arguments" );

		$this->writeLn( " --username		Username", 'red' );
		$this->writeLn( " --password		Password to login", 'red' );

		$this->clearMsg();
		$this->writeLn( "" );
		$this->writeLn( " Optional arguments" );
		$this->writeLn( " --help			This help text" );
		$this->writeLn( " --listtools		Lists the available console tools" );
		$this->writeLn( " 			Only with the correct login" );

		$this->writeLn( $msg );
		exit;
	}

	/**
	 * QUIQQER Console title
	 * output the main quiqqer console info
	 */
	public function title()
	{
	    $str = '
         _______          _________ _______  _______  _______  _______
        (  ___  )|\     /|\__   __/(  ___  )(  ___  )(  ____ \(  ____ )
        | (   ) || )   ( |   ) (   | (   ) || (   ) || (    \/| (    )|
        | |   | || |   | |   | |   | |   | || |   | || (__    | (____)|
        | |   | || |   | |   | |   | |   | || |   | ||  __)   |     __)
        | | /\| || |   | |   | |   | | /\| || | /\| || (      | (\ (
        | (_\ \ || (___) |___) (___| (_\ \ || (_\ \ || (____/\| ) \ \__
        (____\/_)(_______)\_______/(____\/_)(____\/_)(_______/|/   \__/


            Welcome to QUIQQER.

        ';

		$this->message( $str, 'blue', 'white' );
		$this->clearMsg();
	}

	/**
	 * clear the console (all colors)
	 */
	public function clear()
	{
        array_map(
            create_function( '$a', 'print chr($a);' ),
            array( 27, 91, 72, 27, 91, 50, 74 )
        );
	}

	/**
	 * Read the input from the user -> STDIN
	 *
	 * @return String
	 */
	public function readInput()
	{
	    return trim( fgets( STDIN ) );
	}

    /**
     * Write a new line
     *
     * @param String $msg   - [optional] the printed message
	 * @param String $color - [optional] textcolor
	 * @param String $bg    - [optional] background color
     */
	public function writeLn($msg='', $color=false, $bg=false)
	{
        $this->message( "\n". $msg );
	}

	/**
	 * Output a message
	 *
	 * @param String $msg   - Message to output
	 * @param String $color - [optional] textcolor
	 * @param String $bg    - [optional] background color
	 */
	public function message($msg, $color=false, $bg=false)
	{
		if ( $color ) {
			$this->_current_color = $color;
		}

		if ( $bg ) {
			$this->_current_bg = $bg;
		}

		if ( isset( $this->_colors[ $this->_current_color ] ) ) {
            echo "\033[". $this->_colors[ $this->_current_color ] ."m";
        }

        if ( isset( $this->_bg[ $this->_current_bg ] ) ) {
            echo "\033[". $this->_bg[ $this->_current_bg ] ."m";
        }

        echo "\n".$msg;

        $this->resetMsg();
	}

	/**
	 * reset the message color
	 */
	public function resetMsg()
    {
        echo "\033[0m";
    }

    /**
     * reset the message and background color and reset the color settings
     */
    public function clearMsg()
    {
    	$this->_current_color = false;
		$this->_current_bg    = false;

		echo "\033[0m";
    }
}

?>