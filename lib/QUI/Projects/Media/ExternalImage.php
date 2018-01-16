<?php

/**
 * This file contains QUI\Projects\Media\ExternalImage
 */

namespace QUI\Projects\Media;

use QUI;

/**
 * Class ExternalImage
 *
 * @package QUI\Projects\Media
 */
class ExternalImage implements QUI\Interfaces\Projects\Media\File
{
    /**
     * @var string
     */
    protected $image = '';

    /**
     * ExternalImage constructor.
     *
     * @param $image
     */
    public function __construct($image)
    {
        $this->image = $image;
    }

    /**
     * Return the file media ID
     *
     * @return integer
     */
    public function getId()
    {
        return false;
    }

    /**
     * Return the Media of the file
     *
     * @return \QUI\Projects\Media
     */
    public function getMedia()
    {
        return $this->getProject()->getMedia();
    }

    /**
     * Return the Project of the file
     *
     * @return \QUI\Projects\Project
     */
    public function getProject()
    {
        return QUI::getRewrite()->getProject();
    }

    /**
     * Return the Parent of the file
     *
     * @return \QUI\Projects\Media\Item
     */
    public function getParent()
    {
        return $this->getMedia()->firstChild();
    }

    /**
     * Return the parent id
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->getParent()->getId();
    }

    /**
     * return all parent ids from the file
     *
     * @return array
     */
    public function getParentIds()
    {
        return [$this->getParentId()];
    }

    /**
     * Return the path from the file
     *
     * @return string
     */
    public function getPath()
    {
        return '';
    }

    /**
     * Return what type is the file
     *
     * @return string - \QUI\Projects\Media\Image | \QUI\Projects\Media\Folder | \QUI\Projects\Media\File
     */
    public function getType()
    {
        return self::class;
    }

    /**
     * Return the URL of the File, relative to the host
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->image;
    }

    /**
     * Return the real with of the image
     *
     * @return integer | false
     */
    public function getWidth()
    {
        return false;
    }

    /**
     * Return the real height of the image
     *
     * @return integer | false
     */
    public function getHeight()
    {
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
        return $this->image;
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
        return $this->image;
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
        return $this->image;
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
        return $this->image;
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
        return array(
            'width'  => false,
            'height' => false
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
        return $this->image;
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
        return $this->image;
    }

    /**
     * Return the Watermark image file
     *
     * @return boolean
     */
    public function getWatermark()
    {
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
        return false;
    }

    /**
     * @return array|bool|false|string
     */
    public function getWatermarkRatio()
    {
        return false;
    }


    //region placeholder methods

    /**
     * placeholder - do nothing
     */
    public function activate()
    {
        // do nothing
    }

    /**
     * placeholder - do nothing
     */
    public function deactivate()
    {
        // do nothing
    }

    /**
     * placeholder - do nothing
     */
    public function delete()
    {
        // do nothing
    }

    /**
     * placeholder - do nothing
     */
    public function save()
    {
        // do nothing
    }

    /**
     * placeholder - do nothing
     *
     * @param $newname
     */
    public function rename($newname)
    {
        // do nothing
    }

    /**
     * placeholder - do nothing
     */
    public function deleteCache()
    {
        // do nothing
    }

    /**
     * Delete the admin cache
     */
    public function deleteAdminCache()
    {

    }

    /**
     * placeholder - do nothing
     *
     * @param $Folder
     */
    public function moveTo(QUI\Projects\Media\Folder $Folder)
    {
        // do nothing
    }

    /**
     * placeholder - do nothing
     *
     * @param $Folder
     */
    public function copyTo(QUI\Projects\Media\Folder $Folder)
    {
        // do nothing
    }

    /**
     * Generate the MD5 file hash and set it to the Database and to the Object
     */
    public function generateMD5()
    {
        // do nothing
    }

    /**
     * Generate the SHA1 file hash and set it to the Database and to the Object
     */
    public function generateSHA1()
    {
        // do nothing
    }

    //ednregion
}
