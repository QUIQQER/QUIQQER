<?php

/**
 * This file contains the \QUI\Projects\Media\Folder
 */

namespace QUI\Projects\Media;

use QUI;
use QUI\Projects\Media\Utils as MediaUtils;
use QUI\Utils\System\File as FileUtils;
use QUI\Utils\StringHelper as StringUtils;

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
    protected $children = array();

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Projects\Media\File::activate()
     */
    public function activate()
    {
        QUI::getDataBase()->update(
            $this->Media->getTable(),
            array('active' => 1),
            array('id' => $this->getId())
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

        QUI::getEvents()->fireEvent('mediaActivate', array($this));
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Projects\Media\File::deactivate()
     */
    public function deactivate()
    {
        if ($this->isActive() === false) {
            return;
        }

        QUI::getDataBase()->update(
            $this->Media->getTable(),
            array('active' => 0),
            array('id' => $this->getId())
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

        QUI::getEvents()->fireEvent('mediaDeactivate', array($this));
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Projects\Media\Item::delete()
     */
    public function delete()
    {
        if ($this->isDeleted()) {
            throw new QUI\Exception('Folder is already deleted', 400);
        }

        if ($this->getId() == 1) {
            throw new QUI\Exception('Root cannot deleted', 400);
        }

        QUI::getEvents()->fireEvent('mediaDeleteBegin', array($this));

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
                    array('id' => $id)
                );

                QUI::getDataBase()->delete(
                    $this->Media->getTable('relations'),
                    array('child' => $id)
                );
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        // delete the own database entries
        QUI::getDataBase()->delete(
            $this->Media->getTable(),
            array('id' => $this->getId())
        );

        QUI::getDataBase()->delete(
            $this->Media->getTable('relations'),
            array('child' => $this->getId())
        );

        FileUtils::unlink($this->getFullPath());


        // delete cache
        $this->deleteCache();

        QUI::getEvents()->fireEvent('mediaDelete', array($this));
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

        QUI::getEvents()->fireEvent('mediaDestroy', array($this));
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Projects\Media\File::restore()
     *
     * @param QUI\Projects\Media\Folder $Parent
     */
    public function restore(QUI\Projects\Media\Folder $Parent)
    {
        // nothing
        // folders are not in the trash
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Projects\Media\Item::rename()
     *
     * @param string $newname - new name for the folder
     *
     * @throws QUI\Exception
     */
    public function rename($newname)
    {
        if (empty($newname)) {
            throw new QUI\Exception(
                'Dieser Name ist ungültig'
            );
        }

        // filter illegal characters
        $newname = Utils::stripFolderName($newname);

        // rename

        if ($newname == $this->getAttribute('name')) {
            return;
        }

        if ($this->getId() == 1) {
            throw new QUI\Exception(
                'Der Media-Root-Verzeichnis eines Projektes kann nicht umbenannt werden'
            );
        }


        // check if a folder with the new name exist
        $Parent = $this->getParent();

        if ($Parent->childWithNameExists($newname)) {
            throw new QUI\Exception(
                'Ein Ordner mit dem gleichen Namen existiert bereits.',
                403
            );
        }

        $PDO      = QUI::getDataBase()->getPDO();
        $old_path = $this->getPath() . '/';
        $new_path = $Parent->getPath() . '/' . $newname;

        $new_path = StringUtils::replaceDblSlashes($new_path);
        $new_path = ltrim($new_path, '/');

        $old_path = StringUtils::replaceDblSlashes($old_path);
        $old_path = rtrim($old_path, '/');
        $old_path = ltrim($old_path, '/');


        // update children paths
        $Statement = $PDO->prepare(
            "UPDATE " . $this->Media->getTable() . "
             SET file = REPLACE(file, :oldpath, :newpath)
             WHERE file LIKE :search"
        );

        $Statement->bindValue('oldpath', $old_path . '/');
        $Statement->bindValue('newpath', $new_path . '/');
        $Statement->bindValue('search', $old_path . "/%");

        $Statement->execute();

        $title = $this->getAttribute('title');

        if ($title == $this->getAttribute('name')) {
            $title = $newname;
        }

        // update me
        QUI::getDataBase()->update(
            $this->Media->getTable(),
            array(
                'name'  => $newname,
                'file'  => StringUtils::replaceDblSlashes($new_path . '/'),
                'title' => $title
            ),
            array('id' => $this->getId())
        );

        FileUtils::move(
            $this->Media->getFullPath() . $old_path,
            $this->Media->getFullPath() . $new_path
        );

        // @todo rename cache instead of delete
        $this->deleteCache();

        $this->setAttribute('name', $newname);
        $this->setAttribute('file', $new_path);

        QUI::getEvents()->fireEvent('mediaRename', array($this));
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Projects\Media\Item::moveTo()
     *
     * @param QUI\Projects\Media\Folder $Folder
     *
     * @throws QUI\Exception
     */
    public function moveTo(QUI\Projects\Media\Folder $Folder)
    {
        $Parent = $this->getParent();

        if ($Folder->getId() === $Parent->getId()) {
            return;
        }


        if ($Folder->childWithNameExists($this->getAttribute('name'))) {
            throw new QUI\Exception(
                'Ein Ordner mit dem gleichen Namen existiert bereits.',
                403 // #locale
            );
        }

        $PDO      = QUI::getDataBase()->getPDO();
        $old_path = $this->getPath();
        $new_path = $Folder->getPath() . '/' . $this->getAttribute('name');

        $old_path = StringUtils::replaceDblSlashes($old_path);
        $new_path = StringUtils::replaceDblSlashes($new_path);

        // update children paths
        $Statement = $PDO->prepare(
            "UPDATE " . $this->Media->getTable() . "
             SET file = REPLACE(file, :oldpath, :newpath)
             WHERE file LIKE :search"
        );

        $Statement->bindValue('oldpath', StringUtils::replaceDblSlashes($old_path . '/'));
        $Statement->bindValue('newpath', StringUtils::replaceDblSlashes($new_path . '/'));
        $Statement->bindValue('search', $old_path . "%");

        $Statement->execute();

        // update me
        QUI::getDataBase()->update(
            $this->Media->getTable(),
            array('file' => StringUtils::replaceDblSlashes($new_path . '/')),
            array('id' => $this->getId())
        );

        // set the new parent relationship
        QUI::getDataBase()->update(
            $this->Media->getTable('relations'),
            array(
                'parent' => $Folder->getId()
            ),
            array(
                'parent' => $Parent->getId(),
                'child'  => $this->getId()
            )
        );

        FileUtils::move(
            $this->Media->getFullPath() . $old_path,
            $this->Media->getFullPath() . $new_path
        );

        // @todo rename cache instead of delete
        $this->deleteCache();
        $this->setAttribute('file', $new_path);
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Projects\Media\Item::copyTo()
     *
     * @param QUI\Projects\Media\Folder $Folder
     *
     * @return QUI\Projects\Media\Folder
     * @throws QUI\Exception
     */
    public function copyTo(QUI\Projects\Media\Folder $Folder)
    {
        if ($Folder->childWithNameExists($this->getAttribute('name'))) {
            throw new QUI\Exception(
                'Ein Ordner mit dem gleichen Namen existiert bereits.',
                403 // #locale
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
     * Return the first child
     *
     * @return QUI\Projects\Media\File
     *
     * @throws QUI\Exception
     */
    public function firstChild()
    {
        $result = $this->getChildren(
            array(
                'limit' => 1
            )
        );

        if (isset($result[0])) {
            return $result[0];
        }

        throw new QUI\Exception(
            'Kein Kind gefunden',
            404 // #locale
        );
    }

    /**
     * Returns all children in the folder
     *
     * @param array $params - [optional] db query fields
     *
     * @return array
     */
    public function getChildren($params = array())
    {
        $this->children = array();

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
     * @return array
     */
    public function getChildrenIds($params = array())
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
        if (is_string($params)) {
            $order  = $params;
            $params = array();
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

        switch ($order) {
            case 'id':
            case 'id ASC':
                $order_by
                    = 'find_in_set(' . $table . '.type, \'folder\') DESC, ' . $table
                      . '.id';
                break;

            case 'id DESC':
                $order_by
                    = 'find_in_set(' . $table . '.type, \'folder\') DESC, ' . $table
                      . '.id DESC';
                break;

            case 'c_date':
            case 'c_date ASC':
                $order_by
                    = 'find_in_set(' . $table . '.type, \'folder\') DESC, ' . $table
                      . '.c_date';
                break;

            case 'c_date DESC':
                $order_by
                    = 'find_in_set(' . $table . '.type, \'folder\') DESC, ' . $table
                      . '.c_date DESC';
                break;

            case 'name ASC':
                $order_by
                    = 'find_in_set(' . $table . '.type, \'folder\') ASC, ' . $table
                      . '.name';
                break;

            default:
            case 'name':
            case 'name DESC':
                $order_by
                    = 'find_in_set(' . $table . '.type, \'folder\') DESC, ' . $table
                      . '.name';
                break;

            case 'priority':
            case 'priority ASC':
            case 'priority DESC':
                $order_by = $order;
        }

        if (isset($params['limit'])) {
            $query['limit'] = $params['limit'];
        }

        $query = array(
            'select' => 'id',
            'from'   => array(
                $table,
                $table_rel
            ),
            'where'  => array(
                $table_rel . '.parent' => $this->getId(),
                $table_rel . '.child'  => '`' . $table . '.id`',
                $table . '.deleted'    => 0
            ),
            'order'  => $order_by
        );


        $fetch  = QUI::getDataBase()->fetch($query);
        $result = array();

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

        $result = QUI::getDataBase()->fetch(
            array(
                'count' => 'children',
                'from'  => array(
                    $table,
                    $table_rel
                ),
                'where' => array(
                    $table_rel . '.parent' => $this->getId(),
                    $table_rel . '.child'  => '`' . $table . '.id`',
                    $table . '.deleted'    => 0
                )
            )
        );

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
        $result = $this->getImages(array(
            'limit' => 1
        ));

        if (isset($result[0])) {
            return $result[0];
        }

        throw new QUI\Exception(
            'Kein Bild gefunden',
            404 // #locale
        );
    }

    /**
     * Return the sub folders from the folder
     *
     * @param array $params - filter paramater
     *
     * @return array
     */
    public function getFolders($params = array())
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
    public function getFiles($params = array())
    {
        return $this->getElements('file', $params);
    }

    /**
     * Return the images from the folder
     *
     * @param array $params - filter paramater
     *
     * @return array
     */
    public function getImages($params = array())
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
                return array();
        }

        $table     = $this->Media->getTable();
        $table_rel = $this->Media->getTable('relations');

        $dbQuery = array(
            'select' => 'id',
            'from'   => array(
                $table,
                $table_rel
            ),
            'where'  => array(
                $table_rel . '.parent' => $this->getId(),
                $table_rel . '.child'  => '`' . $table . '.id`',
                $table . '.deleted'    => 0,
                $table . '.type'       => $type
            )
        );

        if (isset($params['active'])) {
            $dbQuery['where']['active'] = $params['active'];
        } else {
            $dbQuery['where']['active'] = 1;
        }

        if (isset($params['count'])) {
            $dbQuery['count'] = 'count';
            $fetch            = QUI::getDataBase()->fetch($dbQuery);

            return (int)$fetch[0]['count'];
        }

        if (isset($params['limit'])) {
            $dbQuery['limit'] = $params['limit'];
        }


        // sortierung
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
        $fetch  = QUI::getDataBase()->fetch($dbQuery);
        $result = array();

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
                usort($result, function ($ImageA, $ImageB) {
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
     * @todo use getElements folder with count
     *
     * @return integer
     */
    public function hasSubFolders()
    {
        $table     = $this->Media->getTable();
        $table_rel = $this->Media->getTable('relations');

        $result = QUI::getDataBase()->fetch(
            array(
                'count' => 'children',
                'from'  => array(
                    $table,
                    $table_rel
                ),
                'where' => array(
                    $table_rel . '.parent' => $this->getId(),
                    $table_rel . '.child'  => '`' . $table . '.id`',
                    $table . '.deleted'    => 0,
                    $table . '.type'       => 'folder'
                )
            )
        );

        if (isset($result[0]) && isset($result[0]['children'])) {
            return (int)$result[0]['children'];
        }

        return 0;
    }

    /**
     * Returns only the sub folders
     *
     * @deprecated use getFolders
     *
     * @return array
     */
    public function getSubFolders()
    {
        $table     = $this->Media->getTable();
        $table_rel = $this->Media->getTable('relations');

        $result = QUI::getDataBase()->fetch(
            array(
                'from'  => array(
                    $table,
                    $table_rel
                ),
                'where' => array(
                    $table_rel . '.parent' => $this->getId(),
                    $table_rel . '.child'  => '`' . $table . '.id`',
                    $table . '.deleted'    => 0,
                    $table . '.type'       => 'folder'
                ),
                'order' => 'name'
            )
        );

        $folders = array();

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

        $result = QUI::getDataBase()->fetch(
            array(
                'select' => array(
                    $table . '.id'
                ),
                'from'   => array(
                    $table,
                    $table_rel
                ),
                'where'  => array(
                    $table_rel . '.parent' => $this->getId(),
                    $table_rel . '.child'  => '`' . $table . '.id`',
                    $table . '.deleted'    => 0,
                    $table . '.name'       => $filename
                ),
                'limit'  => 1
            )
        );

        if (!isset($result[0])) {
            throw new QUI\Exception('File ' . $filename . ' not found', 404);
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

        $result = QUI::getDataBase()->fetch(
            array(
                'select' => array(
                    $table . '.id'
                ),
                'from'   => array(
                    $table,
                    $table_rel
                ),
                'where'  => array(
                    $table_rel . '.parent' => $this->getId(),
                    $table_rel . '.child'  => '`' . $table . '.id`',
                    $table . '.file'       => $this->getPath() . $file
                ),
                'limit'  => 1
            )
        );

        return isset($result[0]) ? true : false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Projects\Media\File::createCache()
     */
    public function createCache()
    {
        if (!$this->getAttribute('active')) {
            return true;
        }

        $cache_dir = CMS_DIR . $this->Media->getCacheDir() . $this->getAttribute('file');

        if (FileUtils::mkdir($cache_dir)) {
            return true;
        }

        throw new QUI\Exception(
            'createCache() Error; Could not create Folder ' . $cache_dir,
            506
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @see QUI\Interfaces\Projects\Media\File::deleteCache()
     */
    public function deleteCache()
    {
        FileUtils::unlink(
            $this->Media->getAttribute('cache_dir') . $this->getAttribute('file')
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
        $new_name = trim($foldername);


        $User = QUI::getUserBySession();
        $dir  = $this->Media->getFullPath() . $this->getPath();

        if (is_dir($dir . $new_name)) {
            // prüfen ob dieser ordner schon als kind existiert
            // wenn nein, muss dieser ordner in der DB angelegt werden

            try {
                $children = $this->getChildByName($new_name);
            } catch (QUI\Exception $Exception) {
                $children = false;
            }

            if ($children) {
                throw new QUI\Exception(
                    'Der Ordner existiert schon ' . $dir . $new_name,
                    701
                );
            }
        }

        FileUtils::mkdir($dir . $new_name);

        $table     = $this->Media->getTable();
        $table_rel = $this->Media->getTable('relations');

        // In die DB legen
        QUI::getDataBase()->insert($table, array(
            'name'      => $new_name,
            'title'     => $new_name,
            'short'     => $new_name,
            'type'      => 'folder',
            'file'      => $this->getAttribute('file') . $new_name . '/',
            'alt'       => $new_name,
            'c_date'    => date('Y-m-d h:i:s'),
            'e_date'    => date('Y-m-d h:i:s'),
            'c_user'    => $User->getId(),
            'e_user'    => $User->getId(),
            'mime_type' => 'folder'
        ));

        $id = QUI::getDataBase()->getPDO()->lastInsertId();

        QUI::getDataBase()->insert($table_rel, array(
            'parent' => $this->getId(),
            'child'  => $id
        ));

        if (is_dir($dir . $new_name)) {
            $Folder = $this->Media->get($id);

            $Folder->setEffects($this->getEffects());
            $Folder->save();

            return $Folder;
        }

        throw new QUI\Exception(
            'Der Ordner konnte nicht erstellt werden',
            507
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
     */
    public function uploadFile($file, $options = self::FILE_OVERWRITE_NONE)
    {
        if (!file_exists($file)) {
            // #locale
            throw new QUI\Exception('Datei existiert nicht.', 404);
        }

        if (is_dir($file)) {
            return $this->uploadFolder($file);
        }

        $fileinfo = FileUtils::getInfo($file);
        $filename = MediaUtils::stripMediaName($fileinfo['basename']);
        $filename = mb_strtolower($filename);

        // svg fix
        if ($fileinfo['mime_type'] == 'text/html') {
            $content = file_get_contents($file);

            if (strpos($content, '<svg') !== false && strpos($content, '</svg>')) {
                file_put_contents(
                    $file,
                    '<?xml version="1.0" encoding="UTF-8"?>' .
                    $content
                );

                $fileinfo = FileUtils::getInfo($file);
            }
        }

        // if no ending, we search for one
        if (!isset($fileinfo['extension']) || empty($fileinfo['extension'])) {
            $filename .= FileUtils::getEndingByMimeType($fileinfo['mime_type']);
        }

        $new_file = $this->getFullPath() . '/' . $filename;
        $new_file = str_replace("//", "/", $new_file);

        // overwrite the file
        if (file_exists($new_file)) {
            if ($options != self::FILE_OVERWRITE_DESTROY
                && $options != self::FILE_OVERWRITE_TRUE
            ) {
                // #locale
                throw new QUI\Exception($filename . ' existiert bereits', 705);
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
                    array('file' => $new_file)
                );

                unlink($new_file);
            }
        }

        // copy the file to the media
        FileUtils::copy($file, $new_file);


        // create the database entry
        $User      = QUI::getUserBySession();
        $table     = $this->Media->getTable();
        $table_rel = $this->Media->getTable('relations');

        $new_file_info = FileUtils::getInfo($new_file);
        $title         = str_replace('_', ' ', $new_file_info['filename']);

        if (empty($new_file_info['filename'])) {
            $new_file_info['filename'] = time();
        }

        $filePath = $this->getAttribute('file') . '/' . $new_file_info['basename'];

        if ($this->getId() == 1) {
            $filePath = $new_file_info['basename'];
        }

        $filePath    = StringUtils::replaceDblSlashes($filePath);
        $imageWidth  = '';
        $imageHeight = '';

        if (isset($new_file_info['width']) && $new_file_info['width']) {
            $imageWidth = $new_file_info['width'];
        }

        if (isset($new_file_info['height']) && $new_file_info['height']) {
            $imageHeight = $new_file_info['height'];
        }

        QUI::getDataBase()->insert($table, array(
            'name'         => $new_file_info['filename'],
            'title'        => $title,
            'short'        => '',
            'file'         => $filePath,
            'alt'          => $title,
            'c_date'       => date('Y-m-d h:i:s'),
            'e_date'       => date('Y-m-d h:i:s'),
            'c_user'       => $User->getId(),
            'e_user'       => $User->getId(),
            'mime_type'    => $new_file_info['mime_type'],
            'image_width'  => $imageWidth,
            'image_height' => $imageHeight,
            'type'         => MediaUtils::getMediaTypeByMimeType($new_file_info['mime_type'])
        ));

        $id = QUI::getDataBase()->getPDO()->lastInsertId();

        QUI::getDataBase()->insert($table_rel, array(
            'parent' => $this->getId(),
            'child'  => $id
        ));

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
                    array(
                        'image_width'  => $resizeData['width'],
                        'image_height' => $resizeData['height'],
                    ),
                    array(
                        'id' => $id
                    )
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
                set_time_limit(1);
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
     */
    protected function uploadFolder($path, $Folder = false)
    {
        $files = FileUtils::readDir($path);

        foreach ($files as $file) {
            // subfolders
            if (is_dir($path . '/' . $file)) {
                $foldername = MediaUtils::stripFolderName($file);

                try {
                    $NewFolder = $this->getChildByName($foldername);
                } catch (QUI\Exception $Exception) {
                    $NewFolder = $this->createFolder($foldername);
                }

                $this->uploadFolder($path . '/' . $file, $NewFolder);
                continue;
            }

            // import files
            if ($Folder) {
                $Folder->uploadFile($path . '/' . $file);
            } else {
                $this->uploadFile($path . '/' . $file);
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
        $children = QUI::getDataBase()->fetch(array(
            'select' => 'id',
            'from'   => $this->Media->getTable(),
            'where'  => array(
                'file' => array(
                    'value' => $this->getAttribute('file'),
                    'type'  => 'LIKE%'
                )
            )
        ));

        $result = array();

        foreach ($children as $child) {
            $result[] = $child['id'];
        }

        return $result;
    }
}
