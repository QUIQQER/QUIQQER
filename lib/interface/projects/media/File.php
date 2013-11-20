<?php

/**
 * This file contains Interface_Projects_Media_File
 */

/**
 * The media file interface
 *
 * it shows the main methods of a media file (file, image, folder)
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.interface.projects.media
 */

interface Interface_Projects_Media_File
{
    /**
     * Return the file media ID
     * @return Integer
     */
    public function getId();

    /**
     * Return the Parent of the file
     * @return Projects_Media_Item
     */
    public function getParent();

    /**
     * Return the parent id
     * @return Integer
     */
    public function getParentId();

    /**
     * return all parent ids from the file
     * @return array
     */
    public function getParentIds();

    /**
     * Return the path from the file
     * @return String
     */
    public function getPath();

    /**
     * Return what type is the file
     * @return String Projects_Media_Image|Projects_Media_Folder|Projects_Media_File
     */
    public function getType();

    /**
     * Return the URL of the File, relative to the host
     * @return String
     */
    public function getUrl();

    /**
     * Activate the file
     * @throws \QUI\Exception
     */
    public function activate();

    /**
     * Deactivate the file
     * @throws \QUI\Exception
     */
    public function deactivate();

    /**
     * Delete the file, file is in trash
     * @throws \QUI\Exception
     */
    public function delete();

    /**
     * Save the file with all its attributes to the Database
     * @throws \QUI\Exception
     */
    public function save();

    /**
     * Renames the file
     *
     * @param String $newname - new name of the file
     * @throws \QUI\Exception
     */
    public function rename($newname);

    /**
     * create the file cache
     * @throws \QUI\Exception
     */
    public function createCache();

    /**
     * delete the file cache
     * @throws \QUI\Exception
     */
    public function deleteCache();

    /**
     * move the item to another folder
     * @param Projects_Media_Folder $Folder
     */
    public function moveTo(Projects_Media_Folder $Folder);

    /**
     * copy the item to another folder
     * @param Projects_Media_Folder $Folder
     */
    public function copyTo(Projects_Media_Folder $Folder);
}


?>