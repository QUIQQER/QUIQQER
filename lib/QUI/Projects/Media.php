<?php

/**
 * This file contains the \QUI\Projects\Media
 */

namespace QUI\Projects;

use QUI;
use Intervention\Image\ImageManager;

/**
 * Media Manager for a project
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.projects.media
 * @licence For copyright and license information, please view the /README.md
 */
class Media extends QUI\QDOM
{
    /**
     * internal project object
     *
     * @var \QUI\Projects\Project
     */
    protected $_Project;

    /**
     * internal child cache
     *
     * @var array
     */
    protected $_children = array();

    /**
     * constructor
     *
     * @param \QUI\Projects\Project $Project
     */
    public function __construct(Project $Project)
    {
        $this->_Project = $Project;
    }

    /**
     * Return the project of the media
     *
     * @return \QUI\Projects\Project
     */
    public function getProject()
    {
        return $this->_Project;
    }

    /**
     * Return the main media directory
     * Here are all files located
     * (without the CMS_DIR)
     *
     * @return String - path to the directory, relative to the system
     */
    public function getPath()
    {
        return 'media/sites/'.$this->getProject()->getAttribute('name').'/';
    }

    /**
     * Return the main media directory
     * Here are all files located
     * (with the CMS_DIR)
     *
     * @return String - path to the directory
     */
    public function getFullPath()
    {
        return CMS_DIR.$this->getPath();
    }

    /**
     * Return the cache directory from the media
     *
     * @return String - path to the directory, relative to the system
     */
    public function getCacheDir()
    {
        return 'media/cache/'.$this->getProject()->getAttribute('name').'/';
    }

    /**
     * Return the complete cache path from the media
     * (with the CMS_DIR)
     *
     * @return String - path to the directory, relative to the system
     */
    public function getFullCachePath()
    {
        return CMS_DIR.$this->getCacheDir();
    }

    /**
     * Return the DataBase table name
     *
     * @param String|Bool $type - (optional) standard=false; other options: relations
     *
     * @return string
     */
    public function getTable($type = false)
    {
        if ($type == 'relations') {
            return $this->_Project->getAttribute('name').'_media_relations';
        }

        return $this->_Project->getAttribute('name').'_media';
    }

    /**
     * Return the Placeholder of the media
     *
     * @return string
     */
    public function getPlaceholder()
    {
        $Project = $this->getProject();

        if ($Project->getConfig('placeholder')) {

            try {
                $Image = QUI\Projects\Media\Utils::getImageByUrl(
                    $Project->getConfig('placeholder')
                );

                return $Image->getUrl(true);

            } catch (QUI\Exception $Exception) {

            }
        }

        return URL_BIN_DIR.'images/Q.png';
    }

    /**
     * Return the ImageManager of the Media
     *
     * @return ImageManager
     */
    public function getImageManager()
    {
        if (class_exists('Imagick')) {
            return new ImageManager(array('driver' => 'imagick'));
        }

        return new ImageManager(array('driver' => 'gd'));
    }

    /**
     * Setup for a media table
     */
    public function setup()
    {
        /**
         * Media Center
         */
        $table = $this->getTable();

        $DataBase = QUI::getDataBase();
        $DataBase->Table()->appendFields($table, array(
            'id'            => 'bigint(20) NOT NULL',
            'name'          => 'varchar(200) NOT NULL',
            'title'         => 'tinytext',
            'short'         => 'text',
            'type'          => 'varchar(32) default NULL',
            'active'        => 'tinyint(1) NOT NULL',
            'deleted'       => 'tinyint(1) NOT NULL',
            'c_date'        => 'timestamp NULL default NULL',
            'e_date'        => 'timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
            'file'          => 'text',
            'alt'           => 'text',
            'mime_type'     => 'text',
            'image_height'  => 'int(6) default NULL',
            'image_width'   => 'int(6) default NULL',
            'image_effects' => 'text',
            'c_user'        => 'int(11) default NULL',
            'e_user'        => 'int(11) default NULL',
            'rate_users'    => 'text',
            'rate_count'    => 'float default NULL',
            'md5hash'       => 'varchar(32)',
            'sha1hash'      => 'varchar(40)',
            'priority'      => 'int(6) default NULL',
            'order'         => 'varchar(32) default NULL',
        ));

        $DataBase->Table()->setIndex($table, 'id');
        $DataBase->Table()->setAutoIncrement($table, 'id');

        // create first site -> id 1 if not exist
        $firstChildResult = $DataBase->fetch(array(
            'from'  => $table,
            'where' => array(
                'id' => 1
            ),
            'limit' => 1
        ));

        if (!isset($firstChildResult[0])) {
            $DataBase->insert($table, array(
                'id'     => 1,
                'name'   => 'Media',
                'title'  => 'Media',
                'c_date' => date('Y-m-d H:i:s'),
                'c_user' => QUI::getUserBySession()->getId(),
                'type'   => 'folder'
            ));

        } else {
            // check if id 1 is a folder, id 1 MUST BE a folder
            if ($firstChildResult[0]['type'] != 'folder') {
                $DataBase->update($table, array(
                    'type' => 'folder'
                ), array(
                    'id' => 1
                ));
            }
        }

        // Media Relations
        $table = $this->getTable('relations');

        $DataBase->Table()->appendFields($table, array(
            'parent' => 'bigint(20) NOT NULL',
            'child'  => 'bigint(20) NOT NULL'
        ));

        $DataBase->Table()->setIndex($table, 'parent');
        $DataBase->Table()->setIndex($table, 'child');
    }

    /**
     * methods for usage
     */

    /**
     * Delete the complete media cache
     */
    public function clearCache()
    {
        $dir = $this->getFullCachePath();

        QUI::getTemp()->moveToTemp($dir);
        QUI\Utils\System\File::mkdir($dir);
    }

    /**
     * Return the first child in the media
     *
     * @return \QUI\Projects\Media\Folder
     */
    public function firstChild()
    {
        return $this->get(1);
    }

    /**
     * Return a media object
     *
     * @param Integer $id - media id
     *
     * @return \QUI\Projects\Media\Item|\QUI\Projects\Media\Image|\QUI\Projects\Media\File
     * @throws \QUI\Exception
     */
    public function get($id)
    {
        $id = (int)$id;

        if (isset($this->_children[$id])) {
            return $this->_children[$id];
        }

        // If the RAM is full objects was once empty
        if (QUI\Utils\System::memUsageToHigh()) {
            $this->_children = array();
        }

        $result = QUI::getDataBase()->fetch(array(
            'from'  => $this->getTable(),
            'where' => array(
                'id' => $id
            ),
            'limit' => 1
        ));

        if (!isset($result[0])) {
            throw new QUI\Exception('ID '.$id.' not found', 404);
        }


        $this->_children[$id] = $this->_parseResultToItem($result[0]);

        return $this->_children[$id];
    }

    /**
     * Return the wanted children ids
     *
     * @param array $params - DataBase params
     *
     * @return array id list
     */
    public function getChildrenIds($params = array())
    {
        $params['select'] = 'id';
        $params['from'] = $this->getTable();

        $result = QUI::getDataBase()->fetch($params);
        $ids = array();

        foreach ($result as $entry) {
            $ids[] = $entry['id'];
        }

        return $ids;
    }

    /**
     * Return a file from its file oath
     *
     * @param String $filepath
     *
     * @return QUI\Interfaces\Projects\Media\File
     * @throws QUI\Exception
     */
    public function getChildByPath($filepath)
    {
        $table = $this->getTable();
        $table_rel = $this->getTable('relations');

        $result = QUI::getDataBase()->fetch(array(
            'select' => array(
                $table.'.id'
            ),
            'from'   => array(
                $table,
                $table_rel
            ),
            'where'  => array(
                $table.'.deleted' => 0,
                $table.'.file'    => $filepath
            ),
            'limit'  => 1
        ));

        if (!isset($result[0])) {
            throw new QUI\Exception('File '.$filepath.' not found', 404);
        }

        return $this->get((int)$result[0]['id']);
    }

    /**
     * Replace a file with another
     *
     * @param Integer $id
     * @param String  $file - Path to the new file
     *
     * @return QUI\Interfaces\Projects\Media\File
     * @throws \QUI\Exception
     */
    public function replace($id, $file)
    {
        if (!file_exists($file)) {
            throw new QUI\Exception('File could not be found', 404);
        }

        // use direct db not the objects, because
        // if file is not ok you can replace the file though
        $result = QUI::getDataBase()->fetch(array(
            'from'  => $this->getTable(),
            'where' => array(
                'id' => $id
            ),
            'limit' => 1
        ));


        if (!isset($result[0])) {
            throw new QUI\Exception('File entry not found', 404);
        }

        if ($result[0]['type'] == 'folder') {
            throw new QUI\Exception('Only Files can be replaced', 403);
        }

        $data = $result[0];

        $name = $data['name'];
        $info = QUI\Utils\System\File::getInfo($file);

        if ($info['mime_type'] != $data['mime_type']) {
            $name = $info['basename'];
        }

        /**
         * get the parent and check if a file like the replace file exist
         */
        $parentid = $this->getParentIdFrom($data['id']);

        if (!$parentid) {
            throw new QUI\Exception('No Parent found.', 404);
        }

        /* @var $Parent \QUI\Projects\Media\Folder */
        $Parent = $this->get($parentid);

        if ($data['name'] != $name && $Parent->childWithNameExists($name)) {
            throw new QUI\Exception(
                'A file with the name '.$name.' already exist.',
                403
            );
        }

        // delete the file
        if (isset($data['file']) && !empty($data['file'])) {
            QUI\Utils\System\File::unlink(
                $this->getFullPath().$data['file']
            );
        }

        if ($data['name'] != $name) {
            $new_file = $Parent->getPath().$name;
            $real_file = $Parent->getFullPath().$name;

        } else {
            $new_file = $result[0]['file'];
            $real_file = $this->getFullPath().$result[0]['file'];
        }

        QUI::getDataBase()->update(
            $this->getTable(),
            array(
                'file'         => $new_file,
                'name'         => $name,
                'mime_type'    => $info['mime_type'],
                'image_height' => $info['height'],
                'image_width'  => $info['width'],
                'type'         => QUI\Projects\Media\Utils::getMediaTypeByMimeType(
                    $info['mime_type']
                )
            ),
            array('id' => $id)
        );

        QUI\Utils\System\File::move($file, $real_file);

        if (isset($this->_children[$id])) {
            unset($this->_children[$id]);
        }

        $File = $this->get($id);
        $File->deleteCache();

        return $File;
    }

    /**
     * Return the parent id
     *
     * @param Integer $id
     *
     * @return Integer|false
     */
    public function getParentIdFrom($id)
    {
        $id = (int)$id;

        if ($id <= 1) {
            return false;
        }

        $result = QUI::getDataBase()->fetch(array(
            'select' => 'parent',
            'from'   => $this->getTable('relations'),
            'where'  => array(
                'child' => $id
            ),
            'limit'  => 1
        ));

        if (is_array($result) && isset($result[0])) {
            return (int)$result[0]['parent'];
        }

        return false;
    }

    /**
     * Returns the Media Trash
     *
     * @return \QUI\Projects\Media\Trash
     */
    public function getTrash()
    {
        return new Media\Trash($this);
    }

    /**
     * Parse a database entry to a media object
     *
     * @param array $result
     *
     * @return \QUI\Projects\Media\Item
     */
    public function _parseResultToItem($result)
    {
        switch ($result['type']) {
            case "image":
                return new Media\Image($result, $this);
                break;

            case "folder":
                return new Media\Folder($result, $this);
                break;
        }

        return new Media\File($result, $this);
    }
}
