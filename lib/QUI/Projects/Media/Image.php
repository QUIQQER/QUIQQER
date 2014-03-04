<?php

/**
 * This file contains the \QUI\Projects\Media\Image
 */

namespace QUI\Projects\Media;

/**
 * A media image
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.projects.media
 */

class Image extends \QUI\Projects\Media\Item implements \QUI\Interfaces\Projects\Media\File
{
    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Projects\Media\File::createCache()
     */
    public function createCache()
    {
        return $this->createSizeCache();
    }

    /**
     * Creates a cache file and takes into account the maximum sizes
     * return the media url
     *
     * @param Integer|Bool $maxwidth
     * @param Integer|Bool $maxheight
     *
     * @return String - Path to the file
     */
    public function createSizeCacheUrl($maxwidth=false, $maxheight=false)
    {
        $params = $this->getResizeSize(
            $maxwidth,
            $maxheight
        );

        $cacheUrl = $this->createSizeCache(
            $params['width'],
            $params['height']
        );

        $cacheUrl = str_replace( CMS_DIR, URL_DIR, $cacheUrl );

        return $cacheUrl;
    }

    /**
     * Creates a cache file and takes into account the maximum sizes
     *
     * @param Integer|Bool $maxwidth
     * @param Integer|Bool $maxheight
     *
     * @return String - Path to the file
     */
    public function createResizeCache($maxwidth=false, $maxheight=false)
    {
        $params = $this->getResizeSize(
            $maxwidth,
            $maxheight
        );

        return $this->createSizeCache(
            $params['width'],
            $params['height']
        );
    }

    /**
     * Return the Image specific max resize params
     *
     * @param unknown_type $maxwidth
     * @param unknown_type $maxheight
     * @return array - array('width' => 100, 'height' => 100)
     */
    public function getResizeSize($maxwidth=false, $maxheight=false)
    {
        $width  = $this->getAttribute('image_width');
        $height = $this->getAttribute('image_height');

        if ( !$width || !$height )
        {
            $info = \QUI\Utils\System\File::getInfo($this->getFullPath(), array(
                'imagesize' => true
            ));

            $width  = $info['width'];
            $height = $info['height'];
        }

        $newwidth  = $width;
        $newheight = $height;

        if ( !$maxwidth ) {
            $maxwidth = $width;
        }

        if ( !$maxheight ) {
            $maxheight = $height;
        }

        // Breite
        if ( $newwidth > $maxwidth )
        {
            $resize_by_percent = ($maxwidth * 100) / $newwidth;

            $newheight = (int)round(($newheight * $resize_by_percent) / 100);
            $newwidth  = $maxwidth;
        }

        // Höhe
        if ( $newheight > $maxheight )
        {
            $resize_by_percent = ($maxheight * 100) / $newheight;

            $newwidth  = (int)round(($newwidth * $resize_by_percent) / 100);
            $newheight = $maxheight;
        }

        return array(
            'width'  => $newwidth,
            'height' => $newheight
        );
    }

    /**
     * Create a cache file with the new width and height
     *
     * @param Integer $width
     * @param Integer $height
     * @return String - URL to the cachefile
     */
    public function createSizeCache($width=false, $height=false)
    {
        if ( !$this->getAttribute('active') ) {
            return false;
        }

        $Media   = $this->_Media; /* @var $Media \QUI\Projects\Media */
        $Project = $Media->getProject();

        $mdir = CMS_DIR . $Media->getPath();
        $cdir = CMS_DIR . $Media->getCacheDir();
        $file = $this->getAttribute('file');

        $original = $mdir . $file;
        $extra    = '';

        if ( $this->getAttribute('reflection') ) {
            $extra = '_reflection';
        }


        if ( $width || $height )
        {
            $part      = explode('.', $file);
            $cachefile = $cdir . $part[0] .'__'. $width .'x'. $height . $extra .'.'. \QUI\Utils\String::toLower( end($part) );

            if ( empty( $height ) ) {
                $cachefile2 = $cdir . $part[0] .'__'. $width . $extra .'.'. \QUI\Utils\String::toLower( end($part) );
            }

            if ( $this->getAttribute('reflection') )
            {
                $cachefile = $cdir . $part[0] .'__'. $width .'x'. $height . $extra .'.png';

                if ( empty( $height ) ) {
                    $cachefile2 = $cdir . $part[0] .'__'. $width . $extra .'.png';
                }
            }

        } else
        {
            $cachefile = $cdir . $file;
        }

        // Link Cache erstellen -> macht das noch sinn?
        // $this->_createLinkCache();

        if ( file_exists($cachefile) &&
             // falls es eine Cachedatei ohne x geben soll
            (isset($cachefile2) && file_exists($cachefile2)) )
        {
            return $cachefile;
        }

        // Cachefolder erstellen
        $this->getParent()->createCache();

        if ( $width || $height )
        {
            $this->resize( $cachefile, (int)$width, (int)$height );

            // falls höhe nicht angegeben ist, das Cachefile auch ohne x anlegen
            if ( isset( $cachefile2 ) ) {
                $this->resize( $cachefile2, (int)$width, (int)$height );
            }

        } else
        {
            try
            {
                if ( !file_exists($cachefile) ) {
                    \QUI\Utils\System\File::copy($original, $cachefile);
                }
            } catch ( \QUI\Exception $e )
            {
                // Fehler loggen
                \QUI\System\Log::writeException($e);
            }
        }

        // Spiegelung
        if ( $this->getAttribute('reflection') )
        {
            if ( !file_exists($cachefile) )
            {
                \QUI\Utils\Image::reflection($original, $cachefile);
            } else
            {
                \QUI\Utils\Image::reflection($cachefile, $cachefile);
            }

            if ( isset($cachefile2) ) {
                \QUI\Utils\Image::reflection($cachefile, $cachefile2);
            }

            if ( $width || $height )
            {
                \QUI\Utils\Image::resize($cachefile, $cachefile, (int)$width, (int)$height);

                if ( isset($cachefile2) ) {
                    \QUI\Utils\Image::resize($cachefile2, $cachefile2, (int)$width, (int)$height);
                }
            }
        }

        /**
         *  Runde Ecken
         */
        if ( $this->getAttribute('roundcorners') )
        {
            $roundcorner = $this->getAttribute('roundcorners');

            if ( !is_array($roundcorner) ) {
                $roundcorner = json_decode($roundcorner, true);
            }

            if ( isset($roundcorner['radius']) &&
                 isset($roundcorner['background']) )
            {
                try
                {
                    \QUI\Utils\Image::roundCorner($cachefile, $cachefile, array(
                        'radius' 	 => (int)$roundcorner['radius'],
                        'background' => $roundcorner['background']
                    ));

                    if ( isset($cachefile2) )
                    {
                        \QUI\Utils\Image::roundCorner($cachefile2, $cachefile, array(
                            'radius' 	 => (int)$roundcorner['radius'],
                            'background' => $roundcorner['background']
                        ));
                    }

                } catch ( \QUI\Exception $e )
                {
                    \QUI\System\Log::writeException($e);
                }
            }
        }

        /**
         * Wasserzeichen
         */
        if ( !$this->getAttribute('watermark') ) {
            return $cachefile;
        }

        $watermark = $this->getAttribute('watermark');

        if ( !is_array($watermark) ) {
            $watermark = json_decode($watermark, true);
        }

        if ( empty($watermark) || !$watermark['active'] ) {
            return $cachefile;
        }


        // wenn kein Wasserzeichen, dann schauen ob im Projekt eines gibt
        if ( !isset($watermark['image'] ) &&
             $Project->getConfig('watermark_image') )
        {
            $watermark['image'] = $Project->getConfig('watermark_image');
        }

        if ( !isset($watermark['image']) ) {
            return $cachefile;
        }


        $params = \QUI\Utils\String::getUrlAttributes($watermark['image']);

        if ( !isset($params['id']) || !isset($params['project']) ) {
            return $cachefile;
        }

        try
        {
            $WZ_Project = \QUI::getProject($params['project']);
            $WZ_Media   = $Project->getMedia();
            $_Image     = $WZ_Media->get( (int)$params['id'] );

            if ( $_Image->getType() != 'IMAGE' ) {
                return $cachefile;
            }

            /* @var $_Image MF_Image */

            $d_media = $WZ_Media->getAttribute('media_dir');
            $f_water = $d_media . $_Image->getPath();

            if ( !file_exists($f_water) ) {
                return $cachefile;
            }

            // falls keine position, dann die vom projekt
            if ( !isset($watermark['position']) &&
                 $Project->getConfig('watermark_position') )
            {
                $watermark['position'] = $Project->getConfig('watermark_position');
            }

            if ( isset($watermark['position']) ) {
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
            if ( isset($watermark['percent']) && $watermark['percent'] )
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

                \QUI\Utils\Image::resize($watermark_temp, $watermark_temp, $watermark_width);
                \QUI\Utils\Image::resize($watermark_temp, $watermark_temp, 0, $watermark_height);

                $w_info  = \QUI\Utils\System\File::getInfo($watermark_temp, array('imagesize' => true));
                $f_water = $watermark_temp;
            }


            $top  = 0;
            $left = 0;

            switch ( $position )
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

            \QUI\Utils\Image::watermark($cachefile, $f_water, false, $top, $left);

            if ( isset($cachefile2) ) {
                \QUI\Utils\Image::watermark($cachefile2, $f_water, false, $top, $left);
            }

        } catch ( \QUI\Exception $e )
        {
            \QUI\System\Log::writeException($e);
            // nothing
        }

        return $cachefile;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Projects\Media\File::deleteCache()
     */
    public function deleteCache()
    {
        $Media   = $this->_Media;
        $Project = $Media->getProject();

        $cdir = CMS_DIR . $Media->getCacheDir();
        $file = $this->getAttribute('file');

        $cachefile = $cdir . $file;

        $path  = pathinfo( $cachefile );
        $parts = explode('.', $file);

        $files = \QUI\Utils\System\File::readDir($path['dirname'], true);

        foreach ( $files as $file )
        {
            $len = strlen( $parts[0] );

            if ( substr($file,0, $len+2) == $parts[0] .'__' ) {
                \QUI\Utils\System\File::unlink( $path['dirname'] .'/'. $file );
            }
        }

        \QUI\Utils\System\File::unlink( $cachefile );

        // delete admin cache
        $cache_folder = VAR_DIR .'media_cache/'. $Project->getAttribute('name') .'/';

        if ( !is_dir($cache_folder) ) {
            return;
        }

        $list  = \QUI\Utils\System\File::readDir( $cache_folder );
        $id    = $this->getId();
        $cache = $id .'_';

        foreach ( $list as $file )
        {
            if ( strpos($file, $cache) !== false ) {
                \QUI\Utils\System\File::unlink( $cache_folder . $file );
            }
        }
    }

    /**
     * Resize the image
     *
     * @param String $new_image - Path to the new image
     * @param Integer $new_width
     * @param Integer $new_height
     *
     * @return String - Path to the new Image
     */
    public function resize($new_image, $new_width=0, $new_height=0)
    {
        $dir      = CMS_DIR . $this->_Media->getPath();
        $original = $dir . $this->getAttribute( 'file' );

        try
        {
            return \QUI\Utils\Image::resize(
                $original,
                $new_image,
                $new_width,
                $new_height
            );

        } catch ( \QUI\Exception $Exception )
        {
            \QUI\System\Log::writeException( $Exception );
        }

        return $original;
    }

    /**
     * Set the attribute for round corners
     *
     * @param String $background - #FFFFFF
     * @param Integer $radius    - 10
     */
    public function setRoundCorners($background='', $radius='')
    {
        if ( empty($background) ) {
            throw new \QUI\Exception('Please set a background color');
        }

        if ( empty($radius) ) {
            throw new \QUI\Exception('Please set a radius');
        }

        $roundcorners = array(
            'background' => $background,
            'radius'     => $radius
        );

        $this->setAttribute('roundcorners', $roundcorners);
    }

    /**
     * Set a watermark to the image
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
        if ( isset($watermark['image']) && !isset($params['image']) ) {
            $params['image'] = $watermark['image'];
        }

        if ( isset($watermark['position']) && !isset($params['position']) ) {
            $params['position'] = $watermark['position'];
        }

        if ( isset($watermark['active']) && !isset($params['active']) ) {
            $params['active'] = $watermark['active'];
        }

        // falls deaktiviert
        if ( $params['active'] == 0 )
        {
            $this->setAttribute('watermark', '');
            return;
        }

        $this->setAttribute('watermark', $params);
    }

    /**
     * Generate the MD5 file hash and set it to the Database and to the Object
     */
    public function generateMD5()
    {
        $md5 = md5_file( $this->getFullPath() );

        $this->setAttribute('md5hash', $md5);

        \QUI::getDataBase()->update(
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

        \QUI::getDataBase()->update(
            $this->_Media->getTable(),
            array('sha1hash' => $sha1),
            array('id' => $this->getId())
        );
    }
}
