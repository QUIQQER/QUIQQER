<?php

/**
 * This file contains the \QUI\Projects\Media\Item
 */

namespace QUI\Projects\Media;

use QUI;
use QUI\Groups\Group;
use QUI\Permissions\Permission;
use QUI\Projects\Media;
use QUI\Users\User;
use QUI\Utils\System\File as QUIFile;

/**
 * A media item
 * the parent class of each media entry
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.projects.media
 * @licence For copyright and license information, please view the /README.md
 */
abstract class Item extends QUI\QDOM
{
    /**
     * internal image effect parameter
     *
     * @var bool|array
     */
    protected $effects = false;

    /**
     * internal media object
     *
     * @var QUI\Projects\Media
     */
    protected $Media = null;

    /**
     * internal parent id (use ->getParentId())
     *
     * @var integer
     */
    protected $parent_id = false;

    /**
     * Path to the real file
     *
     * @var string
     */
    protected $file;

    /**
     * @var array
     */
    protected $pathHistory = null;

    /**
     * @var array
     */
    protected $title = [];

    /**
     * @var array
     */
    protected $description = [];

    /**
     * @var array|mixed
     */
    protected $alt = [];

    /**
     * constructor
     *
     * @param array $params - item attributes
     * @param \QUI\Projects\Media $Media - Media of the file
     */
    public function __construct($params, Media $Media)
    {
        $params['id']       = (int)$params['id'];
        $params['hidden']   = (int)$params['hidden'];
        $params['active']   = (int)$params['active'];
        $params['priority'] = (int)$params['priority'];

        $this->Media = $Media;
        $this->setAttributes($params);

        $this->file = CMS_DIR.$this->Media->getPath().$this->getPath();

        // title, description
        $this->setAttribute('title', \json_encode($this->title));
        $this->setAttribute('short', \json_encode($this->description));
        $this->setAttribute('alt', \json_encode($this->alt));

        if (!\file_exists($this->file)) {
            QUI::getMessagesHandler()->addAttention(
                'File '.$this->file.' ('.$this->getId().') doesn\'t exist'
            );

            return;
        }

        $this->setAttribute('filesize', QUIFile::getFileSize($this->file));
        $this->setAttribute('url', $this->getUrl());

        $this->setAttribute(
            'cache_url',
            URL_DIR.$this->Media->getCacheDir().$this->getPath()
        );
    }

    //region General getter and is methods

    /**
     * Returns the id of the item
     *
     * @return integer
     */
    public function getId()
    {
        return (int)$this->getAttribute('id');
    }

    /**
     * Return the Media of the item
     *
     * @return QUI\Projects\Media
     */
    public function getMedia()
    {
        return $this->Media;
    }

    /**
     * Return the Project of the item
     */
    public function getProject()
    {
        return $this->getMedia()->getProject();
    }

    /**
     * Returns if the file is active or not
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->getAttribute('active') ? true : false;
    }

    /**
     * Returns if the file is deleted or not
     *
     * @return boolean
     */
    public function isDeleted()
    {
        return $this->getAttribute('deleted') ? true : false;
    }

    /**
     * overwritten get attribute
     * -> this method considers multilingual attributes
     *
     * @param string $name
     * @return mixed
     */
    public function getAttribute($name)
    {
        if ($name === 'title') {
            return \json_encode($this->title);
        }

        if ($name === 'short' || $name === 'description') {
            return \json_encode($this->description);
        }

        if ($name === 'alt') {
            return \json_encode($this->alt);
        }

        return parent::getAttribute($name);
    }

    /**
     * overwritten get attributes
     * -> this method considers multilingual attributes
     *
     * @return mixed
     */
    public function getAttributes()
    {
        $attributes = parent::getAttributes();

        $attributes['title'] = $this->getAttribute('title');
        $attributes['short'] = $this->getAttribute('short');
        $attributes['alt']   = $this->getAttribute('alt');

        return $attributes;
    }

    /**
     * Return the title
     *
     * @param null|QUI\Locale $Locale
     * @return string
     */
    public function getTitle($Locale = null)
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        $current = $Locale->getCurrent();

        if (isset($this->title[$current])) {
            if (empty($this->title[$current])) {
                return '';
            }

            return $this->title[$current];
        }

        \reset($this->title);
        $current = \current($this->title);

        if (empty($current)) {
            return '';
        }

        return $current;
    }

    /**
     * Return the short / description
     * alias for getDescription()
     *
     * @param null|QUI\Locale $Locale
     * @return string
     */
    public function getShort($Locale = null)
    {
        return $this->getDescription($Locale);
    }

    /**
     * Return the short / description
     *
     * @param null|QUI\Locale $Locale
     * @return mixed
     */
    public function getDescription($Locale = null)
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        $current = $Locale->getCurrent();

        if (isset($this->description[$current])) {
            if (empty($this->description[$current])) {
                return '';
            }

            return $this->description[$current];
        }

        \reset($this->description);
        $current = \current($this->description);

        if (empty($current)) {
            return '';
        }

        return $current;
    }

    /**
     * Return the alt text
     *
     * @param null|QUI\Locale $Locale
     * @return string
     */
    public function getAlt($Locale = null)
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        $current = $Locale->getCurrent();

        if (isset($this->alt[$current])) {
            if (empty($this->alt[$current])) {
                return '';
            }

            return $this->alt[$current];
        }

        \reset($this->alt);
        $result = \current($this->alt);

        if (empty($result)) {
            return '';
        }

        return $result;
    }

    //endregion

    // region API Methods - General important file operations

    /**
     * Activate the file
     * The file is now public
     *
     * @param QUI\Interfaces\Users\User|null $PermissionUser
     *
     * @throws QUI\Exception
     */
    public function activate($PermissionUser = null)
    {
        $this->checkPermission('quiqqer.projects.media.edit', $PermissionUser);

        try {
            // activate the parents, otherwise the file is not accessible
            $this->getParent()->activate($PermissionUser);
        } catch (QUI\Exception $Exception) {
            // has no parent
        }

        QUI::getDataBase()->update(
            $this->Media->getTable(),
            ['active' => 1],
            ['id' => $this->getId()]
        );

        $this->setAttribute('active', 1);


        if (\method_exists($this, 'deleteCache')) {
            try {
                $this->deleteCache();
            } catch (\Exception $Exception) {
                QUI\System\Log::addNotice($Exception->getMessage());
            }
        }

        if (\method_exists($this, 'createCache')) {
            try {
                $this->createCache();
            } catch (\Exception $Exception) {
                QUI\System\Log::addNotice($Exception->getMessage());
            }
        }

        QUI::getEvents()->fireEvent('mediaActivate', [$this]);
    }

    /**
     * Deactivate the file
     * the file is no longer public
     *
     * @param QUI\Interfaces\Users\User|null $PermissionUser
     *
     * @throws QUI\Exception
     */
    public function deactivate($PermissionUser = null)
    {
        $this->checkPermission('quiqqer.projects.media.edit', $PermissionUser);

        QUI::getDataBase()->update(
            $this->Media->getTable(),
            ['active' => 0],
            ['id' => $this->getId()]
        );

        $this->setAttribute('active', 0);

        if (\method_exists($this, 'deleteCache')) {
            $this->deleteCache();
        }

        QUI::getEvents()->fireEvent('mediaDeactivate', [$this]);
    }

    /**
     * Save the file to the database
     * The id attribute can not be overwritten
     *
     * @param QUI\Interfaces\Users\User|null $PermissionUser
     *
     * @throws QUI\Exception
     */
    public function save($PermissionUser = null)
    {
        // permission check
        if (Media::useMediaPermissions()) {
            if (\method_exists($this, 'deleteCache')) {
                $this->deleteCache();
            }
        }

        $this->checkPermission('quiqqer.projects.media.edit', $PermissionUser);


        // save logic
        QUI::getEvents()->fireEvent('mediaSaveBegin', [$this]);

        // Rename the file, if necessary
        $this->rename($this->getAttribute('name'));

        $image_effects = $this->getEffects();

        if (\is_string($image_effects) || \is_bool($image_effects)) {
            $image_effects = [];
        }

        switch ($this->getAttribute('order')) {
            case 'priority':
            case 'priority ASC':
            case 'priority DESC':
            case 'c_date':
            case 'c_date ASC':
            case 'c_date DESC':
            case 'name':
            case 'name ASC':
            case 'name DESC':
            case 'title':
            case 'title ASC':
            case 'title DESC':
            case 'id':
            case 'id ASC':
            case 'id DESC':
                $order = $this->getAttribute('order');
                break;

            default:
                $order = '';
        }

        // svg fix
        if ($this->getAttribute('mime_type') == 'text/html') {
            $content = \file_get_contents($this->getFullPath());

            if (\strpos($content, '<svg') !== false && \strpos($content, '</svg>')) {
                \file_put_contents(
                    $this->getFullPath(),
                    '<?xml version="1.0" encoding="UTF-8"?>'.
                    $content
                );

                $fileinfo = QUI\Utils\System\File::getInfo($this->getFullPath());

                QUI::getDataBase()->update(
                    $this->Media->getTable(),
                    ['mime_type' => $fileinfo['mime_type']],
                    ['id' => $this->getId()]
                );
            }
        }

        if (\method_exists($this, 'deleteCache')) {
            $this->deleteCache();
        }

        if (\method_exists($this, 'deleteAdminCache')) {
            $this->deleteAdminCache();
        }

        $fileinfo = QUI\Utils\System\File::getInfo($this->getFullPath());
        $type     = QUI\Projects\Media\Utils::getMediaTypeByMimeType($fileinfo['mime_type']);

        if (Utils::isFolder($this)) {
            $type = 'folder';
        }

        QUI::getDataBase()->update(
            $this->Media->getTable(),
            [
                'title'         => $this->saveMultilingualField($this->title),
                'alt'           => $this->saveMultilingualField($this->alt),
                'short'         => $this->saveMultilingualField($this->description),
                'order'         => $order,
                'priority'      => (int)$this->getAttribute('priority'),
                'image_effects' => \json_encode($image_effects),
                'type'          => $type,
                'pathHistory'   => \json_encode($this->getPathHistory()),
                'hidden'        => $this->isHidden() ? 1 : 0
            ],
            [
                'id' => $this->getId()
            ]
        );

        // @todo in eine queue setzen
        $Project = $this->getProject();

        if ($Project->getConfig('media_createCacheOnSave') && \method_exists($this, 'createCache')) {
            $this->createCache();
        }

        // build frontend cache
        $Media   = $this->getMedia();
        $Project = $Media->getProject();

        // id cache via filepath
        QUI\Cache\Manager::set(
            $Media->getCacheDir().'filePathIds/'.md5($this->getAttribute('file')),
            $this->getId()
        );

        QUI\Cache\Manager::set(
            'media/cache/'.$Project->getName().'/indexSrcCache/'.md5($this->getAttribute('file')),
            $this->getUrl()
        );

        QUI::getEvents()->fireEvent('mediaSave', [$this]);
    }

    /**
     * @param string|array $value
     * @return string
     */
    protected function saveMultilingualField($value)
    {
        if (\is_array($value)) {
            return \json_encode($value);
        }

        $value   = \json_decode($value, true);
        $current = QUI::getLocale()->getCurrent();

        if (!$value) {
            return \json_encode([
                $current => $value
            ]);
        }

        $result = [];

        foreach ($value as $key => $val) {
            if (QUI::getLocale()->existsLang($key)) {
                $result[$key] = $val;
            }
        }

        return \json_encode($result);
    }

    /**
     * Delete the file and move it to the trash
     *
     * @param QUI\Interfaces\Users\User|null $PermissionUser
     * @throws QUI\Exception
     */
    public function delete($PermissionUser = null)
    {
        $this->checkPermission('quiqqer.projects.media.del', $PermissionUser);


        if ($this->isDeleted()) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.media.already.deleted'),
                ErrorCodes::ALREADY_DELETED
            );
        }

        QUI::getEvents()->fireEvent('mediaDeleteBegin', [$this]);

        $Media = $this->Media;
        $First = $Media->firstChild();

        // Move file to the temp folder
        $original = $this->getFullPath();
        $notFound = false;

        $var_folder = VAR_DIR.'media/trash/'.$Media->getProject()->getName().'/';

        if (!\is_file($original)) {
            QUI::getMessagesHandler()->addAttention(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.delete.originalfile.notfound'
                )
            );

            $notFound = true;
        }

        if ($First->getFullPath() == $original) {
            throw new QUI\Exception(
                ['quiqqer/quiqqer', 'exception.delete.root.file'],
                ErrorCodes::ROOT_FOLDER_CANT_DELETED
            );
        }

        // first, delete the cache
        if (\method_exists($this, 'deleteCache')) {
            try {
                $this->deleteCache();
            } catch (\Exception $Exception) {
                QUI\System\Log::addWarning($Exception->getMessage());
            }
        }

        if (\method_exists($this, 'deleteAdminCache')) {
            try {
                $this->deleteAdminCache();
            } catch (\Exception $Exception) {
                QUI\System\Log::addWarning($Exception->getMessage());
            }
        }


        // second, move the file to the trash
        try {
            QUIFile::unlink($var_folder.$this->getId());
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addWarning($Exception->getMessage());
        }

        try {
            QUIFile::mkdir($var_folder);
            QUIFile::move($original, $var_folder.$this->getId());
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addWarning($Exception->getMessage());
        }

        // change db entries
        QUI::getDataBase()->update(
            $this->Media->getTable(),
            [
                'deleted' => 1,
                'active'  => 0,
                'file'    => ''
            ],
            [
                'id' => $this->getId()
            ]
        );

        QUI::getDataBase()->delete(
            $this->Media->getTable('relations'),
            ['child' => $this->getId()]
        );

        $this->parent_id = false;
        $this->setAttribute('deleted', 1);
        $this->setAttribute('active', 0);

        try {
            QUI::getEvents()->fireEvent('mediaDelete', [$this]);
        } catch (QUI\ExceptionStack $Exception) {
            QUI\System\Log::addWarning($Exception->getMessage());
        }

        if ($notFound) {
            $this->destroy();
        }
    }

    /**
     * Destroy the File complete from the DataBase and from the Filesystem
     *
     * @param QUI\Interfaces\Users\User|null $PermissionUser
     * @throws QUI\Exception
     */
    public function destroy($PermissionUser = null)
    {
        $this->checkPermission('quiqqer.projects.media.del', $PermissionUser);


        if ($this->isActive()) {
            throw new QUI\Exception(
                'Only inactive files can be destroyed',
                ErrorCodes::FILE_CANT_DESTROYED
            );
        }

        if (!$this->isDeleted()) {
            throw new QUI\Exception(
                'Only deleted files can be destroyed',
                ErrorCodes::FILE_CANT_DESTROYED
            );
        }

        $Media = $this->Media;

        // get the trash file and destroy it
        $var_folder = VAR_DIR.'media/trash/'.$Media->getProject()->getName().'/';
        $var_file   = $var_folder.$this->getId();

        try {
            QUIFile::unlink($var_file);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addWarning($Exception->getMessage());
        }

        QUI::getDataBase()->delete($this->Media->getTable(), [
            'id' => $this->getId()
        ]);

        QUI::getEvents()->fireEvent('mediaDestroy', [$this]);
    }

    /**
     * Rename the File
     *
     * @param string $newName - The new name what the file get
     * @param QUI\Interfaces\Users\User|null $PermissionUser
     *
     * @throws \QUI\Exception
     */
    public function rename($newName, $PermissionUser = null)
    {
        $this->checkPermission('quiqqer.projects.media.edit', $PermissionUser);


        $newName = \trim($newName, "_ \t\n\r\0\x0B"); // Trim the default characters and underscores

        $original  = $this->getFullPath();
        $extension = QUI\Utils\StringHelper::pathinfo($original, PATHINFO_EXTENSION);
        $Parent    = $this->getParent();

        $new_full_file = $Parent->getFullPath().$newName.'.'.$extension;
        $new_file      = $Parent->getPath().$newName.'.'.$extension;

        if ($new_full_file == $original) {
            return;
        }

        if (empty($newName)) {
            return;
        }

        // throws the \QUI\Exception
        $fileParts = \explode('/', $new_file);

        foreach ($fileParts as $filePart) {
            Utils::checkMediaName($filePart);
        }


        if ($Parent->childWithNameExists($newName)) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.media.file.with.same.name.exists', [
                    'name' => $newName
                ]),
                ErrorCodes::FILE_ALREADY_EXISTS
            );
        }

        if ($Parent->fileWithNameExists($newName.'.'.$extension)) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.media.file.with.same.name.exists', [
                    'name' => $newName
                ]),
                ErrorCodes::FILE_ALREADY_EXISTS
            );
        }


        if (\method_exists($this, 'deleteCache')) {
            $this->deleteCache();
        }

        if (\method_exists($this, 'deleteAdminCache')) {
            $this->deleteAdminCache();
        }


        $this->addToPathHistory($new_file);

        QUI::getDataBase()->update(
            $this->Media->getTable(),
            [
                'name'        => $newName,
                'file'        => $new_file,
                'pathHash'    => \md5($new_file),
                'pathHistory' => \json_encode($this->getPathHistory())
            ],
            [
                'id' => $this->getId()
            ]
        );

        $this->setAttribute('name', $newName);
        $this->setAttribute('file', $new_file);

        QUIFile::move($original, $new_full_file);

        if (\method_exists($this, 'createCache')) {
            $this->createCache();
        }

        QUI::getEvents()->fireEvent('mediaRename', [$this]);
    }

    /**
     * move the item to another folder
     *
     * @param \QUI\Projects\Media\Folder $Folder - the new folder of the file
     * @param QUI\Interfaces\Users\User|null $PermissionUser
     *
     * @throws QUI\Exception
     */
    public function moveTo(Folder $Folder, $PermissionUser = null)
    {
        $this->checkPermission('quiqqer.projects.media.edit', $PermissionUser);


        // check if a child with the same name exist
        if ($Folder->fileWithNameExists($this->getAttribute('name'))) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.media.file.with.same.name.exists', [
                    'name' => $Folder->getAttribute('name')
                ]),
                ErrorCodes::FILE_ALREADY_EXISTS
            );
        }

        $Parent   = $this->getParent();
        $old_path = $this->getFullPath();

        $Parent->getFullPath();

        $new_path = \str_replace(
            $Parent->getFullPath(),
            $Folder->getFullPath(),
            $this->getFullPath()
        );

        $new_file = \str_replace($this->getMedia()->getFullPath(), '', $new_path);

        // delete the file cache
        // @todo move the cache too
        if (\method_exists($this, 'deleteCache')) {
            $this->deleteCache();
        }

        if (\method_exists($this, 'deleteAdminCache')) {
            $this->deleteAdminCache();
        }


        // update file path
        QUI::getDataBase()->update(
            $this->Media->getTable(),
            [
                'file'     => $new_file,
                'pathHash' => \md5($new_file)
            ],
            ['id' => $this->getId()]
        );

        // set the new parent relationship
        QUI::getDataBase()->update(
            $this->Media->getTable('relations'),
            [
                'parent' => $Folder->getId()
            ],
            [
                'parent' => $Parent->getId(),
                'child'  => $this->getId()
            ]
        );

        // move file on the real directory
        QUIFile::move($old_path, $new_path);

        // update internal references
        $this->setAttribute('file', $new_file);


        $this->parent_id = $Folder->getId();
    }

    /**
     * copy the item to another folder
     *
     * @param \QUI\Projects\Media\Folder $Folder
     * @param QUI\Interfaces\Users\User|null $PermissionUser
     *
     * @return \QUI\Projects\Media\Item - The new file
     *
     * @throws QUI\Exception
     */
    public function copyTo(Folder $Folder, $PermissionUser = null)
    {
        $this->checkPermission('quiqqer.projects.media.edit', $PermissionUser);

        $File = $Folder->uploadFile($this->getFullPath());

        $File->setAttribute('title', $this->getAttribute('title'));
        $File->setAttribute('alt', $this->getAttribute('alt'));
        $File->setAttribute('short', $this->getAttribute('short'));
        $File->save();

        return $File;
    }

    /**
     * @param mixed ...$params
     *
     *      setTitle('text')                 - set this title to all languages
     *      setTitle('text', 'language')     - set this title to the wanted languages
     *      setTitle(['de' => 'text', 'en' => 'text'])  - set the language array
     */
    public function setTitle(...$params)
    {
        $this->setMultilingualParams($params, 'title');
    }

    /**
     * Set the title of this item
     *
     *      setShort('text')                 - set this short to all languages
     *      setShort('text', 'language')     - set this short to the wanted languages
     *      setShort(['de' => 'text', 'en' => 'text'])  - set the language array
     *
     * @param mixed ...$params
     */
    public function setShort(...$params)
    {
        $this->setDescription($params);
    }

    /**
     * Set the description of this item
     *
     *      setDescription('text')                 - set this description to all languages
     *      setDescription('text', 'language')     - set this description to the wanted languages
     *      setDescription(['de' => 'text', 'en' => 'text'])  - set the language array
     *
     * @param mixed ...$params
     */
    public function setDescription(...$params)
    {
        $this->setMultilingualParams($params, 'short');
    }

    /**
     * Set the alt of this item
     *
     *      setAlt('text')                 - set this alt to all languages
     *      setAlt('text', 'language')     - set this alt to the wanted languages
     *      setAlt(['de' => 'text', 'en' => 'text'])  - set the language array
     *
     * @param mixed ...$params
     */
    public function setAlt(...$params)
    {
        $this->setMultilingualParams($params, 'alt');
    }

    /**
     * overwritten set attribute
     * -> this method considers multilingual attributes
     *
     * @param string $name
     * @param array|bool|object|string $val
     * @return QUI\QDOM|void
     */
    public function setAttribute($name, $val)
    {
        if ($name !== 'title'
            && $name !== 'short'
            && $name !== 'description'
            && $name !== 'alt') {
            parent::setAttribute($name, $val);

            return;
        }

        // Multilingual attribute

        $languages = QUI::availableLanguages();
        $result    = [];

        // its already an array
        if (\is_array($val)) {
            foreach ($languages as $language) {
                if (isset($val[$language])) {
                    $result[$language] = $val[$language];
                }
            }

            $this->setMultilingualArray($name, $result);

            return;
        }

        // value is a string, so we need to look deeper
        $val     = \json_decode($val, true);
        $current = QUI::getLocale()->getCurrent();

        if (!$val) {
            $result[$current] = $val;
        } else {
            foreach ($languages as $language) {
                if (isset($val[$language])) {
                    $result[$language] = $val[$language];
                }
            }
        }

        $this->setMultilingualArray($name, $result);
    }

    /**
     * Set multilingual params (attributes)
     * - util helper
     * - looks at the params type
     * - helper for setTitle, setShort, setDescription, setAlt
     *
     * @param $params
     * @param $type
     */
    protected function setMultilingualParams($params, $type)
    {
        $languages = QUI::availableLanguages();

        if (\count($params) === 2) {
            $text     = $params[0];
            $language = $params[0];

            if (\in_array($languages, $language)) {
                $this->setMultilingualArray($type, [$language => $text]);
            }

            return;
        }

        if (\is_string($params[0])) {
            $text   = $params[0];
            $result = [];

            foreach ($languages as $language) {
                $result[$language] = $text;
            }

            $this->setMultilingualArray($type, $result);

            return;
        }

        $this->setMultilingualArray($type, $params[0]);
    }

    /**
     * Set multilingual attributes -> array of attributes [de => text, en => text]
     * - util method
     *
     * @param string $type
     * @param array $val
     */
    protected function setMultilingualArray($type, array $val)
    {
        switch ($type) {
            case 'title':
                foreach ($val as $language => $v) {
                    $this->title[$language] = $v;
                }
                break;

            case 'alt':
                foreach ($val as $language => $v) {
                    $this->alt[$language] = $v;
                }
                break;

            case 'short':
            case 'description':
                foreach ($val as $language => $v) {
                    $this->description[$language] = $v;
                }
                break;
        }
    }

    // endregion

    // region Get Parent Methods

    /**
     * Return the parent id
     *
     * @return integer
     */
    public function getParentId()
    {
        if ($this->parent_id) {
            return $this->parent_id;
        }

        $id = $this->getId();

        if ($id === 1) {
            return false;
        }

        $this->parent_id = $this->Media->getParentIdFrom($id);

        return $this->parent_id;
    }

    /**
     * Return all parent ids
     *
     * @return array
     */
    public function getParentIds()
    {
        if ($this->getId() === 1) {
            return [];
        }

        $parents = [];
        $id      = $this->getId();

        while ($id = $this->Media->getParentIdFrom($id)) {
            $parents[] = $id;
        }

        return \array_reverse($parents);
    }

    /**
     * Return the Parent Media Item Object
     *
     * @return \QUI\Projects\Media\Folder
     * @throws \QUI\Exception
     */
    public function getParent()
    {
        return $this->Media->get($this->getParentId());
    }

    /**
     * Return all Parents
     *
     * @return array
     *
     * @throws QUI\Exception
     */
    public function getParents()
    {
        $ids     = $this->getParentIds();
        $parents = [];

        foreach ($ids as $id) {
            $parents[] = $this->Media->get($id);
        }

        return $parents;
    }

    // endregion

    // region Path and URL Methods

    /**
     * Return the path of the file, without host, url dir or cms dir
     *
     * @return string
     */
    public function getPath()
    {
        return $this->getAttribute('file');
    }

    /**
     * Return the fullpath of the file
     *
     * @return string
     */
    public function getFullPath()
    {
        return $this->Media->getFullPath().$this->getAttribute('file');
    }

    /**
     * Returns information about a file path
     *
     * @param array|boolean $options - If present, specifies a specific element to be returned;
     *                                  one of:
     *                                  PATHINFO_DIRNAME, PATHINFO_BASENAME,
     *                                  PATHINFO_EXTENSION or PATHINFO_FILENAME.
     * @return mixed
     */
    public function getPathinfo($options = false)
    {
        if (!$options) {
            return \pathinfo($this->getFullPath());
        }

        return \pathinfo($this->getFullPath(), $options);
    }

    /**
     * Returns the url from the file
     *
     * @param boolean $rewritten - false = image.php, true = rewrited URL
     *
     * @return string
     */
    public function getUrl($rewritten = false)
    {
        if ($rewritten == false) {
            $Project = $this->Media->getProject();

            $str = 'image.php?id='.$this->getId().'&project='.$Project->getAttribute('name');

            if ($this->getAttribute('maxheight')) {
                $str .= '&maxheight='.$this->getAttribute('maxheight');
            }

            if ($this->getAttribute('maxwidth')) {
                $str .= '&maxwidth='.$this->getAttribute('maxwidth');
            }

            return $str;
        }

        if ($this->getAttribute('active') == 1) {
            return URL_DIR.$this->Media->getCacheDir().$this->getAttribute('file');
        }

        return '';
    }

    // endregion

    // region Effect methods

    /**
     * Return the effects of the item
     *
     * @return array
     */
    public function getEffects()
    {
        if (\is_array($this->effects)) {
            return $this->effects;
        }

        $effects = $this->getAttribute('image_effects');

        if (\is_string($effects)) {
            $effects = \json_decode($effects, true);
        }

        if (\is_array($effects)) {
            $this->effects = $effects;
        } else {
            $this->effects = [];
        }

        return $this->effects;
    }

    /**
     * Set an item effect
     *
     * @param string $effect - Name of the effect
     * @param string|integer|float $value - Value of the effect
     */
    public function setEffect($effect, $value)
    {
        $this->getEffects();

        $this->effects[$effect] = $value;
    }

    /**
     * Set complete effects
     *
     * @param array $effects
     */
    public function setEffects($effects = [])
    {
        $this->effects = $effects;
    }

    //endregion

    //region Hidden

    /**
     * Is the media item hidden?
     *
     * @return bool
     */
    public function isHidden()
    {
        return (bool)$this->getAttribute('hidden');
    }

    /**
     * Set the media item to hidden
     */
    public function setHidden()
    {
        $this->setAttribute('hidden', true);
    }

    /**
     * Set the media item from hidden to visible
     */
    public function setVisible()
    {
        $this->setAttribute('hidden', false);
    }

    //endregion

    //region Permissions

    /**
     * Are permissions set for this item?
     *
     * @param $permission
     * @return bool
     */
    public function hasPermissionsSet($permission)
    {
        if (Media::useMediaPermissions() === false) {
            return false;
        }

        $Manager  = QUI::getPermissionManager();
        $permList = $Manager->getMediaPermissions($this);

        return !empty($permList[$permission]);
    }

    /**
     * Are view permissions set for this item?
     *
     * @return bool
     */
    public function hasViewPermissionSet()
    {
        return $this->hasPermissionsSet('quiqqer.projects.media.view');
    }

    /**
     * Shortcut for QUI\Permissions\Permission::hasSitePermission
     *
     * @param string $permission - name of the permission
     * @param QUI\Users\User|boolean $User - optional
     *
     * @return boolean|integer
     */
    public function hasPermission($permission, $User = false)
    {
        if (Media::useMediaPermissions() === false) {
            return true;
        }


        $Manager  = QUI::getPermissionManager();
        $permList = $Manager->getMediaPermissions($this);

        if (empty($permList[$permission])) {
            return true;
        }

        return QUI\Permissions\Permission::hasMediaPermission(
            $permission,
            $this,
            $User
        );
    }

    /**
     * Shortcut for QUI\Permissions\Permission::checkSitePermission
     *
     * @param string $permission - name of the permission
     * @param QUI\Users\User|boolean $User - optional
     *
     * @throws QUI\Permissions\Exception
     */
    public function checkPermission($permission, $User = false)
    {
        if (Media::useMediaPermissions() === false) {
            return;
        }


        $Manager  = QUI::getPermissionManager();
        $permList = $Manager->getMediaPermissions($this);

        if (empty($permList[$permission])) {
            return;
        }

        QUI\Permissions\Permission::checkMediaPermission(
            $permission,
            $this,
            $User
        );
    }

    /**
     * Add an user to the permission
     *
     * @param string $permission - name of the permission
     * @param User $User - User Object
     * @param boolean|\QUI\Users\User $EditUser
     *
     * @throws QUI\Exception
     */
    public function addUserToPermission(User $User, $permission, $EditUser = false)
    {
        Permission::addUserToMediaPermission($User, $this, $permission, $EditUser);
    }

    /**
     * add an group to the permission
     *
     * @param Group $Group
     * @param string $permission
     * @param boolean|\QUI\Users\User $EditUser
     *
     * @throws QUI\Exception
     */
    public function addGroupToPermission(Group $Group, $permission, $EditUser = false)
    {
        Permission::addGroupToMediaPermission($Group, $this, $permission, $EditUser);
    }

    /**
     * Remove an user from the permission
     *
     * @param string $permission - name of the permission
     * @param User $User - User Object#
     * @param boolean|\QUI\Users\User $EditUser
     *
     * @throws QUI\Exception
     */
    public function removeUserFromSitePermission(User $User, $permission, $EditUser = false)
    {
        Permission::removeUserFromMediaPermission($User, $this, $permission, $EditUser);
    }

    /**
     * Remove a group from the permission
     *
     * @param Group $Group
     * @param string $permission - name of the permission
     * @param boolean|\QUI\Users\User $EditUser
     *
     * @throws QUI\Exception
     */
    public function removeGroupFromSitePermission(Group $Group, $permission, $EditUser = false)
    {
        Permission::removeGroupFromMediaPermission($Group, $this, $permission, $EditUser);
    }



    //endregion

    //region Path history

    /**
     * @return array
     */
    public function getPathHistory()
    {
        if ($this->pathHistory !== null) {
            return $this->pathHistory;
        }

        $pathHistory = $this->getAttribute('pathHistory');

        if (!empty($pathHistory)) {
            $pathHistory = \json_decode($pathHistory, true);

            if (\is_array($pathHistory)) {
                $this->pathHistory = $pathHistory;
            }
        }

        if (empty($this->pathHistory)) {
            $this->pathHistory[] = $this->getPath();
        }

        return $this->pathHistory;
    }

    /**
     * @param string $path
     */
    public function addToPathHistory($path)
    {
        $this->getPathHistory();

        $this->pathHistory[] = $path;
    }

    //endregion
}
