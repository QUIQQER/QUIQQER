<?php

/**
 * This file contains the \QUI\Projects\Media\Image
 */

namespace QUI\Projects\Media;

use Exception;
use QUI;
use QUI\Projects\Media;
use QUI\Projects\Media\Utils as MediaUtils;
use QUI\Utils\StringHelper;
use QUI\Utils\System\File;
use QUI\Utils\System\File as FileUtils;

use function array_map;
use function date;
use function dirname;
use function end;
use function explode;
use function fclose;
use function feof;
use function file_exists;
use function file_put_contents;
use function fread;
use function implode;
use function ini_get;
use function is_array;
use function is_numeric;
use function mb_strlen;
use function mb_substr;
use function md5_file;
use function pathinfo;
use function preg_match;
use function preg_match_all;
use function round;
use function set_time_limit;
use function sha1_file;
use function str_replace;
use function strlen;
use function strpos;
use function substr;
use function unlink;

/**
 * A media image
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Image extends Item implements QUI\Interfaces\Projects\Media\File
{
    /**
     * Max image width & width for image cache creation
     *
     * @var int
     */
    protected $IMAGE_MAX_SIZE = 4000;

    /**
     * Image constructor.
     *
     * @param $params
     * @param Media $Media
     */
    public function __construct($params, Media $Media)
    {
        parent::__construct($params, $Media);

        // read config
        $maxUploadImageSize = $this->getProject()->getConfig('media_maxUploadSize');
        $maxImageCacheSize  = $this->getProject()->getConfig('media_maxImageCacheSize');

        if (!empty($maxUploadImageSize)) {
            $this->IMAGE_MAX_SIZE = (int)$maxUploadImageSize;
        }

        if (!empty($maxImageCacheSize)) {
            $this->IMAGE_MAX_SIZE = (int)$maxImageCacheSize;
        }

        if (empty($this->IMAGE_MAX_SIZE)) {
            $this->IMAGE_MAX_SIZE = 4000;
        }
    }

    /**
     * Return the real with of the image
     *
     * @return integer|false
     * @throws QUI\Exception
     */
    public function getWidth()
    {
        if ($this->getAttribute('image_width')) {
            return (int)$this->getAttribute('image_width');
        }

        $data = File::getInfo($this->getFullPath(), [
            'imagesize' => true
        ]);

        if (isset($data['width'])) {
            return (int)$data['width'];
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
            return (int)$this->getAttribute('image_height');
        }

        $data = File::getInfo($this->getFullPath(), [
            'imagesize' => true
        ]);

        if (isset($data['height'])) {
            return (int)$data['height'];
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
        if (Media::$globalDisableMediaCacheCreation) {
            return false;
        }

        return $this->createSizeCache();
    }

    /**
     * Return the image path
     *
     * @param string|boolean $maxWidth - (optional)
     * @param string|boolean $maxHeight - (optional)
     *
     * @return string
     *
     * @throws QUI\Exception
     */
    public function getSizeCachePath($maxWidth = false, $maxHeight = false)
    {
        $Media = $this->Media;
        /* @var $Media QUI\Projects\Media */
        $cacheDir = CMS_DIR . $Media->getCacheDir();
        $file     = $this->getAttribute('file');


        if ($this->hasPermission('quiqqer.projects.media.view') &&
            $this->hasPermission('quiqqer.projects.media.view', QUI::getUsers()->getNobody()) === false) {
            $cacheDir = VAR_DIR . 'media/cache/permissions/' . $this->getProject()->getAttribute('name') . '/';
        }


        if ($this->getAttribute('mime_type') == 'image/svg+xml') {
            return $cacheDir . $file;
        }

        if (!$maxWidth && !$maxHeight) {
            return $cacheDir . $file;
        }


        if ($maxWidth > $this->IMAGE_MAX_SIZE) {
            $maxWidth = $this->IMAGE_MAX_SIZE;
        }

        if ($maxHeight > $this->IMAGE_MAX_SIZE) {
            $maxHeight = $this->IMAGE_MAX_SIZE;
        }

        $extra  = '';
        $params = $this->getResizeSize($maxWidth, $maxHeight);

        if ($params['height'] > $params['width']) {
            if (!($params['height'] % 8 === 0)) {
                $tempParams = $this->getResizeSize(
                    false,
                    QUI\Utils\Math::ceilUp($params['height'], 100)
                );
            } else {
                $tempParams = $this->getResizeSize(false, $params['height']);
            }
        } else {
            if (!($params['width'] % 8 === 0)) {
                $tempParams = $this->getResizeSize(
                    QUI\Utils\Math::ceilUp($params['width'], 100)
                );
            } else {
                $tempParams = $this->getResizeSize($params['width']);
            }
        }

        $height = $tempParams['height'];
        $width  = $tempParams['width'];

        if ($this->getAttribute('reflection')) {
            $extra = '_reflection';
        }


        if ($width || $height) {
            $part      = explode('.', $file);
            $cacheFile = $cacheDir . $part[0] . '__' . $width . 'x' . $height . $extra . '.' .
                StringHelper::toLower(end($part));

            if (empty($height)) {
                $cacheFile = $cacheDir . $part[0] . '__' . $width . $extra . '.' .
                    StringHelper::toLower(end($part));
            }

            if ($this->getAttribute('reflection')) {
                $cacheFile = $cacheDir . $part[0] . '__' . $width . 'x' . $height . $extra . '.png';

                if (empty($height)) {
                    $cacheFile = $cacheDir . $part[0] . '__' . $width . $extra . '.png';
                }
            }
        } else {
            $cacheFile = $cacheDir . $file;
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
        $cacheUrl  = str_replace(CMS_DIR, URL_DIR, $cachePath);

        if ($this->hasViewPermissionSet()) {
            $cacheUrl = URL_DIR . $this->getUrl();
        }

        if (!preg_match('/[^a-zA-Z0-9_\-.\/]/i', $cacheUrl)) {
            return $cacheUrl;
        }

        // thanks to http://php.net/manual/de/function.rawurlencode.php#100313
        // thanks to http://php.net/manual/de/function.rawurlencode.php#63751
        $encoded = implode(
            "/",
            array_map(function ($part) {
                $encoded = '';
                $length  = mb_strlen($part);

                for ($i = 0; $i < $length; $i++) {
                    $str = mb_substr($part, $i, 1);

                    if (!preg_match('/[^a-zA-Z0-9_\-.]/i', $str)) {
                        $encoded .= $str;
                        continue;
                    }

                    $encoded .= '%' . wordwrap(bin2hex($str), 2, '%', true);
                }

                return $encoded;
            }, explode("/", $cacheUrl))
        );

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

        return str_replace(CMS_DIR, URL_DIR, $cacheUrl);
    }

    /**
     * Creates a cache file and takes into account the maximum sizes
     *
     * @param integer|boolean $maxWidth
     * @param integer|boolean $maxHeight
     *
     * @return string - Path to the file
     *
     * @throws QUI\Exception
     */
    public function createResizeCache($maxWidth = false, $maxHeight = false)
    {
        $params = $this->getResizeSize($maxWidth, $maxHeight);

        return $this->createSizeCache(
            $params['width'],
            $params['height']
        );
    }

    /**
     * Return the Image specific max resize params
     *
     * @param boolean|integer $maxWidth - (optional)
     * @param boolean|integer $maxHeight - (optional)
     *
     * @return array - array('width' => 100, 'height' => 100)
     *
     * @throws QUI\Exception
     */
    public function getResizeSize($maxWidth = false, $maxHeight = false)
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

        $newWidth  = $width;
        $newHeight = $height;

        if (!$maxWidth) {
            $maxWidth = $width;
        }

        if (!$maxHeight) {
            $maxHeight = $height;
        }

        // max höhe breite auf 1200
        if ($maxWidth > $maxConfigSize && $maxConfigSize) {
            $maxWidth = $maxConfigSize;
        }

        if ($maxHeight > $maxConfigSize && $maxConfigSize) {
            $maxHeight = $maxConfigSize;
        }

        // Breite
        if ($newWidth > $maxWidth) {
            $resize_by_percent = ($maxWidth * 100) / $newWidth;

            $newHeight = (int)round(($newHeight * $resize_by_percent) / 100);
            $newWidth  = $maxWidth;
        }

        // Höhe
        if ($newHeight > $maxHeight) {
            $resize_by_percent = ($maxHeight * 100) / $newHeight;

            $newWidth  = (int)round(($newWidth * $resize_by_percent) / 100);
            $newHeight = $maxHeight;
        }

        return [
            'width'  => $newWidth,
            'height' => $newHeight
        ];
    }

    /**
     * Create a cache file with the new width and height
     *
     * @param integer|boolean $width - (optional)
     * @param integer|boolean $height - (optional)
     *
     * @return string - URL to the cache file
     *
     * @throws QUI\Permissions\Exception
     * @throws QUI\Exception
     */
    public function createSizeCache($width = false, $height = false)
    {
        if (!$this->getAttribute('active')) {
            return false;
        }

        $this->checkPermission('quiqqer.projects.media.view');


        if ($width > $this->IMAGE_MAX_SIZE) {
            $width = $this->IMAGE_MAX_SIZE;
        }

        if ($height > $this->IMAGE_MAX_SIZE) {
            $height = $this->IMAGE_MAX_SIZE;
        }

        $Media     = $this->Media;
        $original  = $this->getFullPath();
        $cacheFile = $this->getSizeCachePath($width, $height);

        if (file_exists($cacheFile)) {
            return $cacheFile;
        }

        // create cache folder
        File::mkdir(dirname($cacheFile));

        if ($this->getAttribute('mime_type') == 'image/svg+xml') {
            File::copy($original, $cacheFile);

            return $cacheFile;
        }

        // quiqqer/quiqqer#782
        if ($this->getAttribute('mime_type') == 'image/gif' && $this->isAnimated()) {
            File::copy($original, $cacheFile);

            return $cacheFile;
        }

        $effects = $this->getEffects();

        if ($width === false && $height === false && empty($effects)) {
            File::copy($original, $cacheFile);

            return $cacheFile;
        }

        // resize the proportions
        if ($width && !($width % 8 === 0)) {
            $width = QUI\Utils\Math::ceilUp($width, 100);
        }

        if ($height && !($width % 8 === 0)) {
            $height = QUI\Utils\Math::ceilUp($height, 100);
        }


        // create image
        $time = ini_get('max_execution_time');
        set_time_limit(1000);

        try {
            $Image = $Media->getImageManager()->make($original);
        } catch (Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
            File::copy($original, $cacheFile);

            return $cacheFile;
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
        if (isset($effects['blur']) && is_numeric($effects['blur'])) {
            $blur = (int)$effects['blur'];

            if ($blur > 0 && $blur <= 100) {
                $Image->blur($blur);
            }
        }

        if (isset($effects['brightness']) && is_numeric($effects['brightness'])) {
            $brightness = (int)$effects['brightness'];

            if ($brightness !== 0 && $brightness >= -100
                && $brightness <= 100
            ) {
                $Image->brightness($brightness);
            }
        }

        if (isset($effects['contrast']) && is_numeric($effects['contrast'])) {
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
        } catch (Exception $Exception) {
            QUI\System\Log::addInfo($Exception->getMessage(), [
                'file'   => $this->getFullPath(),
                'fileId' => $this->getId(),
                'info'   => 'watermark creation'
            ]);
        }

        // create folders
        File::mkdir(dirname($cacheFile));

        // save cache image
        $Image->save($cacheFile);

        // reset to the normal limit
        set_time_limit($time);

        QUI::getEvents()->fireEvent('mediaCreateSizeCache', [$this, $Image]);

        return $cacheFile;
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
        $cdir  = CMS_DIR . $Media->getCacheDir();
        $file  = $this->getAttribute('file');

        $cachefile = $cdir . $file;
        $cacheData = pathinfo($cachefile);

        $fileData = File::getInfo($this->getFullPath());
        $files    = File::readDir($cacheData['dirname'], true);
        $filename = $fileData['filename'];

        foreach ($files as $file) {
            $len = strlen($filename);

            // cache delete
            if (substr($file, 0, $len + 2) == $filename . '__') {
                File::unlink($cacheData['dirname'] . '/' . $file);
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

        $cacheDir  = VAR_DIR . 'media/cache/admin/' . $Project->getName() . '/' . $Project->getLang() . '/';
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
     * @throws QUI\Permissions\Exception
     */
    public function resize($newWidth = 0, $newHeight = 0)
    {
        $dir      = CMS_DIR . $this->Media->getPath();
        $original = $dir . $this->getAttribute('file');

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
        } catch (Exception $Exception) {
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
        while (!feof($fh) && $count < 2) {
            $chunk = fread($fh, 1024 * 100); //read 100kb at a time
            $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
        }

        fclose($fh);

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
        if (!file_exists($this->getFullPath())) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.file.not.found', [
                    'file' => $this->getAttribute('file')
                ]),
                ErrorCodes::FILE_NOT_FOUND
            );
        }

        $md5 = md5_file($this->getFullPath());

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
        if (!file_exists($this->getFullPath())) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.file.not.found', [
                    'file' => $this->getAttribute('file')
                ]),
                ErrorCodes::FILE_NOT_FOUND
            );
        }

        $sha1 = sha1_file($this->getFullPath());

        $this->setAttribute('sha1hash', $sha1);

        QUI::getDataBase()->update(
            $this->Media->getTable(),
            ['sha1hash' => $sha1],
            ['id' => $this->getId()]
        );
    }

    /**
     * @return void
     * @throws \QUI\Exception
     */
    public function updateExternalImage()
    {
        $SessionUser = QUI::getUserBySession();
        $external    = $this->getAttribute('external');

        if (empty($external)) {
            return;
        }

        try {
            $file     = QUI\Utils\Request\Url::get($external);
            $original = $this->getFullPath();

            file_put_contents($original, $file);

            // update image dimensions
            $fileInfo    = FileUtils::getInfo($original);
            $imageWidth  = null;
            $imageHeight = null;

            if (isset($fileInfo['width']) && $fileInfo['width']) {
                $imageWidth = (int)$fileInfo['width'];
            }

            if (isset($fileInfo['height']) && $fileInfo['height']) {
                $imageHeight = (int)$fileInfo['height'];
            }

            QUI::getDataBase()->update($this->Media->getTable(), [
                'e_date'       => date('Y-m-d h:i:s'),
                'e_user'       => $SessionUser->getId(),
                'mime_type'    => $fileInfo['mime_type'],
                'image_width'  => $imageWidth,
                'image_height' => $imageHeight,
                'type'         => MediaUtils::getMediaTypeByMimeType($fileInfo['mime_type'])
            ], [
                'id' => $this->getId()
            ]);

            $this->setAttribute('mime_type', $fileInfo['mime_type']);
            $this->setAttribute('image_width', $imageWidth);
            $this->setAttribute('image_height', $imageHeight);
            $this->deleteCache();
        } catch (QUI\Exception $Exception) {
            $this->deactivate();
        }
    }
}
