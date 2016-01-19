<?php

/**
 * This file contains the \QUI\Projects\Media\Image
 */

namespace QUI\Projects\Media;

use QUI;
use QUI\Utils\StringHelper;
use QUI\Utils\System\File;

/**
 * A media image
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.projects.media
 * @licence For copyright and license information, please view the /README.md
 */
class Image extends Item implements QUI\Interfaces\Projects\Media\File
{
    /**
     * Return the real with of the image
     *
     * @return integer | false
     */
    public function getWidth()
    {
        if ($this->getAttribute('image_width')) {
            return $this->getAttribute('image_width');
        }

        $data = File::getInfo(
            $this->getFullPath(),
            array('imagesize' => true)
        );

        if (isset($data['width'])) {
            return $data['width'];
        }

        return false;
    }

    /**
     * Return the real height of the image
     *
     * @return integer | false
     */
    public function getHeight()
    {
        if ($this->getAttribute('image_height')) {
            return $this->getAttribute('image_height');
        }

        $data = File::getInfo(
            $this->getFullPath(),
            array('imagesize' => true)
        );

        if (isset($data['height'])) {
            return $data['height'];
        }

        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Projects\Media\File::createCache()
     */
    public function createCache()
    {
        return $this->createSizeCache();
    }

    /**
     * Return the image path
     *
     * @param string|boolean $maxwidth - (optional)
     * @param string|boolean $maxheight - (optional)
     *
     * @return string
     */
    public function getSizeCachePath($maxwidth = false, $maxheight = false)
    {
        $Media = $this->Media;
        /* @var $Media QUI\Projects\Media */
        $cdir = CMS_DIR . $Media->getCacheDir();
        $file = $this->getAttribute('file');

        if (!$maxwidth && !$maxheight) {
            return $cdir . $file;
        }


        if ($maxwidth > 1200) {
            $maxwidth = 1200;
        }

        if ($maxheight > 1200) {
            $maxheight = 1200;
        }

        $extra  = '';
        $params = $this->getResizeSize($maxwidth, $maxheight);

        $width  = $params['width'];
        $height = $params['height'];

        if ($this->getAttribute('reflection')) {
            $extra = '_reflection';
        }


        if ($width || $height) {
            $part      = explode('.', $file);
            $cachefile = $cdir . $part[0] . '__' . $width . 'x' . $height . $extra . '.'
                         . StringHelper::toLower(end($part));

            if (empty($height)) {
                $cachefile = $cdir . $part[0] . '__' . $width . $extra . '.'
                             . StringHelper::toLower(end($part));
            }

            if ($this->getAttribute('reflection')) {
                $cachefile
                    = $cdir . $part[0] . '__' . $width . 'x' . $height . $extra . '.png';

                if (empty($height)) {
                    $cachefile = $cdir . $part[0] . '__' . $width . $extra . '.png';
                }
            }

        } else {
            $cachefile = $cdir . $file;
        }

        return $cachefile;
    }

    /**
     * Return the image url
     *
     * @param string|boolean $maxwidth - (optional) width
     * @param string|boolean $maxheight - (optional) height
     *
     * @return string
     */
    public function getSizeCacheUrl($maxwidth = false, $maxheight = false)
    {
        $cachePath = $this->getSizeCachePath($maxwidth, $maxheight);
        $cacheUrl  = str_replace(CMS_DIR, URL_DIR, $cachePath);

        return $cacheUrl;
    }

    /**
     * Creates a cache file and takes into account the maximum sizes
     * return the media url
     *
     * @param integer|boolean $maxwidth
     * @param integer|boolean $maxheight
     *
     * @return string - Path to the file
     */
    public function createSizeCacheUrl($maxwidth = false, $maxheight = false)
    {
        $params = $this->getResizeSize($maxwidth, $maxheight);

        $cacheUrl = $this->createSizeCache(
            $params['width'],
            $params['height']
        );

        $cacheUrl = str_replace(CMS_DIR, URL_DIR, $cacheUrl);

        return $cacheUrl;
    }

    /**
     * Creates a cache file and takes into account the maximum sizes
     *
     * @param integer|boolean $maxwidth
     * @param integer|boolean $maxheight
     *
     * @return string - Path to the file
     */
    public function createResizeCache($maxwidth = false, $maxheight = false)
    {
        $params = $this->getResizeSize($maxwidth, $maxheight);

        return $this->createSizeCache(
            $params['width'],
            $params['height']
        );
    }

    /**
     * Return the Image specific max resize params
     *
     * @param boolean|integer $maxwidth - (optional)
     * @param boolean|integer $maxheight - (optional)
     *
     * @return array - array('width' => 100, 'height' => 100)
     */
    public function getResizeSize($maxwidth = false, $maxheight = false)
    {
        $width  = $this->getAttribute('image_width');
        $height = $this->getAttribute('image_height');

        if (!$width || !$height) {
            $info = File::getInfo($this->getFullPath(), array(
                'imagesize' => true
            ));

            $width  = $info['width'];
            $height = $info['height'];
        }

        $maxConfigSize = $this->getProject()->getConfig('media_maxUploadSize');

        $newwidth  = $width;
        $newheight = $height;

        if (!$maxwidth) {
            $maxwidth = $width;
        }

        if (!$maxheight) {
            $maxheight = $height;
        }

        // max höhe breite auf 1200
        if ($maxwidth > $maxConfigSize && $maxConfigSize) {
            $maxwidth = $maxConfigSize;
        }

        if ($maxheight > $maxConfigSize && $maxConfigSize) {
            $maxheight = $maxConfigSize;
        }

        // Breite
        if ($newwidth > $maxwidth) {
            $resize_by_percent = ($maxwidth * 100) / $newwidth;

            $newheight = (int)round(($newheight * $resize_by_percent) / 100);
            $newwidth  = $maxwidth;
        }

        // Höhe
        if ($newheight > $maxheight) {
            $resize_by_percent = ($maxheight * 100) / $newheight;

            $newwidth  = (int)round(($newwidth * $resize_by_percent) / 100);
            $newheight = $maxheight;
        }

        return array(
            'width' => $newwidth,
            'height' => $newheight
        );
    }

    /**
     * Create a cache file with the new width and height
     *
     * @param integer|boolean $width - (optional)
     * @param integer|boolean $height - (optional)
     *
     * @return string - URL to the cachefile
     *
     * @throws QUI\Exception
     */
    public function createSizeCache($width = false, $height = false)
    {
        if (!$this->getAttribute('active')) {
            return false;
        }

        $Media     = $this->Media;
        $original  = $this->getFullPath();
        $cachefile = $this->getSizeCachePath($width, $height);

        if (file_exists($cachefile)) {
            return $cachefile;
        }

        // Cachefolder erstellen
        $this->getParent()->createCache();

        $effects = $this->getEffects();


        if ($width === false && $height === false && empty($effects)) {
            File::copy($original, $cachefile);
            return $cachefile;
        }

        // create image
        $Image = $Media->getImageManager()->make($original);

        if ($width || $height) {
            if (!$width) {
                $width = null;
            }

            if (!$height) {
                $height = null;
            }

            $Image->resize($width, $height, function ($Constraint) {
                $Constraint->aspectRatio();
                $Constraint->upsize();
            });
        }

        // effects
        if (isset($effects['blur'])
            && is_numeric($effects['blur'])
        ) {
            $blur = (int)$effects['blur'];

            if ($blur > 0 && $blur <= 100) {
                $Image->blur($blur);
            }
        }

        if (isset($effects['brightness'])
            && is_numeric($effects['brightness'])
        ) {
            $brightness = (int)$effects['brightness'];

            if ($brightness !== 0 && $brightness >= -100
                && $brightness <= 100
            ) {
                $Image->brightness($brightness);
            }
        }

        if (isset($effects['contrast'])
            && is_numeric($effects['contrast'])
        ) {
            $contrast = (int)$effects['contrast'];

            if ($contrast !== 0 && $contrast >= -100 && $contrast <= 100) {
                $Image->contrast($contrast);
            }
        }

        if (isset($effects['greyscale'])
            && $effects['greyscale'] == 1
        ) {
            $Image->greyscale();
        }

        // watermark
        $Watermark = $this->getWatermark();

        try {
            if ($Watermark) {
                $pos   = $this->getWatermarkPosition();
                $ratio = $this->getWatermarkRatio();

                $WatermarkImage = $Media->getImageManager()->make(
                    $Watermark->getFullPath()
                );

                switch ($pos) {
                    case "top-left":
                    case "top":
                    case "top-right":
                    case "left":
                    case "center":
                    case "right":
                    case "bottom-left":
                    case "bottom":
                    case "bottom-right":
                        $watermarkPosition = $pos;
                        break;

                    default:
                        $watermarkPosition = 'bottom-right';
                        break;
                }

                // ratio calc
                if ($ratio) {
                    $imageHeight = $Image->getHeight();
                    $imageWidth  = $Image->getWidth();

                    $imageHeight = $imageHeight * ($ratio / 100);
                    $imageWidth  = $imageWidth * ($ratio / 100);

                    $WatermarkImage->resize($imageWidth, $imageHeight, function ($Constraint) {
                        $Constraint->aspectRatio();
                        $Constraint->upsize();
                    });
                }

                $Image->insert($WatermarkImage, $watermarkPosition);
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::addInfo($Exception->getMessage(), array(
                'file' => $this->getFullPath(),
                'fileId' => $this->getId(),
                'info' => 'watermark creation'
            ));
        }

        // create folders
        File::mkdir(dirname($cachefile));

        // save cache image
        $Image->save($cachefile);

        QUI::getEvents()->fireEvent('mediaCreateSizeCache', array($this, $Image));


        return $cachefile;
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Projects\Media\File::deleteCache()
     */
    public function deleteCache()
    {
        $Media   = $this->Media;
        $Project = $Media->getProject();

        $cdir = CMS_DIR . $Media->getCacheDir();
        $file = $this->getAttribute('file');

        $cachefile = $cdir . $file;
        $cacheData = pathinfo($cachefile);

        $fileData = File::getInfo($this->getFullPath());
        $files    = File::readDir($cacheData['dirname'], true);
        $filename = $fileData['filename'];

        foreach ($files as $file) {
            $len = strlen($filename);

            if (substr($file, 0, $len + 2) == $filename . '__') {
                File::unlink($cacheData['dirname'] . '/' . $file);
            }
        }

        File::unlink($cachefile);

        // delete admin cache
        $cache_folder
            = VAR_DIR . 'media_cache/' . $Project->getAttribute('name') . '/';

        if (!is_dir($cache_folder)) {
            return;
        }

        $list  = QUI\Utils\System\File::readDir($cache_folder);
        $id    = $this->getId();
        $cache = $id . '_';

        foreach ($list as $file) {
            if (strpos($file, $cache) !== false) {
                File::unlink($cache_folder . $file);
            }
        }
    }

    /**
     * Delete the admin cache
     */
    public function deleteAdminCache()
    {
        $Media   = $this->Media;
        $Project = $Media->getProject();

        $cacheDir = VAR_DIR . 'cache/admin/media/' . $Project->getName() . '/'
                    . $Project->getLang() . '/';

        $cacheName = $this->getId() . '__';

        $files = File::readDir($cacheDir);

        foreach ($files as $file) {
            if (strpos($file, $cacheName) === 0) {
                unlink($cacheDir . $file);
            }
        }
    }

    /**
     * Resize the image and aspect the ratio
     *
     * @param integer $newWidth
     * @param integer $newHeight
     *
     * @return string - Path to the new Image
     *
     * @throws QUI\Exception
     */
    public function resize($newWidth = 0, $newHeight = 0)
    {
        $dir      = CMS_DIR . $this->Media->getPath();
        $original = $dir . $this->getAttribute('file');

        try {
            // create image
            $Image = $this->getMedia()
                ->getImageManager()
                ->make($original);

            $Image->resize(
                $newWidth,
                $newHeight,
                function ($Constraint) {
                    $Constraint->aspectRatio();
                    $Constraint->upsize();
                }
            );

            $Image->save($original);

        } catch (\Exception $Exception) {
            throw new QUI\Exception(
                $Exception->getMessage(),
                $Exception->getCode()
            );
        }

        return $original;
    }

    /**
     * Return the Watermark image file
     *
     * @return Image|boolean
     * @throws QUI\Exception
     */
    public function getWatermark()
    {
        // own watermark?
        $imageEffects = $this->getEffects();

        if (is_array($imageEffects) && !isset($imageEffects['watermark'])) {
            $imageEffects['watermark'] = 'default';
        }


        if (!$imageEffects || $imageEffects['watermark'] === '') {
            return false;
        }

        if ($imageEffects['watermark'] == 'default') {
            try {
                $Project = $this->getProject();

                return Utils::getImageByUrl($Project->getConfig('media_watermark'));

            } catch (QUI\Exception $Exception) {
            }

            return false;
        }

        try {
            return Utils::getImageByUrl($imageEffects['watermark']);

        } catch (QUI\Exception $Exception) {
        }

        return false;
    }

    /**
     * Return the Watermark image file
     *
     * @return Image|boolean
     * @throws QUI\Exception
     */
    public function getWatermarkPosition()
    {
        $imageEffects = $this->getEffects();

        if ($imageEffects
            && isset($imageEffects['watermark_position'])
            && !empty($imageEffects['watermark_position'])
        ) {
            return $imageEffects['watermark_position'];
        }

        // global watermark position?
        $Project = $this->getProject();

        if ($Project->getConfig('media_watermark_position')) {
            return $Project->getConfig('media_watermark_position');
        }

        return false;
    }

    /**
     * @return array|bool|false|string
     */
    public function getWatermarkRatio()
    {
        $imageEffects = $this->getEffects();

        if ($imageEffects
            && isset($imageEffects['watermark_ratio'])
            && !empty($imageEffects['watermark_ratio'])
        ) {
            return $imageEffects['watermark_ratio'];
        }

        // global watermark position?
        $Project = $this->getProject();

        if ($Project->getConfig('media_watermark_ratio')) {
            return $Project->getConfig('media_watermark_ratio');
        }

        return false;
    }

    /**
     * Hash methods
     */

    /**
     * Generate the MD5 file hash and set it to the Database and to the Object
     */
    public function generateMD5()
    {
        if (!file_exists($this->getFullPath())) {
            throw new QUI\Exception(
                QUI::getLocale()
                    ->get('quiqqer/system', 'exception.file.not.found', array(
                        'file' => $this->getAttribute('file')
                    )),
                404
            );
        }

        $md5 = md5_file($this->getFullPath());

        $this->setAttribute('md5hash', $md5);

        QUI::getDataBase()->update(
            $this->Media->getTable(),
            array('md5hash' => $md5),
            array('id' => $this->getId())
        );
    }

    /**
     * Generate the SHA1 file hash and set it to the Database and to the Object
     */
    public function generateSHA1()
    {
        if (!file_exists($this->getFullPath())) {
            throw new QUI\Exception(
                QUI::getLocale()
                    ->get('quiqqer/system', 'exception.file.not.found', array(
                        'file' => $this->getAttribute('file')
                    )),
                404
            );
        }

        $sha1 = sha1_file($this->getFullPath());

        $this->setAttribute('sha1hash', $sha1);

        QUI::getDataBase()->update(
            $this->Media->getTable(),
            array('sha1hash' => $sha1),
            array('id' => $this->getId())
        );
    }
}
