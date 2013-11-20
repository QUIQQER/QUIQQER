<?php

/**
 * This file contains the Projects_Media_File
 */

/**
 * A media file
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.projects.media
 */

class Projects_Media_File
      extends Projects_Media_Item
      implements Interface_Projects_Media_File
{
    /**
	 * (non-PHPdoc)
	 * @see Interface_Projects_Media_File::createCache()
	 */
	public function createCache()
	{
	    if ( !$this->getAttribute('active') ) {
			return false;
		}

	    $WHITE_LIST_EXTENSION = array(
			'pdf', 'txt', 'xml', 'doc', 'pdt', 'xls', 'csv', 'txt',
			'swf', 'flv',
			'mp3', 'ogg', 'wav',
			'mpeg', 'avi', 'mpg', 'divx', 'mov', 'wmv',
			'zip', 'rar', '7z', 'gzip', 'tar', 'tgz', 'ace',
			'psd'
		);

		$Media   = $this->_Media; /* @var $Media Projects_Media */
		$Project = $Media->getProject();

		$mdir = CMS_DIR . $Media->getPath();
		$cdir = CMS_DIR . $Media->getCacheDir();
		$file = $this->getAttribute('file');

		$original  = $mdir . $file;
		$cachefile = $cdir . $file;

		$extension = \QUI\Utils\String::pathinfo($original, PATHINFO_EXTENSION);

		if ( !in_array( $extension, $WHITE_LIST_EXTENSION ) )
		{
            \QUI\Utils\System\File::unlink( $cachefile );

			return $original;
		}

   		// Nur wenn Extension in Whitelist ist dann Cache machen
	    if ( file_exists( $cachefile ) ) {
			return $cachefile;
		}

		// Cachefolder erstellen
		$this->getParent()->createCache();

		try
		{
			\QUI\Utils\System\File::copy( $original, $cachefile );
		} catch ( \QUI\Exception $e )
		{
			// nothing
		}

        return $cachefile;
	}

    /**
     * (non-PHPdoc)
     * @see Interface_Projects_Media_File::deleteCache()
     */
    public function deleteCache()
	{
        $Media   = $this->_Media;
		$Project = $Media->getProject();

		$cdir = CMS_DIR . $Media->getCacheDir();
		$file = $this->getAttribute( 'file' );

		\QUI\Utils\System\File::unlink( $cdir . $file );
	}

	/**
	 * Generate the MD5 file hash and set it to the Database and to the Object
	 */
	public function generateMD5()
	{
        $md5 = md5_file( $this->getFullPath() );

        $this->setAttribute('md5hash', $md5);

        QUI::getDataBase()->update(
            $this->_Media->getTable(),
            array('md5hash' => $md5),
			array('id' => $this->getId())
        );
	}

	/**
	 * Generate the SHA1 file hash and set it to the Database and to the Object
	 */
	public function generateSHA1()
	{
        $sha1 = sha1_file( $this->getFullPath() );

        $this->setAttribute('sha1hash', $sha1);

        QUI::getDataBase()->update(
            $this->_Media->getTable(),
            array('sha1hash' => $sha1),
			array('id' => $this->getId())
        );
	}
}

?>