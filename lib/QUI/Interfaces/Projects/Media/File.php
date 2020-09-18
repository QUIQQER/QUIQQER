<?php

/**
 * This file contains \QUI\Interfaces\Projects\Media\File
 */

namespace QUI\Interfaces\Projects\Media;

use QUI;

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
    public function getId();

    /**
     * Return the Parent of the file
     *
     * @return \QUI\Projects\Media\Item
     */
    public function getParent();

    /**
     * Return the parent id
     *
     * @return integer
     */
    public function getParentId();

    /**
     * return all parent ids from the file
     *
     * @return array
     */
    public function getParentIds();

    /**
     * Return the path from the file
     *
     * @return string
     */
    public function getPath();

    /**
     * Return what type is the file
     *
     * @return string - \QUI\Projects\Media\Image | \QUI\Projects\Media\Folder | \QUI\Projects\Media\File
     */
    public function getType();

    /**
     * Return the URL of the File, relative to the host
     *
     * @return string
     */
    public function getUrl();

    /**
     * Activate the file
     *
     * @param QUI\Interfaces\Users\User|null $PermissionUser
     * @throws \QUI\Exception
     */
    public function activate($PermissionUser = null);

    /**
     * Deactivate the file
     *
     * @param QUI\Interfaces\Users\User|null $PermissionUser
     * @throws \QUI\Exception
     */
    public function deactivate($PermissionUser = null);

    /**
     * Delete the file, file is in trash
     *
     * @param QUI\Interfaces\Users\User|null $PermissionUser
     * @throws \QUI\Exception
     */
    public function delete($PermissionUser = null);

    /**
     * Save the file with all its attributes to the Database
     *
     * @throws \QUI\Exception
     */
    public function save();

    /**
     * Renames the file
     *
     * @param string $newname - new name of the file
     *
     * @throws \QUI\Exception
     */
    public function rename($newname);

    /**
     * create the file cache
     *
     * @throws \QUI\Exception
     */
    public function createCache();

    /**
     * delete the file cache
     *
     * @throws \QUI\Exception
     */
    public function deleteCache();

    /**
     * move the item to another folder
     *
     * @param \QUI\Projects\Media\Folder $Folder
     */
    public function moveTo(QUI\Projects\Media\Folder $Folder);

    /**
     * copy the item to another folder
     *
     * @param \QUI\Projects\Media\Folder $Folder
     */
    public function copyTo(QUI\Projects\Media\Folder $Folder);

    /**
     * Return the Media of the file
     *
     * @return \QUI\Projects\Media
     */
    public function getMedia();

    /**
     * Return the Project of the file
     *
     * @return \QUI\Projects\Project
     */
    public function getProject();
}
