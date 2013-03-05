<?php

/**
 * This file contains the QUI_Systemcheck_Manager
 */

/**
 * Systemcheck Manager
 *
 * Check for all requirements that packages or quiqqer needed
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.systemcheck
 *
 * @todo create a gui, api, console api for QUI_Systemcheck_Manager
 * @todo create a Systemcheck class
 * @todo create a systemcheck exception
 */

class QUI_Systemcheck_Manager
{
    /**
     * Excute standard tests
     *
     * @return array
     */
    static function standard()
    {
        /**
         * 1  = OK
     	 * 0  = Nicht vorhanden, wird aber nicht benötigt
     	 * -1 = MUSS vorhanden sein
         */

        $need = array();

    	// JSON Prüfung
    	$JSON = new QDOM();
    	$JSON->setAttribute('title', 'JSON Prüfung');

    	if (function_exists('json_decode') && function_exists('json_encode'))
    	{
    		$JSON->setAttribute('error', 1);
    		$JSON->setAttribute('message', 'JSON ist verfügbar');
    	} else
    	{
    		$JSON->setAttribute('error', -1);
    		$JSON->setAttribute('message', 'Installieren / Aktivieren Sie bitte die PHP Erweiterung JSON. Weitere Information finden Sie <a href="http://de3.php.net/manual/de/json.setup.php">hier</a>');
    	}

    	$need[] = $JSON;

    	// ZLIB Prüfung
    	$ZLIB = new QDOM();
    	$ZLIB->setAttribute('title', 'ZLIB Prüfung');

    	if (function_exists('gzcompress'))
    	{
    		$ZLIB->setAttribute('error', 1);
    		$ZLIB->setAttribute('message', 'ZLIB ist verfügbar');
    	} else
    	{
    		$ZLIB->setAttribute('error', -1);
    		$ZLIB->setAttribute('message', 'Installieren / Aktivieren Sie bitte die ZLIB Erweiterung JSON. Weitere Information finden Sie <a href="http://de3.php.net/manual/de/zlib.setup.php">hier</a>');
    	}

    	$need[] = $ZLIB;

    	// Tidy Prüfung
    	$Tidy = new QDOM();
    	$Tidy->setAttribute('title', 'Tidy Prüfung');

    	if (class_exists('tidy'))
    	{
    		$Tidy->setAttribute('error', 1);
    		$Tidy->setAttribute('message', 'Tidy ist verfügbar');
    	} else
    	{
    		$Tidy->setAttribute('error', 0);
    		$Tidy->setAttribute('message', 'Tidy wird für das Cleanup des HTML Codes benötigt. Es wird nicht vorrausgesetzt, jedoch empfehlen wir diese Erweiterung zu aktivieren. Mehr Informationen finden Sie <a href="http://de3.php.net/manual/de/tidy.setup.php">hier</a>');
    	}

    	$need[] = $Tidy;

    	// APC Cache Prüfung
    	$APC = new QDOM();
    	$APC->setAttribute('title', 'APC Cache Prüfung');

    	if (function_exists('apc_fetch') && function_exists('apc_store'))
    	{
    		apc_store('system_check', 1);

    		if (apc_fetch('system_check') == 1)
    		{
	    		$APC->setAttribute('error', 1);
	    		$APC->setAttribute('message', '');
    		} else
    		{
    			$APC->setAttribute('error', 0);
    			$APC->setAttribute('message', 'APC Cache kann nicht verwendet werden');
    		}

    	} else
    	{
    		$APC->setAttribute('error', 0);
    		$APC->setAttribute('message', 'APC Cache kann nicht verwendet werden');
    	}

    	$need[] = $APC;


    	// Bildbearbeitung
    	$Image = new QDOM();
    	$Image->setAttribute('title', 'Bildbibliothek Prüfung');
    	$Image->setAttribute('error', 0);

    	$image_check_msg = '';

    	if (!class_exists('Imagick'))
		{
			exec(escapeshellcmd('convert'), $im_console);
		}

		// ImageMagick PHP
		if (class_exists('Imagick'))
		{
			$Image->setAttribute('error', 1);
	    	$image_check_msg .= '<p>[ OK ] Image Magick als PHP Erweiterung vorhanden</p>';

		} else
		{
			$image_check_msg .= '<p>[ False ] Image Magick als PHP Erweiterung vorhanden</p>';
		}

		// ImageMagick Konsole
		if (isset($im_console) && is_array($im_console) && count($im_console))
		{
			$Image->setAttribute('error', 1);
	    	$image_check_msg .= '<p>[ OK ] Image Magick wird in der Konsole verwendet</p>';

		} else
		{
			$image_check_msg .= '<p>[ FALSE ] Image Magick wird in der Konsole verwendet</p>';
		}

		// GDLib PHP
		if (function_exists('imagecopyresampled'))
		{
			$Image->setAttribute('error', 1);
	    	$image_check_msg .= '<p>[ OK ] GDLib Erweiterung</p>';
		} else
		{
			$image_check_msg .= '<p>[ FALSE ] GDLib Erweiterung</p>';
		}

		$Image->setAttribute('message', $image_check_msg);
		$need[] = $Image;

		return $need;
    }
}

?>