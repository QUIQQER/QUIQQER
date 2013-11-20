<?php

/**
 * This file contains the ConsoleLinkChecker
 */

/**
 * LinkChecker
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.system.console.tool
 *
 * @copyright  2008 PCSG
 * @version    0.1 $Revision: 3425 $
 * @since      Class available since Release PTools 0.2
 *
 * @todo Complete Log im Anhang
 * @todo aktualisieren
 */

class ConsoleLinkChecker extends System_Console_Tool
{
    /**
     * errors from linkchecker
     * @var array
     */
	protected $_errors     = array();

	/**
	 * empty links
	 * @var array
	 */
	protected $_emptylinks = array();

	/**
	 * counter links
	 * @var Integer
	 */
	protected $_clinks = 0;

	/**
	 * counter sites
	 * @var Integer
	 */
	protected $_csites = 0;

	/**
	 * link cache, if a link was used before
	 * @var array
	 */
	protected $_linkCache = array();

	/**
	 * debug message
	 * @var String
	 */
	protected $_debugmsg   = '';

	/**
     * constructor
     * @param array $params
     */
	public function __construct($params)
	{
		parent::__construct($params);

		$help  = " Aufruf\n";
		$help .= " admin/console.php --username=[USERNAME] --password=[PASSWORD] --tool=ConsoleLinkChecker --project=[PROJECT] --host=[http://host.com] --lang=[LANGUAGE] [--PARAMS]\n";
		$help .= "\n";

		$help .= " Erforderliche Argumente\n";
		$help .= " --project	Name des Projektes\n";
		$help .= " --lang		Sprache des Projektes\n";
		$help .= " --host		Host des Auftrittes\n";

		$help .= "\n";
		$help .= " Optionale Argumente\n";
		$help .= " --help		Dieser Hilfetext\n";
		$help .= " --mail		Mailadressen an welche das Ergebnis gesendet werden soll (kommaseperiert können mehere Mailadressen angegeben werden)\n";
		$help .= " --attachlog	Attach Complete Log. Fügt eine komplette Log mit einer Debug Ausgabe an den Anhang der Mail\n";
		$help .= " --resume	Anzahl an Seiten die abgearbeitet werden soll, wenn nicht gesetzt werden alle seiten eingelesen\n";
		$help .= "\n";

		$this->addHelp($help);
	}

	/**
	 * Starts the LinkCheckerhen/Downloads/
	 */
	public function start()
	{
		if (!isset($this->_params['--project']) ||
			!isset($this->_params['--lang']) ||
			!isset($this->_params['--host']))
		{
			return $this->help();
		}

		if (isset($this->_params['--debug'])) {
			$this->setAttribute('debug', true);
		}

		if (isset($this->_params['--resume'])) {
			$this->setAttribute('resume', true);

		} else {

		  $datafile = VAR_DIR .'log/linkchecker_resume.data';
		  if (file_exists($datafile)) {
		      unlink($datafile);
		  }
		}
		if (isset($this->_params['--attachlog'])) {
			$this->setAttribute('attachlog', true);
		}

		$url = $this->_params['--host'];
		$url = trim($url, '/').'/';

		try
		{
			$Project = new Projects_Project(
				$this->_params['--project'],
				$this->_params['--lang']
			);

			$this->_log("Folgende URL wird geprüft: {$url}\n");

			$this->_log("... Seiten werden zusammen getragen\n");

			if ($this->getAttribute('resume') &&  $this->_checkResumeData())
			{
			  $sitesArray = $this->_getResumeData();
			  $this->_log("... Resume Aktiv: Alte Daten wurden geladen\n");

			} else {

			  $sites = $Project->getSites(array(
				  'where' => array(
					  'active' => 1
				  )
			  ));
			  $sitesArray = array();
			  // Seitendtaen in ein Array schreiben
			  foreach ($sites as $key => $Site) {
				  /* @var $Site Projects_Site */
				  $sitesArray[$key]['url'] = $Site->getUrlRewrited();
				  $sitesArray[$key]['id']  = $Site->getId();
			  }



			}

			if ($this->getAttribute('resume') )
			{
			  $this->_log("... Resume ist Aktiv es müssen noch ".count($sitesArray)." Seite(n) geprüft\n");
			  $this->_log("... Es werden in diesem durchlauf ".$this->_params['--resume']." Seite(n) geprüft\n");
			}

			$this->_log("... Prüfung der Links wird gestartet, es werden insgesammt ".count($sitesArray)." Seite(n) geprüft\n");

			foreach ($sitesArray as $key => $site)
			{
				$siteurl = $site['url'];

				$this->_checkUrl($url.$siteurl,  $site['id']);

				$this->_csites++;

				// resume funktion
				if ($this->getAttribute('resume'))
				{
				  unset($sitesArray[$key]);

				  if ($this->_params['--resume'] < $this->_csites) {
				    break;
				  }
				}

			}

			// wenn Resume dann daten speichern
			if ($this->getAttribute('resume') )
			{
			  $this->_setResumeData($sitesArray);
			  $this->_log("\n... Prüfung der Links wurde abgeschlossen es wurden ".$this->_params['--resume']." geprüft, es müssen noch ".count($sitesArray)." Seite(n) geprüft werden\n");
			}



			$this->_log("\nZusammenfassung wird erstellt ...");

			$Smarty = QUI_Template::getEngine(true);
			$Smarty->assign(array(
				'Project'    => $Project,
				'errors'     => $this->_errors,
				'clinks'     => $this->_clinks,
				'csites'     => $this->_csites,
				'emptylinks' => $this->_emptylinks,
				'cerrors'    => count($this->_errors)
			));

			$result = $Smarty->fetch(LIB_DIR .'console/tpl/ConsoleLinkChecker.html');

			if (!isset($this->_params['--mail'])) {
				return $this->_log($result);
			}

			// Attachment

			$logfile = VAR_DIR .'log/linkchecker_'. date('d_m_Y__H_i_s') .'.log';

			$this->_log("\nMails werden versendet ...");

			$_mails = explode(',', $this->_params['--mail']);

			foreach ($_mails as $_mail)
			{
				$Mail = new QUI_Mail();

				$params = array(
					'MailTo'  => $_mail,
					'Subject' => 'LinkChecker',
					'IsHTML'  => false,
					'Body'	  => $result
				);

				if ($this->getAttribute('attachlog')) {
					$params['files'] = $this->getAttribute('attachlog');
				}

				$Mail->send($params);

				$this->_log("\n[OK] {$_mail}");
			}

			$this->_log("\n");

		} catch (\QUI\Exception $e)
		{
			$this->_log($e->getMessage()."\n");
		}
	}

	/**
	 * Prüft eine URL und sammelt die Fehler
	 *
	 * @param String $url
	 * @param Integer $siteId - id of the site
	 */
	protected function _checkUrl($url, $siteId=0)
	{
		$debug = $this->getAttribute('debug');
		$this->_log("\n{$this->_csites} - Prüfe URL [SiteID ". $siteId ."]: {$url}");

		/**
		 * Alle Links prüfen
		 */
		$links = Utils_Request_Linkchecker::getLinksFromUrl($url);

		for ($i = 0, $len = count($links); $i < $len; $i++)
		{
			$_url = $links[$i]['url'];

			// Prüfen ob Links leer ist <a href="url"></a>
			if ($links[$i]['empty'])
			{
				$this->_emptylinks[] = array(
					'link'	=> $_url,
					'page'  => $url
				);
			};

			// Statuscode prüfen
			if (!isset($this->_linkCache[$_url])) {
			  $this->_linkCache[$_url] = Utils_Request_Linkchecker::checkUrl($_url);
			}

			$http_code = $this->_linkCache[$_url];


			if ($debug)
			{
				$this->_log("\n.. {$http_code} - {$_url}");

			} else
			{
				$this->_debugmsg .= "\n.. {$http_code} - {$_url}";
				$this->_logToFile();
				echo '.';
			}


			if ($http_code >= 100 && $http_code < 200)
			// 1xx Informational
			{

			} elseif ($http_code >= 200 && $http_code < 300)
			// 2xx Success
			{

			} elseif ($http_code >= 300 && $http_code < 400)
			// 3xx Redirection
			{

			} elseif ($http_code >= 400 && $http_code < 500)
			// 4xx Client Error
			{
				$this->_errors[] = array(
					'code'  => $http_code,
					'error' => 'Client Error',
					'link'	=> $_url,
					'page'  => $url,
					'value' => $links[$i]['value']
				);
			} elseif ($http_code >= 500 && $http_code != 1000)
			// 5xx Server Error
			{
				$this->_errors[] = array(
					'code'  => $http_code,
					'error' => 'Server Error',
					'link'	=> $_url,
					'page'  => $url,
					'value' => $links[$i]['value']
				);
			} elseif ($http_code = 1000)
			// 1000 Fehler (Unbekanter Fehler)
			{
				$this->_errors[] = array(
					'code'  => $http_code,
					'error' => 'unbekannter Fehler',
					'link'	=> $_url,
					'page'  => $url,
					'value' => $links[$i]['value']
				);
			} else
			// Leerer href
			{
				$this->_errors[] = array(
					'code'  => 'EMPTY HREF NO LINK FOUND',
					'error' => 'Error',
					'link'	=> $_url,
					'page'  => $url,
					'value' => $links[$i]['value']
				);
			}

			// Geprüfte Links erhöhen
			$this->_clinks++;
		}
	}

	/**
	 * Log Message
	 *
	 * @param unknown_type $msg
	 */
	protected function _log($msg)
	{
		$this->_debugmsg .= $msg;
		$this->_logToFile();
		echo $msg;

		return true;
	}

	/**
	 * Log Message to file
	 *
	 */
	protected function _logToFile()
	{
		$logfile = VAR_DIR .'log/linkchecker_'. date('d_m_Y') .'.log';
		file_put_contents($logfile, $this->_debugmsg, FILE_APPEND);

		$this->_debugmsg = '';

		return true;
	}

	/**
	 * läd die Resume Daten
	 *
	 * @return Array $data
	 */
	protected function _getResumeData()
	{
		$datafile = VAR_DIR .'log/linkchecker_resume.data';
		if (!file_exists($datafile)) {
		  return false;
		}

		$data = json_decode(file_get_contents($datafile), true);

		return $data;

	}

	/**
	 * setzt die Resume Daten
	 *
	 * @param  Array $data
	 */
	protected function _setResumeData($data)
	{
		$datafile = VAR_DIR .'log/linkchecker_resume.data';

		file_put_contents($datafile,json_encode($data));

		return true;

	}

	/**
	 * prüft ob eine Resume Data Datei vorhanden ist
	 *
	 * @return bool
	 */
	protected function _checkResumeData()
	{
		$datafile = VAR_DIR .'log/linkchecker_resume.data';

		if (file_exists($datafile)) {
		  return true;
		}

		return false;
	}


}

?>