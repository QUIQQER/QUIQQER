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
    /**
     * Return the file media ID
     *
     * @return integer
     */
    public function getId(): int;

    /**
     * Return the Parent of the file
     *
     * @return Item
     */
    public function getParent(): Item;

    /**
     * Return the parent id
     *
     * @return integer
     */
    public function getParentId(): int;

    /**
     * return all parent ids from the file
     *
     * @return array
     */
    public function getParentIds(): array;

    /**
     * Return the path from the file
     *
     * @return string
     */
    public function getPath(): string;

    /**
     * Return what type is the file
     *
     * @return string - \QUI\Projects\Media\Image | \QUI\Projects\Media\Folder | \QUI\Projects\Media\File
     */
    public function getType(): string;

    /**
     * Return the URL of the File, relative to the host
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Activate the file
     *
     * @param QUI\Interfaces\Users\User|null $PermissionUser
     * @throws Exception
     */
    public function activate(QUI\Interfaces\Users\User $PermissionUser = null);

    /**
     * @return bool
     */
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
     * Renames the file
     *
     * @param string $newName
     * @param User|null $PermissionUser
     *
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

    /**
     * move the item to another folder
     *
     * @param Folder $Folder
     */
    public function moveTo(Folder $Folder);

    /**
     * copy the item to another folder
     *
     * @param Folder $Folder
     */
    public function copyTo(Folder $Folder);

    /**
     * Return the Media of the file
     *
     * @return Media
     */
    public function getMedia(): Media;

    /**
     * Return the Project of the file
     *
     * @return Project
     */
    public function getProject(): Project;
}
