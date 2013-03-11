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
	 *
	 * @param array $params - tool parameter
	 */
	public function __construct($params=array())
	{
		if ( !is_array( $params ) )
		{
			$this->help();
			exit;
		}

		if ( isset( $params['--help'] ) && !isset( $params['--tool'] ) )
		{
			$this->help();
			exit;
		}

		// kurze schreibeweise für username und passwort
		if ( isset( $params['-u'] ) && isset( $params['-p'] ) )
		{
            $params['--username'] = $params['-u'];
            $params['--password'] = $params['-p'];
		}

		if ( !isset( $params['--username'] ) )
		{
			$this->message("Please enter your username and password\n", 'red');

			$this->message("\nUsername:", 'green');
			$params['--username'] = trim(fgets(STDIN)); // reads one line from STDIN

			$this->message("Password:", 'green');
			$params['--password'] = trim(fgets(STDIN)); // reads one line from STDIN
		}

		if (!isset($params['--password']))
		{
			//$this->message("Benutzername oder Passwort falsch\n\n", 'red');
			//exit;

			$this->message("Password:", 'green');
			$params['--password'] = trim(fgets(STDIN)); // reads one line from STDIN
		}

		try
		{
    		$Users = QUI::getUsers();
    		$User  = $Users->login($params['--username'], $params['--password']); /* @var $User User */
		} catch(QException $e)
        {
			$this->message($e->getMessage()."\n\n", 'red');
			exit;
		}

		if (!$User->getId())
		{
			$this->message("Login inkorrekt\n\n", 'red');
			exit;
		}

		if (!$User->isSU())
		{
			$this->message("Missing rights to use the console\n\n", 'red');
			exit;
		}

		// Login
		$this->_argv = $params;
		$this->_read();

		if (isset($params['--listtools']))
		{
			$this->title();
			echo "Tools\n";

			$tools = $this->get(true);

			foreach ($tools as $tool => $obj) {
				echo " - ".$tool."\n";
			}

			echo "\n";
		}

		if (!isset($params['--tool']) && !isset($params['--listtools']))  {
            $this->readToolFromShell();
		}
	}

	/**
	 * List all tools in the shell for selection
	 */
	public function readToolFromShell()
	{
        $this->title();
        echo "Available Tools\n";

        $tools = $this->get(true);

        foreach ($tools as $tool => $obj) {
            echo " - ".$tool."\n";
        }

        echo "\n";
        $this->message("Please select a tool from the list\n", 'red');
        $this->message("Tool:", 'green');
        $tool = trim(fgets(STDIN)); // reads one line from STDIN

        if (isset($this->_tools[ $tool ]))
        {
            $this->_argv['--tool'] = $tool;
            $this->clear();
        }

        if ($tool = $this->get($this->_argv['--tool']))
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
		if (isset($this->_tools[ $tool ]) && is_object($this->_tools[ $tool ])) {
			return $this->_tools[ $tool ];
		}

		if ($tool==true) {
			return $this->_tools;
		}

		return false;
	}

	/**
	 * Start the console, if a tool is selected, execute the tool
	 */
	public function start()
	{
		if (!isset($this->_argv['--tool'])) {
			return;
		}

		if ($tool = $this->get($this->_argv['--tool'])) {
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
		$files = Utils_System_File::readDir($path, true);

		for ($i = 0, $len = count($files); $i < $len; $i++)
		{
			if (!file_exists($path.$files[$i])) {
				continue;
			}

			$this->_includeClasses($files[$i], $path);
		}

		/**
		 * Plugins console tools
		 */
		$plugins_dir = Utils_System_File::readDir(OPT_DIR);

		for ($i = 0, $len = count($plugins_dir); $i < $len; $i++)
		{
			if (!file_exists(OPT_DIR . $plugins_dir[$i] .'/Console.php')) {
				continue;
			}

			require_once(OPT_DIR . $plugins_dir[$i] .'/Console.php');

			$class = 'Console'. ucfirst($plugins_dir[$i]);

			if (class_exists($class))
			{
				$tool = new $class($this->_argv);

				$tool->setAttribute('parent', $this);
				$this->_tools[ $class ] = $tool;
			}
		}

		/**
		 * Projects console tools
		 */
		$projects_dir = Utils_System_File::readDir(USR_DIR .'lib/');

		for ($i = 0, $len = count($projects_dir); $i < $len; $i++)
		{
			$dir = USR_DIR .'lib/'. $projects_dir[$i] .'/console/';

			if (!is_dir($dir)) {
				continue;
			}

			$c_dir = Utils_System_File::readDir($dir, true);

			for ($c = 0, $clen = count($c_dir); $c < $clen; $c++)
			{
				if (file_exists($dir.$c_dir[$c])) {
					$this->_includeClasses($c_dir[$c], $dir);
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
		require_once($dir . $file);

		$class = str_replace('.php', '', $file);

		if (class_exists($class))
		{
			$tool = new $class($this->_argv);

			$tool->setAttribute('parent', $this);
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

		$this->message("\n");
		$this->message(" Aufruf\n");
		$this->message(" admin/console.php --username=[USERNAME] --password=[PASSWORD] --tool=[TOOLNAME] [--PARAMS]\n", 'red');

		$this->clearMsg();
		$this->message("\n");
		$this->message(" Required arguments\n");

		$this->message(" --username		Username\n", 'red');
		$this->message(" --password		Password to login\n", 'red');

		$this->clearMsg();
		$this->message("\n");
		$this->message(" Optional arguments\n");
		$this->message(" --help			This help text\n");
		$this->message(" --listtools		Lists the available console tools\n");
		$this->message(" 			Only with the correct login\n");
		echo $msg;
		exit;
	}

	/**
	 * QUIQQER Console title
	 * output the main quiqqer console info
	 */
	public function title()
	{
		$str  = "\n";
		$str .= " ###################################################\n";
		$str .= " #                                                 #\n";
		$str .= " #             QUIQQER Console Tools               #\n";
		$str .= " #     @author Henning Leutz und Moritz Scholz     #\n";
		$str .= " #                                                 #\n";
		$str .= " #          www.pcsg.de www.quiqqer.com            #\n";
		$str .= " ###################################################\n";
		$str .= "\n";

		$this->message($str, 'blue', 'white');
	}

	/**
	 * clear the console (all colors)
	 */
	public function clear()
	{
        array_map(
            create_function('$a', 'print chr($a);'),
            array(27, 91, 72, 27, 91, 50, 74)
        );
	}

	/**
	 * Output a message
	 *
	 * @param String $msg   - Message to output
	 * @param String $color - textcolor
	 * @param String $bg    - background color
	 */
	public function message($msg, $color=false, $bg=false)
	{
		if ($color) {
			$this->_current_color = $color;
		}

		if ($bg) {
			$this->_current_bg = $bg;
		}

		if (isset($this->_colors[$this->_current_color])) {
            echo "\033[" . $this->_colors[$this->_current_color] . "m";
        }

        if (isset($this->_bg[$this->_current_bg])) {
            echo "\033[" . $this->_bg[$this->_current_bg] . "m";
        }

        echo $msg;

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