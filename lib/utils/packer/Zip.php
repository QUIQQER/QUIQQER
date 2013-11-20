<?php

/**
 * This file contains Utils_Packer_Zip
 */

/**
 * ZIP archiver
 * zip and unzip files
 *
 * @copyright www.pcsg.de (Henning Leutz)
 * @uses ZipArchive
 *
 * @package com.pcsg.qui.utils.packer
 */

class Utils_Packer_Zip
{
	/**
	 * constructor
	 * Checks if ZipArchive exists as a php module
	 */
	public function __construct()
	{
		self::check();
	}

	/**
	 * Check, if ZipArchive is enabled
	 *
	 * @return Bool
	 * @throws \QUI\Exception
	 */
	static function check()
	{
	    if ( !class_exists('ZipArchive') )
		{
			throw new \QUI\Exception(
				'Class ZipArchive not exist', 404
			);
		}

		return true;
	}

	/**
	 * From a folder created a ZIP Archive
	 *
	 * @param String $folder 	- Folder which is to be packed
	 * @param String $zipfile 	- Name of new Zipfiles
	 * @param Array $ignore 	- Folder to be ignored
	 */
	static function zip($folder, $zipfile, $ignore=array())
	{
	    self::check();

		$Zip = new ZipArchive();

		if ( $Zip->open($zipfile, ZIPARCHIVE::CREATE) !== true ) {
            throw new \QUI\Exception( 'cannot open '. $zipfile );
        }

        if ( !is_array($ignore) ) {
        	$ignore = array();
        }

        if ( substr($folder, -1) != '/' ) {
        	$folder .= '/';
        }

		$File  = new \QUI\Utils\System\File();
		$files = $File->readDirRecursiv( $folder );

		foreach ( $files as $_folder => $_file )
		{
			if ( !empty($ignore) && in_array($_folder, $ignore) ) {
				continue;
			}

			$oldfolder = $folder.$_folder;

			for ( $i = 0, $len = count($_file); $i < $len; $i++ )
			{
				if ( file_exists( $oldfolder . $_file[$i] ) )
				{
					$Zip->addFile(
					    $oldfolder . $_file[$i],
					    $_folder.$_file[$i]
				    );
				}
			}
		}

		$Zip->close();
	}

	/**
	 * Unzip the file
	 *
	 * @param String $zipfile 	- path to zip file
	 * @param String $to		- path to the destination folder
	 *
	 * @throws \QUI\Exception
	 */
	static function unzip($zipfile, $to)
	{
	    self::check();

		if (!file_exists($zipfile)) {
			throw new \QUI\Exception('Zip Archive '. $zipfile .' doesn\'t exist',	404);
		}

		$Zip = new ZipArchive();

		if ( $Zip->open($zipfile) === true )
		{
			$Zip->extractTo($to);
			$Zip->close();

			return;
		}

		throw new \QUI\Exception('Error on Extract Zip Archive');
	}
}

?>