<?php

/**
 * PCSG Consolentools
 *
 * @author PCSG - Henning
 * @package com.pcsg.pms.console
 *
 * @example    php admin/console.php
 *
 * @copyright  2008 PCSG
 * @version    $Revision: 4006 $
 * @since      Class available since Release P.MS 0.6
 */

if (!isset($_SERVER['argv']))
{
	echo "Cannot use Consoletools";
	exit;
}

// Vars löschen die Probleme bereiten können
$_REQUEST = array();
$_POST    = array();
$_GET     = array();

if (isset($_SERVER['argv'][0])) {
	unset($_SERVER['argv'][0]);
}

// Parameter auslesen
$params = array();

foreach ($_SERVER['argv'] as $argv)
{
	if (strpos($argv,'=') !== false)
	{
		$var = explode('=', $argv);

		if (isset($var[0]) && isset($var[1])) {
			$params[ $var[0] ] = $var[1];
		}

	} else
	{
		$params[ $argv ] = true;
	}
}

require_once 'header.php';
require_once LIB_DIR .'Console.php';

// Console aufbauen
$Console = new System_Console($params);
$Console->start();

?>