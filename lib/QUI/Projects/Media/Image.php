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
     * @return integer|false
     * @throws QUI\Exception
     */
    public function getWidth()
    {
        if ($this->getAttribute('image_width')) {
            return $this->getAttribute('image_width');
        }

        $data = File::getInfo($this->getFullPath(), [
            'imagesize' => true
        ]);

        if (isset($data['width'])) {
            return $data['width'];
        }

        return false;
    }

    /**
     * Return the real height of the image
     *
     * @return integer|false
     * @throws QUI\Exception
     */
    public function getHeight()
    {
        if ($this->getAttribute('image_height')) {
            return $this->getAttribute('image_height');
        }

        $data = File::getInfo($this->getFullPath(), [
            'imagesize' => true
        ]);

        if (isset($data['height'])) {
            return $data['height'];
        }

        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @throws QUI\Exception
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
     *
     * @throws QUI\Exception
     */
    public function getSizeCachePath($maxwidth = false, $maxheight = false)
    {
        $Media = $this->Media;
        /* @var $Media QUI\Projects\Media */
        $cdir = CMS_DIR.$Media->getCacheDir();
        $file = $this->getAttribute('file');

        if ($this->getAttribute('mime_type') == 'image/svg+xml') {
            return $cdir.$file;
        }

        if (!$maxwidth && !$maxheight) {
            return $cdir.$file;
        }


        if ($maxwidth > 1200) {
            $maxwidth = 1200;
        }

        if ($maxheight > 1200) {
            $maxheight = 1200;
        }

        $extra  = '';
        $params = $this->getResizeSize($maxwidth, $maxheight);

        if ($params['height'] > $params['width']) {
            $tempParams = $this->getResizeSize(
                false,
                QUI\Utils\Math::ceilUp($params['height'], 100)
            );
        } else {
            $tempParams = $this->getResizeSize(
                QUI\Utils\Math::ceilUp($params['width'], 100),
                false
            );
        }

        $height = $tempParams['height'];
        $width  = $tempParams['width'];

        if ($this->getAttribute('reflection')) {
            $extra = '_reflection';
        }


        if ($width || $height) {
            $part      = \explode('.', $file);
            $cacheFile = $cdir.$part[0].'__'.$width.'x'.$height.$extra.'.'.StringHelper::toLower(\end($part));

            if (empty($height)) {
                $cacheFile = $cdir.$part[0].'__'.$width.$extra.'.'.StringHelper::toLower(\end($part));
            }

            if ($this->getAttribute('reflection')) {
                $cacheFile = $cdir.$part[0].'__'.$width.'x'.$height.$extra.'.png';

                if (empty($height)) {
                    $cacheFile = $cdir.$part[0].'__'.$width.$extra.'.png';
                }
            }
        } else {
            $cacheFile = $cdir.$file;
        }

        return $cacheFile;
    }

    /**
     * Return the image url
     *
     * @param string|boolean $maxwidth - (optional) width
     * @param string|boolean $maxheight - (optional) height
     *
     * @return string
     *
     * @throws QUI\Exception
     */
    public function getSizeCacheUrl($maxwidth = false, $maxheight = false)
    {
        $cachePath = $this->getSizeCachePath($maxwidth, $maxheight);
        $cacheUrl  = \str_replace(CMS_DIR, URL_DIR, $cachePath);

        if (!\preg_match('/[^a-zA-Z0-9_\-.\/]/i', $cacheUrl)) {
            return $cacheUrl;
        }

        // thanks to http://php.net/manual/de/function.rawurlencode.php#100313
        // thanks to http://php.net/manual/de/function.rawurlencode.php#63751
        $encoded = \implode("/", \array_map(function ($part) {
            $encoded = '';
            $length  = \mb_strlen($part);

            for ($i = 0; $i < $length; $i++) {
                $str = \mb_substr($part, $i, 1);

                if (!\preg_match('/[^a-zA-Z0-9_\-.]/i', $str)) {
                    $encoded .= $str;
                    continue;
                }

                $encoded .= '%'.wordwrap(bin2hex($str), 2, '%', true);
            }

            return $encoded;
        }, \explode("/", $cacheUrl)));

        return $encoded;
    }

    /**
     * Creates a cache file and takes into account the maximum sizes
     * return the media url
     *
     * @param integer|boolean $maxwidth
     * @param integer|boolean $maxheight
     *
     * @return string - Path to the file
     *
     * @throws QUI\Exception
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
     *
     * @throws QUI\Exception
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
     *
     * @throws QUI\Exception
     */
    public function getResizeSize($maxwidth = false, $maxheight = false)
    {
        if ($this->getAttribute('mime_type') == 'image/svg+xml') {
            return [
                'width'  => false,
                'height' => false
            ];
        }

        $width  = $this->getAttribute('image_width');
        $height = $this->getAttribute('image_height');

        if (!$width || !$height) {
            $info = File::getInfo($this->getFullPath(), [
                'imagesize' => true
            ]);

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

            $newheight = (int)\round(($newheight * $resize_by_percent) / 100);
            $newwidth  = $maxwidth;
        }

        // Höhe
        if ($newheight > $maxheight) {
            $resize_by_percent = ($maxheight * 100) / $newheight;

            $newwidth  = (int)\round(($newwidth * $resize_by_percent) / 100);
            $newheight = $maxheight;
        }

        return [
            'width'  => $newwidth,
            'height' => $newheight
        ];
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

        if (\file_exists($cachefile)) {
            return $cachefile;
        }

        if ($this->getAttribute('mime_type') == 'image/svg+xml') {
            File::copy($original, $cachefile);

            return $cachefile;
        }

        // quiqqer/quiqqer#782
        if ($this->getAttribute('mime_type') == 'image/gif' && $this->isAnimated()) {
            File::copy($original, $cachefile);

            return $cachefile;
        }

        // Cachefolder erstellen
        $this->getParent()->createCache();

        $effects = $this->getEffects();


        if ($width === false && $height === false && empty($effects)) {
            File::copy($original, $cachefile);

            return $cachefile;
        }


        // resize the proportions
        if ($width) {
            $width = QUI\Utils\Math::ceilUp($width, 100);
        }

        if ($height) {
            $height = QUI\Utils\Math::ceilUp($height, 100);
        }


        // create image
        $time = \ini_get('max_execution_time');
        \set_time_limit(1000);

        try {
            $Image = $Media->getImageManager()->make($original);
        } catch (\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
            File::copy($original, $cachefile);

            return $cachefile;
        }


        if ($width || $height) {
            if (!$width) {
                $width = null;
            }

            if (!$height) {
                $height = null;
            }

            $Image->resize($width, $height, function ($Constraint) {
                /* @var $Constraint \Intervention\Image\Constraint; */
                $Constraint->aspectRatio();
                $Constraint->upsize();
            });
        }

        // effects
        if (isset($effects['blur']) && \is_numeric($effects['blur'])) {
            $blur = (int)$effects['blur'];

            if ($blur > 0 && $blur <= 100) {
                $Image->blur($blur);
            }
        }

        if (isset($effects['brightness']) && \is_numeric($effects['brightness'])) {
            $brightness = (int)$effects['brightness'];

            if ($brightness !== 0 && $brightness >= -100
                && $brightness <= 100
            ) {
                $Image->brightness($brightness);
            }
        }

        if (isset($effects['contrast']) && \is_numeric($effects['contrast'])) {
            $contrast = (int)$effects['contrast'];

            if ($contrast !== 0 && $contrast >= -100 && $contrast <= 100) {
                $Image->contrast($contrast);
            }
        }

        if (isset($effects['greyscale']) && $effects['greyscale'] == 1) {
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
                        /* @var $Constraint \Intervention\Image\Constraint; */
                        $Constraint->aspectRatio();
                        $Constraint->upsize();
                    });
                }

                $Image->insert($WatermarkImage, $watermarkPosition);
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::addInfo($Exception->getMessage(), [
                'file'   => $this->getFullPath(),
                'fileId' => $this->getId(),
                'info'   => 'watermark creation'
            ]);
        }

        // create folders
        File::mkdir(\dirname($cachefile));

        // save cache image
        $Image->save($cachefile);

        // reset to the normal limit
        \set_time_limit($time);

        QUI::getEvents()->fireEvent('mediaCreateSizeCache', [$this, $Image]);

        return $cachefile;
    }

    /**
     * (non-PHPdoc)
     *
     * @throws QUI\Exception
     * @see QUI\Interfaces\Projects\Media\File::deleteCache()
     */
    public function deleteCache()
    {
        $Media = $this->Media;
        $cdir  = CMS_DIR.$Media->getCacheDir();
        $file  = $this->getAttribute('file');

        $cachefile = $cdir.$file;
        $cacheData = \pathinfo($cachefile);

        $fileData = File::getInfo($this->getFullPath());
        $files    = File::readDir($cacheData['dirname'], true);
        $filename = $fileData['filename'];

        foreach ($files as $file) {
            $len = \strlen($filename);

            // cache delete
            if (\substr($file, 0, $len + 2) == $filename.'__') {
                File::unlink($cacheData['dirname'].'/'.$file);
            }
        }

        File::unlink($cachefile);

        // delete admin cache, too
        $this->deleteAdminCache();
    }

    /**
     * Delete the admin cache
     */
    public function deleteAdminCache()
    {
        $Media   = $this->Media;
        $Project = $Media->getProject();

        $cacheDir  = VAR_DIR.'media/cache/admin/'.$Project->getName().'/'.$Project->getLang().'/';
        $cacheName = $this->getId().'__';

        $files = File::readDir($cacheDir);

        foreach ($files as $file) {
            if (\strpos($file, $cacheName) === 0) {
                \unlink($cacheDir.$file);
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
     */
    public function resize($newWidth = 0, $newHeight = 0)
    {
        $dir      = CMS_DIR.$this->Media->getPath();
        $original = $dir.$this->getAttribute('file');

        if ($this->getAttribute('mime_type') == 'image/svg+xml') {
            return $original;
        }

        try {
            // create image
            $Image = $this->getMedia()
                ->getImageManager()
                ->make($original);

            $Image->resize(
                $newWidth,
                $newHeight,
                function ($Constraint) {
                    /* @var $Constraint \Intervention\Image\Constraint; */
                    $Constraint->aspectRatio();
                    $Constraint->upsize();
                }
            );

            $Image->save($original);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        return $original;
    }

    /**
     * Is the image an animated image?
     * Thanks to https://stackoverflow.com/a/415942
     *
     * @return bool
     */
    public function isAnimated()
    {
        $filename = $this->getFullPath();

        if (!($fh = @\fopen($filename, 'rb'))) {
            return false;
        }

        $count = 0;
        //an animated gif contains multiple "frames", with each frame having a
        //header made up of:
        // * a static 4-byte sequence (\x00\x21\xF9\x04)
        // * 4 variable bytes
        // * a static 2-byte sequence (\x00\x2C)

        // We read through the file til we reach the end of the file, or we've found
        // at least 2 frame headers
        while (!\feof($fh) && $count < 2) {
            $chunk = \fread($fh, 1024 * 100); //read 100kb at a time
            $count += \preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
        }

        \fclose($fh);

        return $count > 1;
    }

    /**
     * Return the Watermark image file
     *
     * @return Image|boolean
     */
    public function getWatermark()
    {
        // own watermark?
        $imageEffects = $this->getEffects();

        if (\is_array($imageEffects) && !isset($imageEffects['watermark'])) {
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
     *
     * @throws QUI\Exception
     */
    public function generateMD5()
    {
        if (!\file_exists($this->getFullPath())) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/system', 'exception.file.not.found', [
                    'file' => $this->getAttribute('file')
                ]),
                ErrorCodes::FILE_NOT_FOUND
            );
        }

        $md5 = \md5_file($this->getFullPath());

        $this->setAttribute('md5hash', $md5);

        QUI::getDataBase()->update(
            $this->Media->getTable(),
            ['md5hash' => $md5],
            ['id' => $this->getId()]
        );
    }

    /**
     * Generate the SHA1 file hash and set it to the Database and to the Object
     *
     * @throws QUI\Exception
     */
    public function generateSHA1()
    {
        if (!\file_exists($this->getFullPath())) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/system', 'exception.file.not.found', [
                    'file' => $this->getAttribute('file')
                ]),
                ErrorCodes::FILE_NOT_FOUND
            );
        }

        $sha1 = \sha1_file($this->getFullPath());

        $this->setAttribute('sha1hash', $sha1);

        QUI::getDataBase()->update(
            $this->Media->getTable(),
            ['sha1hash' => $sha1],
            ['id' => $this->getId()]
        );
    }
}
