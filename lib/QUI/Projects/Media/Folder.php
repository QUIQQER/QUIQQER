<?php

/**
 * This file contains the \QUI\Projects\Media\Folder
 */

namespace QUI\Projects\Media;

use QUI;
use QUI\Projects\Media\Utils as MediaUtils;
use QUI\Utils\System\File as FileUtils;
use QUI\Utils\StringHelper as StringUtils;
use QUI\Utils\Security\Orthos;

/**
 * A media folder
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.projects.media
 * @licence For copyright and license information, please view the /README.md
 */
class Folder extends Item implements QUI\Interfaces\Projects\Media\File
{
    /**
     * Upload file flag - dont overwrite the file
     */
    const FILE_OVERWRITE_NONE = 0;

    /**
     * Upload file flag - overwrite the file, dont delete the old file
     */
    const FILE_OVERWRITE_TRUE = 1;

    /**
     * Upload file flag - overwrite the file and delete the old file
     */
    const FILE_OVERWRITE_DESTROY = 2;

    /**
     * direct children of the folder
     *
     * @var array
     */
    protected $children = [];

    /**
     * (non-PHPdoc)
     *
     * @throws QUI\Exception
     * @see QUI\Interfaces\Projects\Media\File::activate()
     */
    public function activate()
    {
        QUI::getDataBase()->update(
            $this->Media->getTable(),
            ['active' => 1],
            ['id' => $this->getId()]
        );

        $this->setAttribute('active', 1);

        // activate resursive to the top
        $Media       = $this->Media;
        $parents_ids = $this->getParentIds();

        foreach ($parents_ids as $id) {
            try {
                $Item = $Media->get($id);
                $Item->activate();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        // Cacheordner erstellen
        $this->createCache();

        QUI::getEvents()->fireEvent('mediaActivate', [$this]);
    }

    /**
     * (non-PHPdoc)
     *
     * @throws QUI\Exception
     * @see QUI\Interfaces\Projects\Media\File::deactivate()
     */
    public function deactivate()
    {
        if ($this->isActive() === false) {
            return;
        }

        QUI::getDataBase()->update(
            $this->Media->getTable(),
            ['active' => 0],
            ['id' => $this->getId()]
        );

        $this->setAttribute('active', 0);

        // Images / Folders / Files rekursive deactivasion
        $ids   = $this->getAllRecursiveChildrenIds();
        $Media = $this->Media;

        foreach ($ids as $id) {
            try {
                $Item = $Media->get($id);
                $Item->deactivate();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        $this->deleteCache();

        QUI::getEvents()->fireEvent('mediaDeactivate', [$this]);
    }

    /**
     * (non-PHPdoc)
     *
     * @throws QUI\Exception
     * @see QUI\Projects\Media\Item::delete()
     *
     */
    public function delete()
    {
        if ($this->isDeleted()) {
            throw new QUI\Exception(
                'Folder is already deleted',
                ErrorCodes::FOLDER_ALREADY_DELETED
            );
        }

        if ($this->getId() == 1) {
            throw new QUI\Exception(
                'Root cannot deleted',
                ErrorCodes::ROOT_FOLDER_CANT_DELETED
            );
        }

        QUI::getEvents()->fireEvent('mediaDeleteBegin', [$this]);

        $children = $this->getAllRecursiveChildrenIds();

        // move files to the temp folder
        // and delete the files first
        foreach ($children as $id) {
            try {
                $File = $this->Media->get($id);

                if (MediaUtils::isFolder($File) === false) {
                    $File->delete();
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        // now delete all sub folders
        foreach ($children as $id) {
            try {
                $File = $this->Media->get($id);

                if (MediaUtils::isFolder($File) === false) {
                    continue;
                }

                // delete database entries
                QUI::getDataBase()->delete(
                    $this->Media->getTable(),
                    ['id' => $id]
                );

                QUI::getDataBase()->delete(
                    $this->Media->getTable('relations'),
                    ['child' => $id]
                );
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        // delete the own database entries
        QUI::getDataBase()->delete(
            $this->Media->getTable(),
            ['id' => $this->getId()]
        );

        QUI::getDataBase()->delete(
            $this->Media->getTable('relations'),
            ['child' => $this->getId()]
        );

        FileUtils::unlink($this->getFullPath());


        // delete cache
        $this->deleteCache();

        QUI::getEvents()->fireEvent('mediaDelete', [$this]);
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Projects\Media\Item::destroy()
     */
    public function destroy()
    {
        // nothing
        // folders are not in the trash

        QUI::getEvents()->fireEvent('mediaDestroy', [$this]);
    }

    /**
     * (non-PHPdoc)
     *
     * @param QUI\Projects\Media\Folder $Parent
     * @see QUI\Interfaces\Projects\Media\File::restore()
     *
     */
    public function restore(QUI\Projects\Media\Folder $Parent)
    {
        // nothing
        // folders are not in the trash
    }

    /**
     * (non-PHPdoc)
     *
     * @param string $newname - new name for the folder
     *
     * @throws QUI\Exception
     * @see QUI\Projects\Media\Item::rename()
     *
     */
    public function rename($newname)
    {
        if (empty($newname)) {
            throw new QUI\Exception(
                ['quiqqer/quiqqer', 'exception.media.folder.name.invalid'],
                ErrorCodes::FOLDER_NAME_INVALID
            );
        }

        if ($this->getId() == 1) {
            throw new QUI\Exception(
                ['quiqqer/quiqqer', 'exception.media.root.folder.rename'],
                ErrorCodes::ROOT_FOLDER_CANT_RENAMED
            );
        }

        // filter illegal characters
        $Parent  = $this->getParent();
        $newname = Utils::stripFolderName($newname);

        // rename
        if ($newname == $this->getAttribute('name')) {
            return;
        }


        // check if a folder with the new name exist
        if ($Parent->childWithNameExists($newname)) {
            throw new QUI\Exception(
                ['quiqqer/quiqqer', 'exception.media.folder.with.same.name.exists'],
                ErrorCodes::FOLDER_ALREADY_EXISTS
            );
        }

        $PDO      = QUI::getDataBase()->getPDO();
        $old_path = $this->getPath().'/';
        $new_path = $Parent->getPath().'/'.$newname;

        $new_path = StringUtils::replaceDblSlashes($new_path);
        $new_path = \ltrim($new_path, '/');

        $old_path = StringUtils::replaceDblSlashes($old_path);
        $old_path = \rtrim($old_path, '/');
        $old_path = \ltrim($old_path, '/');


        // update children paths
        $Statement = $PDO->prepare(
            "UPDATE ".$this->Media->getTable()."
             SET file = REPLACE(file, :oldpath, :newpath)
             WHERE file LIKE :search"
        );

        $Statement->bindValue('oldpath', $old_path.'/');
        $Statement->bindValue('newpath', $new_path.'/');
        $Statement->bindValue('search', $old_path."/%");

        $Statement->execute();

        $title = $this->getAttribute('title');

        if ($title == $this->getAttribute('name')) {
            $title = $newname;
        }

        // update me
        QUI::getDataBase()->update(
            $this->Media->getTable(),
            [
                'name'  => $newname,
                'file'  => StringUtils::replaceDblSlashes($new_path.'/'),
                'title' => $title
            ],
            ['id' => $this->getId()]
        );

        FileUtils::move(
            $this->Media->getFullPath().$old_path,
            $this->Media->getFullPath().$new_path
        );

        // @todo rename cache instead of delete
        $this->deleteCache();

        $this->setAttribute('name', $newname);
        $this->setAttribute('file', $new_path.'/');

        QUI::getEvents()->fireEvent('mediaRename', [$this]);
    }

    /**
     * (non-PHPdoc)
     *
     * @param QUI\Projects\Media\Folder $Folder
     *
     * @throws QUI\Exception
     * @see QUI\Projects\Media\Item::moveTo()
     *
     */
    public function moveTo(QUI\Projects\Media\Folder $Folder)
    {
        $Parent = $this->getParent();

        if ($Folder->getId() === $Parent->getId()) {
            return;
        }


        if ($Folder->childWithNameExists($this->getAttribute('name'))) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/system', 'exception.media.folder.already.exists', [
                    'name' => $this->getAttribute('name')
                ]),
                ErrorCodes::FOLDER_ALREADY_EXISTS
            );
        }

        $PDO      = QUI::getDataBase()->getPDO();
        $old_path = $this->getPath();
        $new_path = $Folder->getPath().'/'.$this->getAttribute('name');

        $old_path = StringUtils::replaceDblSlashes($old_path);
        $new_path = StringUtils::replaceDblSlashes($new_path);

        // update children paths
        $Statement = $PDO->prepare(
            "UPDATE ".$this->Media->getTable()."
             SET file = REPLACE(file, :oldpath, :newpath)
             WHERE file LIKE :search"
        );

        $Statement->bindValue('oldpath', StringUtils::replaceDblSlashes($old_path.'/'));
        $Statement->bindValue('newpath', StringUtils::replaceDblSlashes($new_path.'/'));
        $Statement->bindValue('search', $old_path."%");

        $Statement->execute();

        // update me
        QUI::getDataBase()->update(
            $this->Media->getTable(),
            ['file' => StringUtils::replaceDblSlashes($new_path.'/')],
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

        FileUtils::move(
            $this->Media->getFullPath().$old_path,
            $this->Media->getFullPath().$new_path
        );

        // @todo rename cache instead of delete
        $this->deleteCache();
        $this->setAttribute('file', $new_path);
    }

    /**
     * (non-PHPdoc)
     *
     * @param QUI\Projects\Media\Folder $Folder
     *
     * @return QUI\Projects\Media\Folder
     * @throws QUI\Exception
     * @see QUI\Projects\Media\Item::copyTo()
     *
     */
    public function copyTo(QUI\Projects\Media\Folder $Folder)
    {
        if ($Folder->childWithNameExists($this->getAttribute('name'))) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/system', 'exception.media.folder.already.exists', [
                    'name' => $this->getAttribute('name')
                ]),
                ErrorCodes::FOLDER_ALREADY_EXISTS
            );
        }

        // copy me
        $Copy = $Folder->createFolder($this->getAttribute('name'));

        $Copy->setAttributes($this->getAttributes());
        $Copy->save();

        // copy the children
        $ids = $this->getChildrenIds();

        foreach ($ids as $id) {
            try {
                $Item = $this->Media->get($id);
                $Item->copyTo($Copy);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $Copy;
    }

    /**
     * Creates a zip in the temp and return the path to it
     *
     * @return string
     * @throws QUI\Exception
     */
    public function createZIP()
    {
        $path = $this->getFullPath();

        $tempFolder = QUI::getTemp()->createFolder();
        $newZipFile = $tempFolder.$this->getAttribute('name').'.zip';

        if (!\class_exists('\ZipArchive')) {
            throw new QUI\Exception([
                'quiqqer/quiqqer',
                'exception.zip.extension.not.installed'
            ]);
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        $countFiles = 0;

        foreach ($files as $name => $File) {
            if (!$File->isDir()) {
                $countFiles++;
            }
        }

        if (!$countFiles) {
            throw new QUI\Exception([
                'quiqqer/quiqqer',
                'exception.zip.folder.is.empty'
            ]);
        }

        $Zip = new \ZipArchive();
        $Zip->open($newZipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($files as $name => $File) {
            if ($File->isDir()) {
                continue;
            }

            $filePath     = $File->getRealPath();
            $relativePath = \substr($filePath, \strlen($path));

            $Zip->addFile($filePath, $relativePath);
        }

        $Zip->close();

        return $newZipFile;
    }

    /**
     * Return the first child
     *
     * @return QUI\Projects\Media\File
     *
     * @throws QUI\Exception
     */
    public function firstChild()
    {
        $result = $this->getChildren(
            ['limit' => 1]
        );

        if (isset($result[0])) {
            return $result[0];
        }

        throw new QUI\Exception(
            QUI::getLocale()->get('quiqqer/quiqqer', 'exception.folder.has.no.files'),
            ErrorCodes::FOLDER_HAS_NO_FILES
        );
    }

    /**
     * Returns all children in the folder
     *
     * @param array $params - [optional] db query fields
     *
     * @return array
     */
    public function getChildren($params = [])
    {
        $this->children = [];

        if (!isset($params['order'])) {
            $params['order'] = $this->getAttribute('order');
        }

        if (empty($params['order'])) {
            $params['order'] = 'priority';
        }

        $ids = $this->getChildrenIds($params);

        foreach ($ids as $id) {
            try {
                $this->children[] = $this->Media->get($id);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $this->children;
    }

    /**
     * Return the children ids ( not resursive )
     * folders first, files seconds
     *
     * @param array $params - [optional] db query fields
     *
     * If $params['count'] = true is set, then the total number of search results is returned!
     *
     * @return array|int
     */
    public function getChildrenIds($params = [])
    {
        $table     = $this->Media->getTable();
        $table_rel = $this->Media->getTable('relations');
        $order     = 'name';

        if ($this->getAttribute('order')) {
            $order = $this->getAttribute('order');
        }

        if (isset($params['order'])) {
            $order = $params['order'];
        }

        // abwärtskompatibilität
        if (\is_string($params)) {
            $order  = $params;
            $params = [];
        }

        // Sortierung
        switch ($order) {
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
                break;

            default:
                $order = 'name';
        }

        $table     = Orthos::cleanupDatabaseFieldName($table);
        $table_rel = Orthos::cleanupDatabaseFieldName($table_rel);

        $table_parent = $table_rel.'.`parent`';
        $table_child  = $table_rel.'.`child`';

        $table_id     = $table.'.`id`';
        $table_delete = $table.'.`deleted`';
        $table_type   = $table.'.`type`';
        $table_cDate  = $table.'.`c_date`';
        $table_name   = $table.'.`name`';

        $parentId = $this->getId();

        switch ($order) {
            case 'id':
            case 'id ASC':
                $order_by = "find_in_set({$table_type}, 'folder') DESC, {$table_id}";
                break;

            case 'id DESC':
                $order_by = "find_in_set({$table_type}, 'folder') DESC, {$table_id} DESC";
                break;

            case 'c_date':
            case 'c_date ASC':
                $order_by = "find_in_set({$table_type}, 'folder') DESC, {$table_cDate}";
                break;

            case 'c_date DESC':
                $order_by = "find_in_set({$table_type}, 'folder') DESC, {$table_cDate} DESC";
                break;

            case 'name ASC':
                $order_by = "find_in_set({$table_type}, 'folder') ASC, {$table_name}";
                break;

            default:
            case 'name':
            case 'name DESC':
                $order_by = "find_in_set({$table_type}, 'folder') DESC, {$table_name}";
                break;

            case 'priority':
            case 'priority ASC':
            case 'priority DESC':
                $order_by = $order;
        }

        $limit          = '';
        $isCountRequest = !empty($params['count']);

        if (!$isCountRequest && isset($params['limit'])) {
            $limitParams = \explode(',', $params['limit']);

            if (\count($limitParams) === 2) {
                $limitParams[0] = (int)$limitParams[0];
                $limitParams[1] = (int)$limitParams[1];

                $limit = "LIMIT {$limitParams[0]}, {$limitParams[1]}";
            } else {
                $limitParams[0] = (int)$limitParams[0];
                $limit          = "LIMIT {$limitParams[0]}";
            }
        }

        // hidden query
        $hiddenQuery = '';

        if (isset($params['where']) && isset($params['where']['hidden'])) {
            if ($params['where']['hidden'] === 0) {
                $hiddenQuery = ' AND hidden = 0';
            } elseif ($params['where']['hidden'] === 1) {
                $hiddenQuery = ' AND hidden = 1';
            }
        }

        $query = "
        
        SELECT id
        FROM {$table}, {$table_rel}
        WHERE
            {$table_parent} = {$parentId} AND
            {$table_child}  = {$table_id} AND
            {$table_delete} = 0
            {$hiddenQuery}
        ORDER BY
            {$order_by} {$limit}
        ;
        ";

        try {
            $fetch = QUI::getDataBase()->fetchSQL($query);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return [];
        }

        if ($isCountRequest) {
            return \count($fetch);
        }

        $result = [];

        foreach ($fetch as $entry) {
            $result[] = (int)$entry['id'];
        }

        return $result;
    }

    /**
     * Returns the count of the children
     *
     * @return integer
     */
    public function hasChildren()
    {
        $table     = $this->Media->getTable();
        $table_rel = $this->Media->getTable('relations');

        try {
            $result = QUI::getDataBase()->fetch([
                'count' => 'children',
                'from'  => [
                    $table,
                    $table_rel
                ],
                'where' => [
                    $table_rel.'.parent' => $this->getId(),
                    $table_rel.'.child'  => '`'.$table.'.id`',
                    $table.'.deleted'    => 0
                ]
            ]);
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return 0;
        }


        if (isset($result[0]) && isset($result[0]['children'])) {
            return (int)$result[0]['children'];
        }

        return 0;
    }

    /**
     * Return the first image
     *
     * @return QUI\Projects\Media\Image
     *
     * @throws QUI\Exception
     */
    public function firstImage()
    {
        $result = $this->getImages([
            'limit' => 1
        ]);

        if (isset($result[0])) {
            return $result[0];
        }

        throw new QUI\Exception(
            QUI::getLocale()->get('quiqqer/quiqqer', 'exception.folder.has.no.images'),
            ErrorCodes::FOLDER_HAS_NO_IMAGES
        );
    }

    /**
     * Return the sub folders from the folder
     *
     * @param array $params - filter paramater
     *
     * @return array
     */
    public function getFolders($params = [])
    {
        return $this->getElements('folder', $params);
    }

    /**
     * Return the files from folder
     *
     * @param array $params - filter paramater
     *
     * @return array
     */
    public function getFiles($params = [])
    {
        return $this->getElements('file', $params);
    }

    /**
     * @return int
     *
     * @todo as cron
     */
    public function getSize()
    {
        return QUI\Utils\System\Folder::getFolderSize($this->getFullPath());
    }

    /**
     * Return the images from the folder
     *
     * @param array $params - filter paramater
     *
     * @return array
     */
    public function getImages($params = [])
    {
        return $this->getElements('image', $params);
    }

    /**
     * Return children / elements in the folder
     *
     * @param string $type
     * @param array $params
     *
     * @return array|int
     */
    protected function getElements($type, $params)
    {
        switch ($type) {
            case 'image':
            case 'file':
            case 'folder':
                break;

            default:
                return [];
        }

        $table     = $this->Media->getTable();
        $table_rel = $this->Media->getTable('relations');

        $dbQuery = [
            'select' => 'id',
            'from'   => [
                $table,
                $table_rel
            ],
            'where'  => [
                $table_rel.'.parent' => $this->getId(),
                $table_rel.'.child'  => '`'.$table.'.id`',
                $table.'.deleted'    => 0,
                $table.'.type'       => $type
            ]
        ];

        if (isset($params['active'])) {
            $dbQuery['where']['active'] = $params['active'];
        } else {
            $dbQuery['where']['active'] = 1;
        }

        if (isset($params['count'])) {
            $dbQuery['count'] = 'count';

            try {
                $fetch = QUI::getDataBase()->fetch($dbQuery);
            } catch (QUI\Exception $Exception) {
                return 0;
            }

            return (int)$fetch[0]['count'];
        }

        if (isset($params['limit'])) {
            $dbQuery['limit'] = $params['limit'];
        }


        // sorting
        $order = 'title ASC';

        if ($this->getAttribute('order')) {
            $order = $this->getAttribute('order');
        }

        if (isset($params['order'])) {
            $order = $params['order'];
        }

        switch ($order) {
            case 'title':
            case 'title DESC':
            case 'title ASC':
            case 'name':
            case 'name DESC':
            case 'name ASC':
            case 'c_date':
            case 'c_date DESC':
            case 'c_date ASC':
            case 'e_date':
            case 'e_date ASC':
            case 'e_date DESC':
                break;

            case 'priority':
            case 'priority ASC':
            case 'priority DESC':
                // @todo priority, title
                break;

            default:
                $order = 'title ASC'; // title aufsteigend
                break;
        }

        $dbQuery['order'] = $order;

        // database
        try {
            $fetch = QUI::getDataBase()->fetch($dbQuery);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return [];
        }

        $result = [];

        foreach ($fetch as $entry) {
            try {
                $result[] = $this->Media->get((int)$entry['id']);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addDebug($Exception->getMessage());
            }
        }

        switch ($order) {
            case 'priority':
            case 'priority ASC':
            case 'priority DESC':
                // if priority, sort, that empty priority is the last
                \usort($result, function ($ImageA, $ImageB) {
                    /* @var $ImageA Image */
                    $a = $ImageA->getAttribute('priority');
                    /* @var $ImageB Image */
                    $b = $ImageB->getAttribute('priority');

                    if (empty($a)) {
                        return 1;
                    }

                    if (empty($b)) {
                        return -1;
                    }

                    if ($a == $b) {
                        return 0;
                    }

                    return ($a < $b) ? -1 : 1;
                });
                break;
        }

        return $result;
    }

    /**
     * Returns the count of the children
     *
     * @return integer
     * @todo use getElements folder with count
     */
    public function hasSubFolders()
    {
        $table     = $this->Media->getTable();
        $table_rel = $this->Media->getTable('relations');

        try {
            $result = QUI::getDataBase()->fetch([
                'count' => 'children',
                'from'  => [
                    $table,
                    $table_rel
                ],
                'where' => [
                    $table_rel.'.parent' => $this->getId(),
                    $table_rel.'.child'  => '`'.$table.'.id`',
                    $table.'.deleted'    => 0,
                    $table.'.type'       => 'folder'
                ]
            ]);
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return 0;
        }

        if (isset($result[0]) && isset($result[0]['children'])) {
            return (int)$result[0]['children'];
        }

        return 0;
    }

    /**
     * Returns only the sub folders
     *
     * @return array
     * @deprecated use getFolders
     */
    public function getSubFolders()
    {
        $table     = $this->Media->getTable();
        $table_rel = $this->Media->getTable('relations');

        $result = QUI::getDataBase()->fetch([
            'from'  => [
                $table,
                $table_rel
            ],
            'where' => [
                $table_rel.'.parent' => $this->getId(),
                $table_rel.'.child'  => '`'.$table.'.id`',
                $table.'.deleted'    => 0,
                $table.'.type'       => 'folder'
            ],
            'order' => 'name'
        ]);

        $folders = [];

        foreach ($result as $entry) {
            try {
                $folders[] = $this->Media->get((int)$entry['id']);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $folders;
    }

    /**
     * Return a file from the folder by name
     *
     * @param string $filename
     *
     * @return QUI\Projects\Media\Item
     * @throws QUI\Exception
     */
    public function getChildByName($filename)
    {
        $table     = $this->Media->getTable();
        $table_rel = $this->Media->getTable('relations');

        $result = QUI::getDataBase()->fetch([
            'select' => [
                $table.'.id'
            ],
            'from'   => [
                $table,
                $table_rel
            ],
            'where'  => [
                $table_rel.'.parent' => $this->getId(),
                $table_rel.'.child'  => '`'.$table.'.id`',
                $table.'.deleted'    => 0,
                $table.'.name'       => $filename
            ],
            'limit'  => 1
        ]);

        if (!isset($result[0])) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.media.file.not.found.NAME', [
                    'file' => $filename
                ]),
                ErrorCodes::FILE_NOT_FOUND
            );
        }

        return $this->Media->get((int)$result[0]['id']);
    }

    /**
     * Return true if a child with the name exist
     *
     * @param string $name - name (my_holiday)
     *
     * @return boolean
     */
    public function childWithNameExists($name)
    {
        try {
            $this->getChildByName($name);

            return true;
        } catch (QUI\Exception $Exception) {
        }

        return false;
    }

    /**
     * Return true if a file with the filename in the folder exists
     *
     * @param string $file - filename (my_holiday.png)
     *
     * @return boolean
     */
    public function fileWithNameExists($file)
    {
        $table     = $this->Media->getTable();
        $table_rel = $this->Media->getTable('relations');

        try {
            $result = QUI::getDataBase()->fetch([
                'select' => [
                    $table.'.id'
                ],
                'from'   => [
                    $table,
                    $table_rel
                ],
                'where'  => [
                    $table_rel.'.parent' => $this->getId(),
                    $table_rel.'.child'  => '`'.$table.'.id`',
                    $table.'.file'       => $this->getPath().$file
                ],
                'limit'  => 1
            ]);
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return false;
        }

        return isset($result[0]) ? true : false;
    }

    /**
     * (non-PHPdoc)
     *
     * @throws QUI\Exception
     * @see QUI\Interfaces\Projects\Media\File::createCache()
     */
    public function createCache()
    {
        if (!$this->getAttribute('active')) {
            return true;
        }

        $cacheDir = CMS_DIR.$this->Media->getCacheDir().$this->getAttribute('file');

        if (FileUtils::mkdir($cacheDir)) {
            return true;
        }

        throw new QUI\Exception(
            'createCache() Error; Could not create Folder '.$cacheDir,
            ErrorCodes::FOLDER_CACHE_CREATION_MKDIR_ERROR
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @throws QUI\Exception
     * @see QUI\Interfaces\Projects\Media\File::deleteCache()
     */
    public function deleteCache()
    {
        FileUtils::unlink(
            $this->Media->getAttribute('cache_dir').$this->getAttribute('file')
        );

        return true;
    }

    /**
     * Adds / create a subfolder
     *
     * @param string $foldername - Name of the new folder
     *
     * @return QUI\Projects\Media\Folder
     * @throws QUI\Exception
     */
    public function createFolder($foldername)
    {
        // Namensprüfung wegen unerlaubten Zeichen
        MediaUtils::checkFolderName($foldername);

        // Whitespaces am Anfang und am Ende rausnehmen
        $new_name = \trim($foldername);


        $User = QUI::getUserBySession();
        $dir  = $this->Media->getFullPath().$this->getPath();

        if (\is_dir($dir.$new_name)) {
            // prüfen ob dieser ordner schon als kind existiert
            // wenn nein, muss dieser ordner in der DB angelegt werden

            try {
                $children = $this->getChildByName($new_name);
            } catch (QUI\Exception $Exception) {
                $children = false;
            }

            if ($children) {
                throw new QUI\Exception(
                    'Der Ordner existiert schon '.$dir.$new_name,
                    ErrorCodes::FOLDER_ALREADY_EXISTS
                );
            }
        }

        FileUtils::mkdir($dir.$new_name);

        $table     = $this->Media->getTable();
        $table_rel = $this->Media->getTable('relations');

        // In die DB legen
        QUI::getDataBase()->insert($table, [
            'name'      => $new_name,
            'title'     => $new_name,
            'short'     => $new_name,
            'type'      => 'folder',
            'file'      => $this->getAttribute('file').$new_name.'/',
            'alt'       => $new_name,
            'c_date'    => \date('Y-m-d h:i:s'),
            'e_date'    => \date('Y-m-d h:i:s'),
            'c_user'    => $User->getId(),
            'e_user'    => $User->getId(),
            'mime_type' => 'folder'
        ]);

        $id = QUI::getDataBase()->getPDO()->lastInsertId();

        QUI::getDataBase()->insert($table_rel, [
            'parent' => $this->getId(),
            'child'  => $id
        ]);

        if (\is_dir($dir.$new_name)) {
            $Folder = $this->Media->get($id);

            $Folder->setEffects($this->getEffects());
            $Folder->save();

            return $Folder;
        }

        throw new QUI\Exception(
            ['quiqqer/quiqqer', 'exception.media.folder.could.not.be.created'],
            ErrorCodes::FOLDER_ERROR_CREATION
        );
    }

    /**
     * Uploads a file to the Folder
     *
     * @param string $file - Path to the File
     * @param integer $options - Overwrite flags,
     *                           self::FILE_OVERWRITE_NONE
     *                           self::FILE_OVERWRITE_FILE
     *                           self::FILE_OVERWRITE_DESTROY
     *
     * @return QUI\Projects\Media\Item
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     */
    public function uploadFile($file, $options = self::FILE_OVERWRITE_NONE)
    {
        QUI\Permissions\Permission::checkPermission(
            'quiqqer.projects.media.upload'
        );


        if (!\file_exists($file)) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/system', 'exception.file.not.found', [
                    'file' => $file
                ]),
                ErrorCodes::FILE_NOT_FOUND
            );
        }

        if (\is_dir($file)) {
            return $this->uploadFolder($file);
        }

        $fileinfo = FileUtils::getInfo($file);
        $filename = MediaUtils::stripMediaName($fileinfo['basename']);


        // test if the image is readable
        if (MediaUtils::getMediaTypeByMimeType($fileinfo['mime_type']) === 'image'
            && \strpos($fileinfo['mime_type'], 'svg') === false
        ) {
            try {
                $this->getMedia()->getImageManager()->make($file);
            } catch (\Exception $Exception) {
                $message = $Exception->getMessage();

                // gd lib has some unsupported image types
                // but we can go on
                if (\strpos($message, 'Unsupported image type') === false) {
                    QUI\System\Log::addError($Exception->getMessage());

                    throw new QUI\Exception(
                        ['quiqqer/quiqqer', 'exception.image.upload.image.corrupted'],
                        ErrorCodes::FILE_IMAGE_CORRUPT
                    );
                }
            }
        }


        // mb_strtolower hat folgenden Grund: file_exists beachtet Gross und Kleinschreibung im Unix Systemen
        // Daher sind die Namen im Mediabereich alle klein geschrieben damit es keine Doppelten Dateien geben kann
        // Test.jpg und test.jpg wären unterschiedliche Dateien bei Windows aber nicht
        $filename = \mb_strtolower($filename);

        // svg fix
        if ($fileinfo['mime_type'] == 'text/html'
            || $fileinfo['mime_type'] == 'text/plain'
            || $fileinfo['mime_type'] == 'image/svg'
        ) {
            $content = \file_get_contents($file);

            if (\strpos($content, '<svg') !== false && \strpos($content, '</svg>')) {
                \file_put_contents(
                    $file,
                    '<?xml version="1.0" encoding="UTF-8"?>'.
                    $content
                );

                $fileinfo = FileUtils::getInfo($file);
            }
        }

        // if no ending, we search for one
        if (!isset($fileinfo['extension']) || empty($fileinfo['extension'])) {
            $filename .= FileUtils::getEndingByMimeType($fileinfo['mime_type']);
        }

        $new_file = $this->getFullPath().'/'.$filename;
        $new_file = \str_replace("//", "/", $new_file);

        // overwrite the file
        if (\file_exists($new_file)) {
            if ($options != self::FILE_OVERWRITE_DESTROY
                && $options != self::FILE_OVERWRITE_TRUE
            ) {
                throw new QUI\Exception(
                    QUI::getLocale()->get('quiqqer/system', 'exception.media.file.already.exists', [
                        'filename' => $filename
                    ]),
                    ErrorCodes::FILE_ALREADY_EXISTS
                );
            }

            // overwrite file
            try {
                $Item = MediaUtils::getElement($new_file);

                if (MediaUtils::isImage($Item)) {
                    /* @var $Item QUI\Projects\Media\Image */
                    $Item->deleteCache();
                }

                $Item->deactivate();
                $Item->delete();

                if ($options == self::FILE_OVERWRITE_DESTROY) {
                    $Item->destroy();
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addDebug(
                    $Exception->getMessage(),
                    ['file' => $new_file]
                );

                \unlink($new_file);
            }
        }

        // copy the file to the media
        FileUtils::copy($file, $new_file);


        // create the database entry
        $User      = QUI::getUserBySession();
        $table     = $this->Media->getTable();
        $table_rel = $this->Media->getTable('relations');

        $new_file_info = FileUtils::getInfo($new_file);
        $title         = \str_replace('_', ' ', $new_file_info['filename']);

        if (empty($new_file_info['filename'])) {
            $new_file_info['filename'] = \time();
        }

        $filePath = $this->getAttribute('file').'/'.$new_file_info['basename'];

        if ($this->getId() == 1) {
            $filePath = $new_file_info['basename'];
        }

        $filePath    = StringUtils::replaceDblSlashes($filePath);
        $imageWidth  = null;
        $imageHeight = null;

        if (isset($new_file_info['width']) && $new_file_info['width']) {
            $imageWidth = (int)$new_file_info['width'];
        }

        if (isset($new_file_info['height']) && $new_file_info['height']) {
            $imageHeight = (int)$new_file_info['height'];
        }


        QUI::getDataBase()->insert($table, [
            'name'         => $new_file_info['filename'],
            'title'        => $title,
            'short'        => '',
            'file'         => $filePath,
            'alt'          => $title,
            'c_date'       => \date('Y-m-d h:i:s'),
            'e_date'       => \date('Y-m-d h:i:s'),
            'c_user'       => $User->getId(),
            'e_user'       => $User->getId(),
            'mime_type'    => $new_file_info['mime_type'],
            'image_width'  => $imageWidth,
            'image_height' => $imageHeight,
            'type'         => MediaUtils::getMediaTypeByMimeType($new_file_info['mime_type'])
        ]);

        $id = QUI::getDataBase()->getPDO()->lastInsertId();

        QUI::getDataBase()->insert($table_rel, [
            'parent' => $this->getId(),
            'child'  => $id
        ]);

        /* @var $File QUI\Projects\Media\File */
        $File = $this->Media->get($id);
        $File->generateMD5();
        $File->generateSHA1();

        $maxSize = $this->getProject()->getConfig('media_maxUploadSize');

        // if it is an image, than resize -> if needed
        if (Utils::isImage($File) && $maxSize) {
            /* @var $File Image */
            $resizeData = $File->getResizeSize($maxSize, $maxSize);

            if ($new_file_info['width'] > $maxSize || $new_file_info['height'] > $maxSize) {
                $File->resize($resizeData['width'], $resizeData['height']);

                QUI::getDataBase()->update(
                    $table,
                    [
                        'image_width'  => $resizeData['width'],
                        'image_height' => $resizeData['height'],
                    ],
                    [
                        'id' => $id
                    ]
                );
            }

            $File->setEffects($this->getEffects());
        }

        $File->save();

        return $File;
    }

    /**
     * Set the effects recursive to all items and folders
     *
     * @todo do this as a job
     */
    public function setEffectsRecursive()
    {
        $Media   = $this->getMedia();
        $ids     = $this->getAllRecursiveChildrenIds();
        $effects = $this->getEffects();

        foreach ($ids as $id) {
            try {
                \set_time_limit(1);
                $Item = $Media->get($id);

                if (MediaUtils::isFolder($Item) || MediaUtils::isImage($Item)) {
                    $Item->setEffects($effects);
                    $Item->save();
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addDebug($Exception->getMessage());
            }
        }
    }

    /**
     * If the file is a folder
     *
     * @param string $path - Path to the dir
     * @param QUI\Projects\Media\Folder|boolean $Folder - (optional) Uploaded Folder
     *
     * @return QUI\Projects\Media\Item
     * @throws QUI\Exception
     */
    protected function uploadFolder($path, $Folder = false)
    {
        $files = FileUtils::readDir($path);

        foreach ($files as $file) {
            // subfolders
            if (\is_dir($path.'/'.$file)) {
                $foldername = MediaUtils::stripFolderName($file);

                try {
                    $NewFolder = $this->getChildByName($foldername);
                } catch (QUI\Exception $Exception) {
                    $NewFolder = $this->createFolder($foldername);
                }

                $this->uploadFolder($path.'/'.$file, $NewFolder);
                continue;
            }

            // import files
            if ($Folder) {
                $Folder->uploadFile($path.'/'.$file);
            } else {
                $this->uploadFile($path.'/'.$file);
            }
        }

        return $this;
    }

    /**
     * Returns all ids from children under the folder
     *
     * @return array
     */
    protected function getAllRecursiveChildrenIds()
    {
        // own sql statement, not over the getChildren() method,
        // its better for performance
        try {
            $children = QUI::getDataBase()->fetch([
                'select' => 'id',
                'from'   => $this->Media->getTable(),
                'where'  => [
                    'file' => [
                        'value' => $this->getAttribute('file'),
                        'type'  => 'LIKE%'
                    ]
                ]
            ]);
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return [];
        }

        $result = [];

        foreach ($children as $child) {
            $result[] = $child['id'];
        }

        return $result;
    }
}
