<?php

/**
 * This file contains the ConsoleImageCacheOptimize
 */

/**
 * Cache Bilder Optimieren
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.system.console.tool
 *
 * @copyright 2011 PCSG
 * @version   0.2
 *
 * @todo aktualisieren
 */

class ConsoleImageCacheOptimize extends System_Console_Tool
{
    /**
     * constructor
     * @param array $params
     */
	public function __construct($params)
	{
		parent::__construct($params);

		$help = " Beschreibung:\n";
		$help .= " Übersetzungstool\n";
		$help .= "\n";
		$help .= " Aufruf:\n";
		$help .= " admin/console.php --username=[USERNAME] --password=[PASSWORD] --tool=ConsoleImageCacheOptimize [params]\n";
		$help .= "\n";
		$help .= " Parameter:\n";
		$help .= " --project=[PROJECT]		Projektnamen\n\n";
		$help .= " --lang=[LANG]		    Sprache\n\n";
		$help .= " --image=[STRING]			PNG, JPG, ALL - Bildart welche kombrimiert werden soll\n\n";

		$help .= " Optionale Parameter:\n";
		$help .= " --help			Dieser Hilfetext\n\n";
		$help .= " --mtime			Nur Bilder die neuer sind als (--mtime) : in Tage \n\n";
		$help .= "\n";

		$this->addHelp($help);
	}

	/**
	 * Führt das Tool aus
	 */
	public function start()
	{
	    if (!isset($this->_params['--project'])) {
			throw new QException('Es wurde kein Projekt angegeben');
		}

	    if (!isset($this->_params['--lang'])) {
			throw new QException('Es wurde keine Projekt Sprache angegeben');
		}

		if (!isset($this->_params['--image'])) {
		    throw new QException('Bitte geben Sie an welche Bilder kombrimiert werden sollen');
		}

        $Project = QUI::getProject(
            $this->_params['--project'],
            $this->_params['--lang']
        );

        // JPGS
        if ($this->_params['--image'] == 'JPG' ||
            $this->_params['--image'] == 'ALL')
        {
            $this->_jpgs($Project);
        }

        // PNGS
	    if ($this->_params['--image'] == 'PNG' ||
	        $this->_params['--image'] == 'ALL')
	    {
            $this->_pngs($Project);
        }
	}

    /**
     * JPG Bilder optimieren
     *
     * @param Projects_Project $Project
     */
	protected function _jpgs(Projects_Project $Project)
	{
        $media_cachedir  = CMS_DIR .'media/cache/'. $Project->getAttribute('name') .'/';
        $system_cachedir = VAR_DIR .'cache/';
        $file_cache      = $system_cachedir .'optimize_images_jpgs_'. $Project->getAttribute('name') .'_'. $Project->getAttribute('lang');

        if (file_exists($file_cache)) {
            unlink($file_cache);
        }

        // Alle jpgs in eine Datei
        if (isset($this->_params['--mtime']))
        {
            exec('find "'. $media_cachedir .'" -iname \*.jp*g -type f -mtime -'. (int)$this->_params['--mtime'] .' > "'. $file_cache .'"');
        } else
        {
            exec('find "'. $media_cachedir .'" -iname \*.jp*g -type f > "'. $file_cache .'"');
        }

        // Anzahl Zeilen der Datei
        $lines = exec('wc -l "'. $file_cache .'"');
        $du    = exec('du -hs "'. $media_cachedir .'"');

        $du    = explode("\t", $du);
        $lines = explode(" ", $lines);
        $lines = (int)$lines[0];

        echo "\n".'Es werden '. $lines .' Bilder optimiert ('. $du[0] .')'."\n";

        for ($i = 1; $i < $lines; $i++)
        {
            // Bestimmte Zeile ausgeben
            //head -n2 find_media_jpgs|tail -n1
            $img = exec("sed -n '{$i}p' '{$file_cache}'");

            // Bild optimieren
            exec('jpegoptim -m70 -o --strip-all "'. $img. "\"");

            if (($i % 1000) == 0) {
                $this->message($i ."\n");
            }
        }

        $du2 = exec('du -hs '. $media_cachedir);
        $du2 = explode("\t", $du2);

        echo "\n\n##############################\n".
        	'Kombrimiert auf von '. $du[0] .' auf '. $du2[0] ."\n\n";

	    if (file_exists($file_cache)) {
            unlink($file_cache);
        }
    }

    /**
     * PNG Bilder Komprimieren
     *
     * @param Projects_Project $Project
     */
    protected function _pngs(Projects_Project $Project)
	{
	    $media_cachedir  = CMS_DIR .'media/cache/'. $Project->getAttribute('name') .'/';
        $system_cachedir = VAR_DIR .'cache/';
        $file_cache      = $system_cachedir .'optimize_images_pngs_'. $Project->getAttribute('name') .'_'. $Project->getAttribute('lang');

        if (file_exists($file_cache)) {
            unlink($file_cache);
        }

        // Alle pngs in eine Datei
        if (isset($this->_params['--mtime']))
        {
            exec('find "'. $media_cachedir .'" -iname \*.png -type f -mtime -'. (int)$this->_params['--mtime'] .'  > "'. $file_cache .'"');
        } else
        {
            exec('find "'. $media_cachedir .'" -iname \*.png -type f > "'. $file_cache .'"');
        }

        // Anzahl Zeilen der Datei
        $lines = exec('wc -l "'. $file_cache .'"');
        $du    = exec('du -hs "'. $media_cachedir .'"');

        $du    = explode("\t", $du);
        $lines = explode(" ", $lines);
        $lines = (int)$lines[0];

        echo "\n".'Es werden '. $lines .' Bilder optimiert ('. $du[0] .')'."\n";

        for ($i = 1; $i < $lines; $i++)
        {
            // Bestimmte Zeile ausgeben
            //head -n2 find_media_jpgs|tail -n1
            $img = exec("sed -n '{$i}p' '{$file_cache}'");

            // Bild optimieren
            exec('optipng "'. $img .'"');

            if (($i % 10) == 0) {
                $this->message($i ."\n");
            }
        }

        $du2 = exec('du -hs '. $media_cachedir);
        $du2 = explode("\t", $du2);

        echo "\n\n##############################\n".
        	'Kombrimiert auf von '. $du[0] .' auf '. $du2[0] ."\n\n";

	    if (file_exists($file_cache)) {
            unlink($file_cache);
        }
	}
}

?>