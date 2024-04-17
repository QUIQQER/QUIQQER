<?php

/**
 * This file contains QUI\Projects\Media\ExternalImage
 */

namespace QUI\Projects\Media;

use QUI;
use QUI\Exception;
use QUI\Projects\Media;
use QUI\Projects\Project;

/**
 * Class ExternalImage
 */
class ExternalImage implements QUI\Interfaces\Projects\Media\File
{
    public function __construct(protected string $image)
    {
    }

    /**
     * return all parent ids from the file
     *
     * @return array
     * @throws Exception
     */
    public function getParentIds(): array
    {
        return [$this->getParentId()];
    }

    /**
     * Return the parent id
     *
     * @return integer
     * @throws Exception
     */
    public function getParentId(): int
    {
        return $this->getParent()->getId();
    }

    /**
     * Return the file media ID
     *
     * @return integer
     */
    public function getId(): int
    {
        return -1;
    }

    /**
     * Return the Parent of the file
     *
     * @return Item
     * @throws Exception
     */
    public function getParent(): Item
    {
        return $this->getMedia()->firstChild();
    }

    /**
     * Return the Media of the file
     *
     * @return Media
     */
    public function getMedia(): Media
    {
        return $this->getProject()->getMedia();
    }

    /**
     * Return the Project of the file
     *
     * @return Project
     * @throws Exception
     */
    public function getProject(): Project
    {
        return QUI::getRewrite()->getProject();
    }

    /**
     * Return the path from the file
     *
     * @return string
     */
    public function getPath(): string
    {
        return '';
    }

    /**
     * Return what type is the file
     *
     * @return string - \QUI\Projects\Media\Image | \QUI\Projects\Media\Folder | \QUI\Projects\Media\File
     */
    public function getType(): string
    {
        return self::class;
    }

    /**
     * Return the URL of the File, relative to the host
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->image;
    }

    /**
     * Return the real with of the image
     *
     * @return false
     */
    public function getWidth(): bool
    {
        return false;
    }

    /**
     * Return the real height of the image
     *
     * @return false
     */
    public function getHeight(): bool
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
        if (Media::$globalDisableMediaCacheCreation) {
            return false;
        }

        return $this->createSizeCache();
    }

    /**
     * Create a cache file with the new width and height
     *
     * @param integer|boolean $width - (optional)
     * @param integer|boolean $height - (optional)
     *
     * @return string - URL to the cache file
     */
    public function createSizeCache($width = false, $height = false): string
    {
        return $this->image;
    }

    /**
     * Return the image path
     *
     * @param bool $maxWidth
     * @param bool $maxHeight
     * @return string
     */
    public function getSizeCachePath(bool $maxWidth = false, bool $maxHeight = false): string
    {
        return $this->image;
    }

    /**
     * Return the image url
     *
     * @param bool $maxWidth
     * @param bool $maxHeight
     * @return string
     */
    public function getSizeCacheUrl(bool $maxWidth = false, bool $maxHeight = false): string
    {
        return $this->image;
    }

    /**
     * Creates a cache file and takes into account the maximum sizes
     * return the media url
     *
     * @param bool $maxWidth
     * @param bool $maxHeight
     * @return string - Path to the file
     */
    public function createSizeCacheUrl(bool $maxWidth = false, bool $maxHeight = false): string
    {
        return $this->image;
    }

    /**
     * Creates a cache file and takes into account the maximum sizes
     *
     * @param bool $maxWidth
     * @param bool $maxHeight
     * @return string - Path to the file
     */
    public function createResizeCache(bool $maxWidth = false, bool $maxHeight = false): string
    {
        return $this->image;
    }

    /**
     * Return the Image specific max resize params
     *
     * @param bool $maxWidth
     * @param bool $maxHeight
     * @return array - array('width' => 100, 'height' => 100)
     */
    public function getResizeSize(bool $maxWidth = false, bool $maxHeight = false): array
    {
        return [
            'width' => false,
            'height' => false
        ];
    }

    /**
     * Resize the image and aspect the ratio
     *
     * @param integer $newWidth
     * @param integer $newHeight
     *
     * @return string - Path to the new Image
     */
    public function resize(int $newWidth = 0, int $newHeight = 0): string
    {
        return $this->image;
    }

    /**
     * Return the Watermark image file
     *
     * @return boolean
     */
    public function getWatermark(): bool
    {
        return false;
    }

    /**
     * Return the Watermark image file
     *
     * @return boolean
     */
    public function getWatermarkPosition(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function getWatermarkRatio(): bool
    {
        return false;
    }


    //region placeholder methods

    /**
     * Placeholder - do nothing
     *
     * @param null $PermissionUser
     */
    public function activate($PermissionUser = null)
    {
        // do nothing
    }

    /**
     * placeholder - do nothing
     *
     * @param null $PermissionUser
     */
    public function deactivate($PermissionUser = null)
    {
        // do nothing
    }

    /**
     * placeholder - do nothing
     *
     * @param null $PermissionUser
     */
    public function delete($PermissionUser = null)
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
     * @param string $newName
     * @param QUI\Interfaces\Users\User|null $PermissionUser
     */
    public function rename(string $newName, QUI\Interfaces\Users\User $PermissionUser = null)
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
     * @param Folder $Folder
     */
    public function moveTo(QUI\Projects\Media\Folder $Folder)
    {
        // do nothing
    }

    /**
     * placeholder - do nothing
     *
     * @param Folder $Folder
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

    //endregion
    public function isActive(): bool
    {
        return true;
    }
}
