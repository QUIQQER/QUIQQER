<?php

/**
 * MediaFile Image
 * Image Objekt im MediaCenter
 *
 * @author PCSG - Henning
 * @package com.pcsg.pms.media
 *
 * @copyright  2008 PCSG
 * @version    $Revision: 4722 $
 * @since      Class available since Release P.MS 0.9
 */

class MF_Image extends MF_File implements iMF
{
    protected $_TYPE = 'IMAGE';

	/**
	 * Konstruktor
	 *
	 * @param Media $Media
	 * @param array $attributes
	 */
	public function __construct(Media $Media, array $attributes)
	{
		parent::__construct($Media, $attributes);

		$roundcorners = $this->getAttribute('roundcorners');
		$watermark    = json_decode($this->getAttribute('watermark'), true);

		$this->setAttribute('roundcorners', json_decode($roundcorners, true));
		$this->setAttribute('watermark', $watermark);

		// Prüfen ob das Projekt ein Wasserzeichen hat
		// Wenn Wasserzeichen nicht gesetzt ist
		/*
		if ($watermark && isset($watermark['image'])) {
            return;
		}

		$Project = $Media->getProject();
		$image   = $Project->getConfig('watermark_image');

		/*
		if (empty($image)) {
            return;
		}

		$this->setWatermark(array(
		    'image'    => $image,
		    'position' => $Project->getConfig('watermark_position'),
		    'active'   => 0,
		    'percent'  => $Project->getConfig('watermark_percent')
 		));
 		*/
	}

	/**
	 * Gibt die Attribute als Array zurück
	 *
	 * @return Array
	 */
	public function toArray()
	{
		/**
		 * Runde Ecken
		 */
		$roundcorners = $this->getAttribute('roundcorners');

		if (is_array($roundcorners) && isset($roundcorners['radius']))
		{
			$this->setAttribute('rc_radius', $roundcorners['radius']);
			$this->setAttribute('rc', true);
		}

		if (is_array($roundcorners) && isset($roundcorners['background']))
		{
			$this->setAttribute('rc_bg', $roundcorners['background']);
			$this->setAttribute('rc', true);
		}

		/**
		 * Wasserzeichen
		 */
		$watermark = $this->getAttribute('watermark');

		if (is_array($watermark) && isset($watermark['image'])) {
			$this->setAttribute('watermark_image', $watermark['image']);
		}

		if (is_array($watermark) && isset($watermark['position'])) {
			$this->setAttribute('watermark_position', $watermark['position']);
		}

		$attributes        = $this->getAllAttributes();
		$attributes['url'] = $this->getUrl();

		$file = $this->_Media->getAttribute('media_dir').$this->getAttribute('file');

		if (file_exists($file))
		{
			$filesize           = filesize($file);
			$attributes['size'] = \QUI\Utils\System\File::formatSize($filesize, 2);
		}

		return $attributes;
	}

	/**
	 * Erzeugt eine Cachedatei mit Beachtung von maximalen Größen
	 *
	 * @param Integer | Bool $maxwidth
	 * @param Integer | Bool $maxheight
	 */
	public function createResizeCache($maxwidth=false, $maxheight=false)
	{
		$width  = $this->getAttribute('image_width');
		$height = $this->getAttribute('image_height');

		$newwidth  = $width;
		$newheight = $height;

		if (!$maxwidth) {
		    $maxwidth = $width;
		}

		if (!$maxheight) {
		    $maxheight = $height;
		}

		// Breite
		if ($newwidth > $maxwidth)
		{
			$resize_by_percent = ($maxwidth * 100)/ $newwidth;

			$newheight = (int)round(($newheight * $resize_by_percent)/100);
			$newwidth  = $maxwidth;
		}

		// Höhe
		if ($newheight > $maxheight)
		{
			$resize_by_percent = ($maxheight * 100)/ $newheight;

			$newwidth = (int)round(($newwidth * $resize_by_percent)/100);
			$newheight  = $maxheight;
		}

		return $this->createCache($newwidth, $newheight);
	}

	/**
	 * Cachedatei erzeugen
	 *
	 * @param Integer $width
	 * @param Integer $height
	 * @return String - Pfad zur Cachedatei
	 */
	public function createCache($width=false, $height=false)
	{
		if (!$this->getAttribute('active')) {
			return false;
		}

		$Media   = $this->_Media; /* @var $Media Media */
		$Project = $Media->getProject();

		$mdir = $Media->getAttribute('media_dir');
		$cdir = $Media->getAttribute('cache_dir');
		$file = $this->getAttribute('file');

		$original = $mdir . $file;
		$extra    = '';

		if ($this->getAttribute('reflection')) {
			$extra = '_reflection';
		}

		if ($width || $height)
		{
			$part      = explode('.', $file);
			$cachefile  = $cdir . $part[0] .'__'. $width .'x'. $height . $extra .'.'. \QUI\Utils\String::toLower( end($part) );

			if ($height == false) {
			    $cachefile2 = $cdir . $part[0] .'__'. $width . $extra .'.'. \QUI\Utils\String::toLower( end($part) );
			}

			if ($this->getAttribute('reflection'))
			{
				$cachefile = $cdir . $part[0] .'__'. $width .'x'. $height . $extra .'.png';

				if ($height == false) {
			        $cachefile2 = $cdir . $part[0] .'__'. $width . $extra .'.png';
			    }
			}

		} else
		{
			$cachefile = $cdir.$file;
		}

		// Link Cache erstellen
		$this->_createLinkCache();

		if (file_exists($cachefile) &&
		    // falls es eine Cachedatei ohne x geben soll
		    (isset($cachefile2) && file_exists($cachefile2)))
        {
			return $cachefile;
		}

		// Cachefolder erstellen
		$this->getParent()->createCache();

		if ($width || $height)
		{
			$this->resize($cachefile, (int)$width, (int)$height);

			// falls höhe nicht angegeben ist, das Cachefile auch ohne x anlegen
			if (isset($cachefile2)) {
                $this->resize($cachefile2, (int)$width, (int)$height);
			}

		} else
		{
			try
			{
				\QUI\Utils\System\File::copy($original, $cachefile);
			} catch (\QUI\Exception $e)
			{
			    // Fehler loggen
			    System_Log::writeException($e);
			}
		}

		// Spiegelung
		if ($this->getAttribute('reflection'))
		{
			if (!file_exists($cachefile))
			{
				Utils_Image::reflection($original, $cachefile);
			} else
			{
				Utils_Image::reflection($cachefile, $cachefile);
			}

		    if (isset($cachefile2)) {
                Utils_Image::reflection($cachefile, $cachefile2);
			}

			if ($width || $height)
			{
				Utils_Image::resize($cachefile, $cachefile, (int)$width, (int)$height);

    			if (isset($cachefile2)) {
                    Utils_Image::resize($cachefile2, $cachefile2, (int)$width, (int)$height);
    			}
			}
		}

		/**
		 *  Runde Ecken
		 */
		if ($this->getAttribute('roundcorners'))
		{
			$roundcorner = $this->getAttribute('roundcorners');

			if (!is_array($roundcorner)) {
				$roundcorner = json_decode($roundcorner, true);
			}

			if (isset($roundcorner['radius']) &&
				isset($roundcorner['background']))
			{
				try
				{
					Utils_Image::roundCorner($cachefile, $cachefile, array(
						'radius' 	 => (int)$roundcorner['radius'],
						'background' => $roundcorner['background']
					));

    				if (isset($cachefile2))
    				{
                        Utils_Image::roundCorner($cachefile2, $cachefile, array(
    						'radius' 	 => (int)$roundcorner['radius'],
    						'background' => $roundcorner['background']
    					));
        			}

				} catch (\QUI\Exception $e)
				{
					System_Log::writeException($e);
				}
			}
		}

		/**
		 * Wasserzeichen
		 */
		if (!$this->getAttribute('watermark')) {
			return $cachefile;
		}

		$watermark = $this->getAttribute('watermark');

		if (!is_array($watermark)) {
			$watermark = json_decode($watermark, true);
		}

		if (empty($watermark) || !$watermark['active']) {
            return $cachefile;
		}


		// wenn kein Wasserzeichen, dann schauen ob im Projekt eines gibt
		if (!isset($watermark['image']) &&
		    $Project->getConfig('watermark_image'))
		{
            $watermark['image'] = $Project->getConfig('watermark_image');
		}

		if (!isset($watermark['image'])) {
			return $cachefile;
		}


		$params  = \QUI\Utils\String::getUrlAttributes($watermark['image']);

		if (!isset($params['id']) || !isset($params['project'])) {
			return $cachefile;
		}

		try
		{
		    $WZ_Project = \QUI::getProject($params['project']);
			$WZ_Media   = $Project->getMedia();
			$_Image     = $WZ_Media->get( (int)$params['id'] );

			if ($_Image->getType() != 'IMAGE') {
				return $cachefile;
			}

			/* @var $_Image MF_Image */

			$d_media = $WZ_Media->getAttribute('media_dir');
			$f_water = $d_media . $_Image->getPath();

			if (!file_exists($f_water)) {
				return $cachefile;
			}

			// falls keine position, dann die vom projekt
		    if (!isset($watermark['position']) &&
		        $Project->getConfig('watermark_position'))
		    {
				$watermark['position'] = $Project->getConfig('watermark_position');
			}

			if (isset($watermark['position'])) {
				$position = $watermark['position'];
			}

			// falls keine prozent, dann die vom projekt
			if ((!isset($watermark['percent']) || !$watermark['percent']) &&
			    $Project->getConfig('watermark_percent'))
            {
			    $watermark['percent'] = $Project->getConfig('watermark_percent');
			}



			$c_info = \QUI\Utils\System\File::getInfo($cachefile, array('imagesize' => true));
			$w_info = \QUI\Utils\System\File::getInfo($f_water, array('imagesize' => true));

			// Prozentuale Grösse - Wasserzeichen
			if (isset($watermark['percent']) && $watermark['percent'])
		    {
		        $w_width  = $c_info['width'];
		        $w_height = $c_info['height'];

                $watermark_width  = ($w_width / 100 * $watermark['percent']);
                $watermark_height = ($w_height / 100 * $watermark['percent']);

                $watermark_temp = VAR_DIR .'tmp/'. md5($cachefile);

                // Resize des Wasserzeichens
                if (!file_exists($watermark_temp)) {
                    \QUI\Utils\System\File::copy($f_water, $watermark_temp);
                }

                Utils_Image::resize($watermark_temp, $watermark_temp, $watermark_width);
                Utils_Image::resize($watermark_temp, $watermark_temp, 0, $watermark_height);

                $w_info  = \QUI\Utils\System\File::getInfo($watermark_temp, array('imagesize' => true));
                $f_water = $watermark_temp;
			}


			$top  = 0;
			$left = 0;

			switch ($position)
			{
				case 'topright':
					$left = ($c_info['width'] - $w_info['width']);
				break;

				case 'bottomleft':
					$top = ($c_info['height'] - $w_info['height']);
				break;

				case 'bottomright':
					$top  = ($c_info['height'] - $w_info['height']);
					$left = ($c_info['width'] - $w_info['width']);
				break;

				case 'center':
					$top  = (($c_info['height'] - $w_info['height']) / 2);
					$left = (($c_info['width'] - $w_info['width']) / 2);
				break;
			}

			Utils_Image::watermark($cachefile, $f_water, false, $top, $left);

			if (isset($cachefile2)) {
                Utils_Image::watermark($cachefile2, $f_water, false, $top, $left);
    		}

		} catch (\QUI\Exception $e)
		{
			System_Log::writeException($e);
			// nothing
		}

		return $cachefile;
	}

	/**
	 * Admincache erstellen für Bilder
	 *
	 * @param unknown_type $width
	 * @param unknown_type $height
	 * @return unknown
	 */
	public function createAdminCache($width=false, $height=false)
	{
		$Media = $this->_Media;
		$mdir  = $Media->getAttribute('media_dir');
		$cdir  = VAR_DIR .'media_cache/'. $this->_Project->getAttribute('name') .'/';
		$file  = $this->getAttribute('file');

		$original = $mdir . $file;

		if ($width || $height)
		{
			$cachefile = $cdir . $this->getId() .'_'. $width .'x'. $height;
		} else
		{
			$cachefile = $cdir . $this->getId() .'_';
		}

		if (file_exists($cachefile)) {
			return $cachefile;
		}

		// Cachefolder erstellen
		\QUI\Utils\System\File::mkdir($cdir);

		if ($width || $height)
		{
			$this->resize($cachefile, (int)$width, (int)$height);
		} else
		{
			\QUI\Utils\System\File::copy($original, $cachefile);
		}

		return $cachefile;
	}

	/**
	 * Cachedatei löschen
	 */
	public function deleteCache()
	{
		$cachefile = $this->_Media->getAttribute('cache_dir') . $this->getAttribute('file');

		// Alle Cachefiles des Bildes löschen
		$path = pathinfo($cachefile);
		$expl = explode('.', $this->getAttribute('name'));

		$files = \QUI\Utils\System\File::readDir($path['dirname'], true);

		foreach ($files as $file)
		{
			$len = strlen($expl[0]);

			if (substr($file,0, $len+2) == $expl[0].'__') {
				unlink($path['dirname'].'/'.$file);
			}
		}

		if (file_exists($cachefile)) {
			unlink($cachefile);
		}

		// Admincache löschen
		$acdir = VAR_DIR .'media_cache/'. $this->_Project->getAttribute('name') .'/';
		$files = \QUI\Utils\System\File::readDir($acdir, true);
		$id    = $this->getId();

		foreach ($files as $file)
		{
			if (strpos($file, $id.'_') === true &&
			    file_exists($acdir.'/'.$file))
			{
				unlink($acdir.'/'.$file);
			}
		}
	}

	/**
	 * Bildgrösse ändern
	 *
	 * @param String $new_image - Pfad zum neuen Bild
	 * @param Integer $new_width
	 * @param Integer $new_height
	 * @return unknown
	 */
	public function resize($new_image, $new_width = 0, $new_height = 0)
	{
		try
		{
			$original = $this->_Media->getAttribute('media_dir').$this->getAttribute('file');
			return \QUI\Utils\System\File::resize($original, $new_image, $new_width, $new_height);

		} catch (\QUI\Exception $e)
		{
			System_Log::writeException($e);
			return $original;
		}
	}

	/**
	 * Setzt die Attribute für Runde Ecken
	 *
	 * @param String $background - #FFFFFF
	 * @param Integer $radius    - 10
	 */
	public function setRoundCorners($background='', $radius='')
	{
		if (empty($background)) {
			throw new \QUI\Exception('Please enter a background color');
		}

		if (empty($radius)) {
			throw new \QUI\Exception('Please enter a Radius');
		}

		$roundcorners = array(
			'background' => $background,
			'radius'     => $radius
		);

		$this->setAttribute('roundcorners', $roundcorners);
	}

	/**
	 * Wasserzeichen auf das Bild setzen
	 *
	 * @param Array $params
	 * 	image
	 * 	position
	 * 	active
	 *  percent
	 */
	public function setWatermark($params=array())
	{
	    $watermark = $this->getAttribute('watermark');

	    // jetziges Wasserzeichen setzen, falls nichts übergeben wurde
	    if (isset($watermark['image']) &&
	        !isset($params['image']))
        {
            $params['image'] = $watermark['image'];
	    }

	    if (isset($watermark['position']) &&
	        !isset($params['position']))
        {
            $params['position'] = $watermark['position'];
	    }

	    if (isset($watermark['active']) &&
	        !isset($params['active']))
        {
            $params['active'] = $watermark['active'];
	    }


		// falls deaktiviert
		if ($params['active'] == 0)
		{
            $this->setAttribute('watermark', '');
            return;
		}

		$this->setAttribute('watermark', $params);
	}

	/**
	 * Bild speichern
	 */
	public function save()
	{
		$name = $this->getAttribute('name');

		// Prüfung des Namens - Sonderzeichen
        Media::checkMediaName($name);

		// Falls runde Ecken gewollt wird
		$roundcorners = $this->getAttribute('roundcorners');
		$watermark	  = $this->getAttribute('watermark');

		$values = array(
			'roundcorners' => null,
			'watermark'	   => null
		);

		if (is_array($roundcorners))
		{
			$values['roundcorners'] = json_encode($roundcorners);
		} elseif (is_string($roundcorners))
		{
			$values['roundcorners'] = $roundcorners;
		}

		if (is_array($watermark))
		{
			$values['watermark'] = json_encode($watermark);
		} elseif (is_string($watermark))
		{
			$values['watermark'] = $watermark;
		}

		QUI::getDB()->updateData(
			$this->_TABLE,
			$values,
			array('id' => $this->getId())
		);

		parent::save();
	}
}

?>