<?php

/**
 * This file contains \QUI\Interfaces\Projects\Media\File
 */

namespace QUI\Interfaces\Projects\Media;

use QUI;
use QUI\Exception;
use QUI\Interfaces\Users\User;
use QUI\Projects\Media;
use QUI\Projects\Media\Folder;
use QUI\Projects\Media\Item;
use QUI\Projects\Project;

/**
 * The media file interface
 *
 * it shows the main methods of a media file (file, image, folder)
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
interface File
{
    public function getId(): int;

    public function getParent(): Item;

    public function getParentId(): int;

    public function getParentIds(): array;

    public function getPath(): string;

    /**
     * Return what type is the file
     *
     * @return string - \QUI\Projects\Media\Image | \QUI\Projects\Media\Folder | \QUI\Projects\Media\File
     */
    public function getType(): string;

    /**
     * Return the URL of the File, relative to the host
     */
    public function getUrl(): string;

    /**
     * @param QUI\Interfaces\Users\User|null $PermissionUser
     *
     * @throws Exception
     */
    public function activate(QUI\Interfaces\Users\User $PermissionUser = null);

    public function isActive(): bool;

    /**
     * Deactivate the file
     *
     * @param QUI\Interfaces\Users\User|null $PermissionUser
     * @throws Exception
     */
    public function deactivate(QUI\Interfaces\Users\User $PermissionUser = null);

    /**
     * Delete the file, file is in trash
     *
     * @param QUI\Interfaces\Users\User|null $PermissionUser
     * @throws Exception
     */
    public function delete(QUI\Interfaces\Users\User $PermissionUser = null);

    /**
     * Save the file with all its attributes to the Database
     *
     * @throws Exception
     */
    public function save();

    /**
     * @throws Exception
     */
    public function rename(string $newName, QUI\Interfaces\Users\User $PermissionUser = null);

    /**
     * create the file cache
     *
     * @throws Exception
     */
    public function createCache();

    /**
     * delete the file cache
     *
     * @throws Exception
     */
    public function deleteCache();

    public function moveTo(Folder $Folder);

    public function copyTo(Folder $Folder);

    public function getMedia(): Media;

    public function getProject(): Project;

    public function checkPermission(string $permission, QUI\Interfaces\Users\User $User = null): void;
}
