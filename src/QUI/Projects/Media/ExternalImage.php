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
class ExternalImage extends QUI\QDOM implements QUI\Interfaces\Projects\Media\File
{
    public function __construct(protected string $image)
    {
    }

    /**
     * return all parent ids from the file
     *
     * @throws Exception
     */
    public function getParentIds(): array
    {
        return [$this->getParentId()];
    }

    /**
     * Return the parent id
     *
     * @throws Exception
     */
    public function getParentId(): int
    {
        return $this->getParent()->getId();
    }

    /**
     * Return the file media ID
     */
    public function getId(): int
    {
        return -1;
    }

    /**
     * Return the Parent of the file
     *
     * @throws Exception
     */
    public function getParent(): Item
    {
        return $this->getMedia()->firstChild();
    }

    /**
     * Return the Media of the file
     */
    public function getMedia(): Media
    {
        return $this->getProject()->getMedia();
    }

    /**
     * Return the Project of the file
     *
     * @throws Exception
     */
    public function getProject(): Project
    {
        return QUI::getRewrite()->getProject();
    }

    /**
     * Return the path from the file
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
     */
    public function getUrl(bool $rewritten = false): string
    {
        return $this->image;
    }

    public function getFullPath(): string
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
    public function createCache(): bool | string
    {
        if (Media::$globalDisableMediaCacheCreation) {
            return false;
        }

        return $this->createSizeCache();
    }

    /**
     * Create a cache file with the new width and height
     *
     * @param boolean|integer $width - (optional)
     * @param boolean|integer $height - (optional)
     *
     * @return string - URL to the cache file
     */
    public function createSizeCache(bool | int $width = false, bool | int $height = false): string
    {
        return $this->image;
    }

    /**
     * Return the image path
     */
    public function getSizeCachePath(bool $maxWidth = false, bool $maxHeight = false): string
    {
        return $this->image;
    }

    /**
     * Return the image url
     */
    public function getSizeCacheUrl(bool $maxWidth = false, bool $maxHeight = false): string
    {
        return $this->image;
    }

    /**
     * Creates a cache file and takes into account the maximum sizes
     * return the media url
     *
     * @return string - Path to the file
     */
    public function createSizeCacheUrl(bool $maxWidth = false, bool $maxHeight = false): string
    {
        return $this->image;
    }

    /**
     * Creates a cache file and takes into account the maximum sizes
     *
     * @return string - Path to the file
     */
    public function createResizeCache(bool $maxWidth = false, bool $maxHeight = false): string
    {
        return $this->image;
    }

    /**
     * Return the Image specific max resize params
     *
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
     * @return string - Path to the new Image
     */
    public function resize(int $newWidth = 0, int $newHeight = 0): string
    {
        return $this->image;
    }

    /**
     * Return the Watermark image file
     */
    public function getWatermark(): bool
    {
        return false;
    }

    /**
     * Return the Watermark image file
     */
    public function getWatermarkPosition(): bool
    {
        return false;
    }

    public function getWatermarkRatio(): bool
    {
        return false;
    }


    //region placeholder methods

    /**
     * Placeholder - do nothing
     */
    public function activate(null | QUI\Interfaces\Users\User $PermissionUser = null)
    {
        // do nothing
    }

    /**
     * placeholder - do nothing
     */
    public function deactivate(null | QUI\Interfaces\Users\User $PermissionUser = null): void
    {
        // do nothing
    }

    /**
     * placeholder - do nothing
     */
    public function delete(null | QUI\Interfaces\Users\User $PermissionUser = null): void
    {
        // do nothing
    }

    public function destroy(null | QUI\Interfaces\Users\User $PermissionUser = null): void
    {
        // do nothing
    }

    /**
     * placeholder - do nothing
     */
    public function save(): void
    {
        // do nothing
    }

    /**
     * placeholder - do nothing
     *
     * @param QUI\Interfaces\Users\User|null $PermissionUser
     */
    public function rename(string $newName, null | QUI\Interfaces\Users\User $PermissionUser = null)
    {
        // do nothing
    }

    /**
     * placeholder - do nothing
     */
    public function deleteCache(): void
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
     * @throws Exception
     */
    public function moveTo(QUI\Projects\Media\Folder $Folder): void
    {
        throw new QUI\Exception('External images can not be moved');
    }

    /**
     * placeholder - do nothing
     * @throws Exception
     */
    public function copyTo(QUI\Projects\Media\Folder $Folder): QUI\Interfaces\Projects\Media\File
    {
        throw new QUI\Exception('External images can not be copied');
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

    public function isDeleted(): bool
    {
        return false;
    }

    public function isHidden(): bool
    {
        return false;
    }

    public function checkPermission(string $permission, null | QUI\Interfaces\Users\User $User = null): void
    {
    }
}
