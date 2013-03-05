<?php

/**
 * This file contains System_Console_Tool
 */

/**
 * Parent class for a console tool
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.system.console
 */

class System_Console_Tool extends QDOM
{
    /**
     * Console parameter
     * @var array
     */
	protected $_params;

	/**
	 * Help String
	 * @var String
	 */
	protected $_help;

	/**
	 * Constructor
	 *
	 * @param Array $params - optional
	 */
	public function __construct($params=array())
	{
		if (is_array($params)) {
			$this->_params = $params;
		}
	}

	/**
	 * Exceute the cron
	 */
	public function execute()
	{
		if (isset($this->_params['--help'])) {
			$this->help();
		}

		if (method_exists($this, 'start')) {
			$this->start();
		}
	}

	/**
	 * Add a help to the cron
	 *
	 * @param String $help
	 */
	public function addHelp($help)
	{
		$this->_help = $help;
	}

	/**
	 * Outout the help
	 */
	protected function help()
	{
		$parent = $this->getAttribute('parent');

		if ($this->_help)
		{
			$parent->title();
			$this->message( $this->_help );

			exit;
		}

		$parent->help();
		return true;
	}

	/**
	 * Output a message
	 *
	 * @param String $msg	- Message
	 * @param String $color - optional, Text color
	 * @param String $bg	- optional, Background color
	 */
	public function message($msg, $color=false, $bg=false)
	{
	    if ($this->getAttribute('parent')) {
	        $this->getAttribute('parent')->message($msg, $color, $bg);
	    }
	}
}

?>