<?php

/**
 * This file contains the ConsolePatch
 */

/**
 * Patch System
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.system.console.tool
 *
 * @copyright  2008 PCSG
 * @version    0.12 $Revision: 3514 $
 * @since      Class available since Release P.MS 0.9.8
 *
 * @todo noch einmal anschauen, maybe überdenken
 */

class ConsolePatch extends System_Console_Tool
{
    /**
     * histroy string for messages
     * @var String
     */
	private $_history = '';

	/**
	 * constructor
	 * @param array $params - patch params
	 */
	public function __construct($params)
	{
		parent::__construct($params);

		$help  = " Beschreibung:\n";
		$help .= " Führt einzelne Patches oder alle Patches aus\n";
		$help .= "\n";
		$help .= " Aufruf:\n";
		$help .= " admin/console.php --username=[USERNAME] --password=[PASSWORD] --tool=ConsolePatch [params]\n";
		$help .= "\n";
		$help .= " Optionale Parameter:\n";
		$help .= " --help			Dieser Hilfetext\n\n";

		$help .= " --type=[type]\n";
		$help .= "	all 		Alle Patches ausführen\n";
		$help .= "	name 		Name des Patches welcher ausgeführt werden soll\n\n";

		$help .= " --list		Listet alle verfügbaren Patches auf\n";
		$help .= "\n";

		$this->addHelp($help);
	}

	/**
	 * Führt das Tool aus
	 */
	public function start()
	{
		$params = $this->_params;

		// Hilfe ausgeben falls keine Parameter übergeben wurden oder die Hilfe gewünscht ist
		if ((!isset($params['--list']) && !isset($params['--type'])) ||
			isset($params['--help']))
		{
			$this->help();
			return;
		}

		$patches = $this->_getPatches();

		// Listet alle Patches auf
		if (isset($params['--list']))
		{
			foreach ($patches as $patch => $func) {
				echo '- '. $patch ."\n";
			}

			return;
		}


		// Patches durchführen
		$this->write('');
		$this->write('');
		$this->write('######################################');
		$this->write('         PATCH '.date('d_m_Y H:i:s'));
		$this->write('######################################');

		$ERRORS  = 0;
		$PATCHES = 0;

		switch ($params['--type'])
		{
			// Alle Patches durchführen
			case "all":
				foreach ($patches as $patch => $p_params)
				{
					require_once $p_params['file'];

					if (function_exists($p_params['function']))
					{
						$this->write('######################################');
						eval('$r = '. $p_params['function'] .'($this);');

						if ($r == false) {
							$ERRORS++;
						}

						$PATCHES++;
					}
				}
			break;

			// Einzelne Patches durchführen
			default:
				if (!isset($patches[$params['--type']]))
				{ // Patch existiert nicht
					$this->help();
					return;
				}

				$p_params = $patches[$params['--type']];
				require_once $p_params['file'];

				if (function_exists($p_params['function']))
				{
					$this->write('######################################');
					eval('$r = '. $p_params['function'] .'($this);');

					if ($r == false) {
						$ERRORS++;
					}

					$PATCHES++;
				}
			break;
		}

		$history_file = VAR_DIR .'log/patch_'. date('d_m_Y__H_i_s') .'.history';

		$this->write('');
		$this->write('');
		$this->write('######################################');
		$this->write($ERRORS .' Patches hatten Fehler');
		$this->write($PATCHES .' Patches wurden ausgeführt');
		$this->write('');

		//file_put_contents($history_file, $this->_history);
		$this->write('Komplette History: '. $history_file);
		$this->write('');
	}

	/**
	 * Liste der Patches
	 *
	 * @return Array
	 */
	protected function _getPatches()
	{
		$list = array();

		// System Patches
		$p_dir   = LIB_DIR .'patch/';
		$p_files = Utils_System_File::readDir($p_dir);

		foreach ($p_files as $patch)
		{
			if (is_dir($p_dir . $patch)) {
				continue;
			}

			$params = $this->_getNameParams($patch);
/*
			$p_name = str_replace('.','_', str_replace(array('.php', 'patch.'), '', $patch));
			$p_func = str_replace('.','_', str_replace('.php', '', $patch));
*/
			$list[$params['name']] = array(
				'function' => $params['func'],
				'file'     => $p_dir . $patch
			);
		}

		// Plugin Patches
		$p_files = Utils_System_File::readDir(OPT_DIR);

		foreach ($p_files as $plugin)
		{
			if (!is_dir(OPT_DIR . $plugin)) {
				continue;
			}

			if (!is_dir(OPT_DIR . $plugin .'/patch/')) {
				continue;
			}

			// Patchdir lesen
			$patches = Utils_System_File::readDir(OPT_DIR . $plugin .'/patch/');

			foreach ($patches as $file)
			{
				$params  = $this->_getNameParams($file);

				$list[$params['name']] = array(
					'function' => $params['func'],
					'file'     => OPT_DIR . $plugin .'/patch/' . $file
				);
			}
		}

		return $list;
	}

	/**
	 * Patch Funktions Teile
	 *
	 * @param String $str
	 * @return Array
	 */
	protected function _getNameParams($str)
	{
		$p_name = str_replace('.','_', str_replace(array('.php', 'patch.'), '', $str));
		$p_func = str_replace('.','_', str_replace('.php', '', $str));

		return array(
			'name' => $p_name,
			'func' => $p_func
		);
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $txt
	 */
	public function write($txt)
	{
		echo $txt ."\n";
		$this->_history .= $txt ."\n";
	}
}

?>