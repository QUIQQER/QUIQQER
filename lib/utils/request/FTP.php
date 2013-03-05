<?php

/**
 * This file contains Utils_Request_Linkchecker
 */

/**
 * FTP Klasse für das Update
 *
 * @author www.pcsg.de (Henning Leutz)
 * @author www.pcsg.de (Moritz Scholz)
 *
 * @package com.pcsg.qui.utils.request
 *
 * @todo code style!!! ... its so old :(
 * @todo maybe complete rewrite, code is a mess
 * @todo docu translation
 * @todo write it as php5 -.-
 */

class Utils_Request_FTP
{
    /**
     * ftp host
     * @var String
     */
	private $_host;

	/**
	 * ftp username
	 * @var String
	 */
	private $_username;

	/**
	 * ftp password
	 * @var String
	 */
	private $_password;

	/**
	 * ftp port
	 * @var String
	 */
	private $_port = 21;

    /**
     * internal socket object
     * @var fsockopen
     */
	private $_socket;

	/**
	 * internal data socket
	 * @var fsockopen
	 */
	private $_date_socket;

	/**
	 * transfer type
	 * @var String
	 */
	private $_type = "i";

	/**
	 * error number, if error exist
	 * @var Integer
	 */
	private $_error_no = '';

    /**
     * error message, if error exist
     * @var String
     */
	private $_error_string = '';

    /**
     * socket message
     * @var String
     */
	private $_message = '';

	/**
	 * write logs?
	 * @var Bool 0 / 1
	 */
	public $log = 1;

	/**
	 * Konstruktor
	 *
	 * @param array $settings
	 */
	public function __construct($settings)
	{
		if (is_array($settings))
		{
			if(isset($settings['host']))
			{
				$this->_host = $settings['host'];
			} else
			{
				throw new QException('No Host given');
			}

			if(isset($settings['password']))
			{
				$this->_password = $settings['password'];
			} else
			{
				throw new QException('No Password given');
			}

			if(isset($settings['username']))
			{
				$this->_username = $settings['username'];
			} else
			{
				throw new QException('No Username given');
			}

		} else
		{
			throw new QException('Settings must be an Array');
		}
	}

	/**
	 * Erstellt eine Veribndung zum Server
	 *
	 * @throws QException
	 */
	public function connect()
	{
		$e_str =& $this->_error_string;
		$e_no  =& $this->_error_no;

		$this->_socket = fsockopen($this->_host, $this->_port, $e_no, $e_str, 10);
		stream_set_blocking($this->_socket, 0);

		if($this->_socket)
		{
			if($this->isOk())
			{
				$this->_socketWrite("user ".$this->_username);

				if($this->isOk())
				{
					$this->_socketWrite("pass ".$this->_password);

					if($this->isOk())
					{
						if($this->_pasive())
						{
							$this->_writelog("<u><b>Verbindung hergestellt</b></u>");
							return true;
						}
					}
				}
			}
		}

		throw new QException('Fehler bei der Verbindung zu Server ' . $this->_host);
	}

	/**
	 * Statusprüfung
	 *
	 * @return Bool
	 * @throws QException
	 */
	public function isOk()
	{
		$this->_message = $this->_socketRead();

		if ($this->_message == "" || preg_match('/^5/', $this->_message) )
		{
			throw new QException($this->_message);
			return 0;
		}

		return 1;
	}

	/**
	 * Listet ein Verzeichnis auf
	 *
	 * @param String $path
	 * @return array
	 */
	public function dirList($path="")
	{
		$list=array();

		if ($this->_pasive())
		{
			if ($path == '')
			{
				$this->_socketWrite("LIST");
			} else
			{
				$this->_socketWrite("LIST $path");
			}

			if ($this->isOk())
			{
				while (true)
				{
					$line   = fgets($this->_date_socket);
					$list[] = $line;

					if ($line == '' ) {
						break;
					}
				}
			}
		}

		return $list;
	}

	/**
	 * Größe einer Datei bekommen
	 *
	 * @param String $file
	 * @return false|String
	 */
	public function getFileSize($file)
	{
		if (empty($file)) {
			return false;
		}

		if ($this->_pasive())
		{
			$this->_socketWrite("SIZE ".$file);
			return $this->isOk();
		}

		return false;
	}

	/**
	 * Größe einer Datei bekommen
	 *
	 * @param String $file
	 * @param Integer $size - ?
	 * @return boolen
	 */
	public function checkFileSize($file, $size)
	{
		if (empty($file)) {
			return false;
		}

		if ($this->_pasive())
		{
			$this->_socketWrite("SIZE ". $file);
			$filesize= explode(' ', $this->_socketRead());

			if ($size == $filesize[1]) {
				return true;
			}

			return false;
		}

		return false;
	}

	/**
	 * Läd eine Datei in einen bestimmten Datei hoch
	 *
	 * @param String $localFile
	 * @param String $remoteFile
	 */
	function upload($localFile, $remoteFile)
	{
		$tmp = $this->log;
		$this->log = 0;

		if ($this->_pasive())
		{
			$this->_socketWrite('STOR '.$remoteFile);

			if ($this->isOk())
			{
				$fp = fopen($localFile, "rb");
				$size = filesize($localFile);

				$this->log = $tmp;
				$this->_writelog('<div id="upload">0%</div>');

				$fs = 0;
				$pr = 0;

				while (!feof($fp))
				{
					$s = fread($fp, 4096);
					fwrite($this->_date_socket, $s);

					$fs = $fs+4096;
					$pt= (($fs/$size*100) < 100 ? round($fs/$size*100) : 100);

					if($pt > $pr ){
						$pr = $pt;
						$this->_writelog('<script>document.getElementById("upload").innerHTML = "'. $pr .'% hochgeladen"</script>', false);
					}
					set_time_limit(30);

				}

				sleep(2);
				fclose($this->_date_socket);
			}
		}

		$this->log = $tmp;
	}

	/**
	 * Nichts machen
	 * Hält die Verbindung aufrecht
	 */
	function noop()
	{
		$this->_socketWrite('NOOP');
	}

	/**
	 * Löscht ein File
	 *
	 * @param String $file
	 * @return Bool
	 */
	function delete($file)
	{
		$this->_socketWrite("DELE $file");
		return $this->isOk();
	}

	/**
	 * Läd eine Datei in einen bestimmten Ordner runter
	 *
	 * @param String $remoteFile - SPätere Datei auf dem Filesystem
	 * @param String $localFile - Datei welcher herruntergeladen werden soll
	 */
	function download($remoteFile, $localFile)
	{
		if ($this->_pasive())
		{
			$this->_socketWrite("RETR $remoteFile");

			if ($this->isOk())
			{
				$line = fgets($this->_date_socket, 1024);

				if ($line[0] == "-")
				    break;

				if (!$fhandle = fopen($localFile, 'a+'))
				{
					$this->_writelog('Cannot open file ('.$localFile.')');
					exit;
				}

				fwrite($fhandle, $line);

				$i = 0;
				while (($line = fgets($this->_date_socket, 1024)) <> ".\r\n")
				{
					if (!($line))
					{
						break;
					}

					fwrite($fhandle, $line);
					if( $i > 1000 )
					{
						$this->_writelog('.', false);
						$i = 0;
					}

					$i++;
				}
			}
		}
	}

	/**
	 * Liest das Sockets aus
	 *
	 * @param Integer $i
	 * @return String
	 */
	private function _socketRead($i=0)
	{
		$s = fgets($this->_socket);
		$this->_writelog($s, false);
		$s_tmp = $s;

		if ($s=='')
		{
			if ($i==30)
			{
				break;
			} else
			{
				sleep(1);
				return $this->_socketRead($i+1);
			}
		}

		while ($s != '')
		{
			$s = fgets($this->_socket);
			$this->_writelog($s);
		}

		return $s_tmp;
	}

	/**
	 * Schreibt in das Socket
	 *
	 * @param String $s
	 */
	private function _socketWrite($s)
	{
		if (substr($s, 0, 4) == 'pass')
		{
			$this->_writelog('< '.substr($s, 0, 4).' ****');
		} else
		{
			$this->_writelog("< $s");
		}

		fputs($this->_socket, "$s"."\r\n");
	}

	/**
	 * In Passive Mode umsteigen
	 *
	 * @return DataSocket|String
	 */
	private function _pasive()
	{
		$this->_socketWrite("PASV");

		if ($this->isOk())
		{
			$offset = strpos($this->_message,"(");

			$s = substr($this->_message, ++$offset, strlen($this->_message)-2);
			$this->_writelog('Message:: '.$s);

			if(DEBUG_MODE)
			$this->_writelog('TRIM:: '.trim($s));

			$parts = explode(",",trim($s));

			if(DEBUG_MODE)
			$this->_writelog( print_r($parts ,true) );

			$data_host = "$parts[0].$parts[1].$parts[2].$parts[3]";

			if(DEBUG_MODE)
			$this->_writelog( $data_host );

			$data_port = ((int)$parts[4] << 8) + (int) $parts[5];

			if(DEBUG_MODE)
			$this->_writelog( $data_port );

			$e_str =& $this->_error_string;
			$e_no =& $this->_error_no;

			$this->_date_socket = fsockopen($data_host, $data_port, $e_str, $e_no, 30);
			return $this->_date_socket;
		}

		return "";
	}

	/**
	 * Log ausgeben
	 *
	 * @param String $msg
	 * @param Bool <br> nach der Message ausgeben ja | Nein - default = true
	 */
	private function _writelog( $msg, $brake=true )
	{
		if($this->log == 1)
		{
			$iebug =  "      ";
			for ($i=0; $i < 30; $i++){
				$iebug .=  "      ";

			}

			echo $msg.$iebug;


			if($brake)
			{
				echo '<br>'.$iebug;
			}

			if(!isset($this->scroll))
			{
				$this->scroll = 0;
			}

			$this->scroll = $this->scroll+1;
			echo '<span id="ftp_'.$this->scroll.'"></span><script>  document.getElementById("ftp_'.$this->scroll.'").scrollIntoView(true);  </script>'.$iebug;

			flush();
		}
	}

	/**
	 * Schließt das Socket
	 *
	 */
	public function close()
	{
		if ($this->_socket) {
			fclose($this->_socket);
		}
	}

	/**
	 * Sets the transfer type
	 *
	 * @param String $type - bin | asci
	 * @param Bool $now 	- true = exec command now
	 */
	public function setType($type, $now=false)
	{
		if ($type == "bin") {

			$this->_type = "i";
		}

		if ($type == "asci") {

			$this->_type = "a";
		}

		if ($now) {
			$this->_socketWrite('TYPE '.$this->_type);
			$this->_socketRead();
		}
	}
}

?>