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
 * @licence For copyright and license information, please view the /README.md
 */
class Media extends QUI\QDOM
{
    /**
     * internal project object
     *
     * @var \QUI\Projects\Project
     */
    protected $Project;

    /**
     * internal child cache
     *
     * @var array
     */
    protected $children = [];

    /**
     * @var null
     */
    protected static $mediaPermissions = null;

    /**
     * This flag indicates if the creation of media item/folder cache is disabled
     * when createCache() is called.
     *
     * This should only be set to true if a lot of media items are created (e.g. in a mass import).
     *
     * @var bool
     */
    public static $globalDisableMediaCacheCreation = false;

    /**
     * constructor
     *
     * @param \QUI\Projects\Project $Project
     */
    public function __construct(Project $Project)
    {
        $this->Project = $Project;
    }

    /**
     * Use media permissions? Media permissions are available?
     *
     * @return bool
     */
    public static function useMediaPermissions()
    {
        if (self::$mediaPermissions === null) {
            $mediaPermissions = QUI::conf('permissions', 'media');
            $mediaPermissions = (int)$mediaPermissions;
            $mediaPermissions = (bool)$mediaPermissions;

            self::$mediaPermissions = $mediaPermissions;
        }

        return self::$mediaPermissions;
    }

    /**
     * Return the project of the media
     *
     * @return \QUI\Projects\Project
     */
    public function getProject()
    {
        return $this->Project;
    }

    /**
     * Return the main media directory
     * Here are all files located
     * (without the CMS_DIR)
     *
     * @return string - path to the directory, relative to the system
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
     * @return string - path to the directory
     */
    public function getFullPath()
    {
        return CMS_DIR.$this->getPath();
    }

    /**
     * Return the cache directory from the media
     *
     * @return string - path to the directory, relative to the system
     */
    public function getCacheDir()
    {
        return 'media/cache/'.$this->getProject()->getAttribute('name').'/';
    }

    /**
     * Return the complete cache path from the media
     * (with the CMS_DIR)
     *
     * @return string - path to the directory, relative to the system
     */
    public function getFullCachePath()
    {
        return CMS_DIR.$this->getCacheDir();
    }

    /**
     * Return the DataBase table name
     *
     * @param string|boolean $type - (optional) standard=false; other options: relations
     *
     * @return string
     */
    public function getTable($type = false)
    {
        if ($type == 'relations') {
            return QUI::getDBTableName($this->Project->getAttribute('name').'_media_relations');
        }

        return QUI::getDBTableName($this->Project->getAttribute('name').'_media');
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
     * Return the Placeholder of the media
     *
     * @return QUI\Projects\Media\Image|false
     */
    public function getPlaceholderImage()
    {
        $Project = $this->getProject();

        if ($Project->getConfig('placeholder')) {
            try {
                return QUI\Projects\Media\Utils::getImageByUrl(
                    $Project->getConfig('placeholder')
                );
            } catch (QUI\Exception $Exception) {
            }
        }

        return false;
    }

    /**
     * Return the Logo of the media / project
     *
     * @return string
     */
    public function getLogo()
    {
        $Project = $this->getProject();

        if ($Project->getConfig('logo')) {
            try {
                $Image = QUI\Projects\Media\Utils::getImageByUrl(
                    $Project->getConfig('logo')
                );

                return $Image->getUrl(true);
            } catch (QUI\Exception $Exception) {
            }
        }

        return $this->getPlaceholder();
    }

    /**
     * Return the Logo image object of the media
     *
     * @return QUI\Projects\Media\Image|string
     */
    public function getLogoImage()
    {
        $Project = $this->getProject();

        if ($Project->getConfig('logo')) {
            try {
                return QUI\Projects\Media\Utils::getImageByUrl(
                    $Project->getConfig('logo')
                );
            } catch (QUI\Exception $Exception) {
            }
        }

        return $this->getPlaceholderImage();
    }

    /**
     * Return the ImageManager of the Media
     *
     * @return ImageManager
     */
    public function getImageManager()
    {
        $Project = $this->getProject();
        $library = $Project->getConfig('media_image_library');

        switch ($library) {
            case '':
            case 'imagick':
            case 'gd':
                break;

            default:
                $library = '';
        }

//        if (\class_exists('Imagick') && ($library === '' || $library === 'imagick')) {
//            return new ImageManager(['driver' => 'imagick']);
//        }

        return new ImageManager(['driver' => 'gd']);
    }

    /**
     * Setup for a media table
     *
     * @throws QUI\Exception
     * @throws QUI\Database\Exception
     */
    public function setup()
    {
        /**
         * Media Center
         */
        $table    = $this->getTable();
        $DataBase = QUI::getDataBase();

        $DataBase->table()->addColumn($table, [
            'id'            => 'bigint(20) NOT NULL',
            'name'          => 'varchar(200) NOT NULL',
            'title'         => 'text',
            'short'         => 'text',
            'alt'           => 'text',
            'type'          => 'varchar(32) default NULL',
            'active'        => 'tinyint(1) NOT NULL DEFAULT 0',
            'deleted'       => 'tinyint(1) NOT NULL DEFAULT 0',
            'c_date'        => 'timestamp NULL default NULL',
            'e_date'        => 'timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
            'file'          => 'text',
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
            'pathHistory'   => 'text',
            'hidden'        => 'int(1) default 0',
            'pathHash'      => 'varchar(32) NOT NULL',
        ]);

        $DataBase->table()->setPrimaryKey($table, 'id');
        $DataBase->table()->setIndex($table, 'id');
        $DataBase->table()->setAutoIncrement($table, 'id');

        $DataBase->table()->setIndex($table, 'name');
        $DataBase->table()->setIndex($table, 'type');
        $DataBase->table()->setIndex($table, 'active');
        $DataBase->table()->setIndex($table, 'deleted');
        $DataBase->table()->setIndex($table, 'e_date');
        $DataBase->table()->setIndex($table, 'order');
        $DataBase->table()->setIndex($table, 'hidden');
        $DataBase->table()->setIndex($table, 'pathHash');

        try {
            $DataBase->execSQL('UPDATE '.$table.' SET pathHash = MD5(file)');
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        // remove index (patch)
        $removeIndex = ['c_date', 'c_user', 'e_user', 'md5hash', 'sha1hash'];

        foreach ($removeIndex as $index) {
            if ($DataBase->table()->issetIndex($table, $index)) {
                try {
                    $DataBase->fetchSQL(
                        'ALTER TABLE `'.$table.'` DROP INDEX `'.$index.'`;'
                    );
                } catch (\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);
                }
            }
        }

        // create first site -> id 1 if not exist
        $firstChildResult = $DataBase->fetch([
            'from'  => $table,
            'where' => [
                'id' => 1
            ],
            'limit' => 1
        ]);

        if (!isset($firstChildResult[0])) {
            $DataBase->insert($table, [
                'id'     => 1,
                'name'   => 'Media',
                'title'  => 'Media',
                'c_date' => \date('Y-m-d H:i:s'),
                'c_user' => QUI::getUserBySession()->getId(),
                'type'   => 'folder'
            ]);
        } else {
            // check if id 1 is a folder, id 1 MUST BE a folder
            if ($firstChildResult[0]['type'] != 'folder') {
                $DataBase->update(
                    $table,
                    ['type' => 'folder'],
                    ['id' => 1]
                );
            }
        }

        // Media Relations
        $table = $this->getTable('relations');

        $DataBase->table()->addColumn($table, [
            'parent' => 'bigint(20) NOT NULL',
            'child'  => 'bigint(20) NOT NULL'
        ]);

        $DataBase->table()->setIndex($table, 'parent');
        $DataBase->table()->setIndex($table, 'child');

        // multilingual patch

        $table = $this->getTable();

        // check if patch needed
        $result = QUI::getDataBase()->fetch([
            'from'  => $table,
            'where' => [
                'id' => 1
            ]
        ]);

        $title = $result[0]['title'];
        $title = \json_decode($title, true);

        if (\is_array($title)) {
            return;
        }

        // patch is needed
        $result = QUI::getDataBase()->fetch([
            'from' => $table
        ]);

        $languages = QUI::availableLanguages();

        $updateEntry = function ($type, $data, $table) use ($languages) {
            $value     = $data[$type];
            $valueJSON = \json_decode($value, true);

            if (\is_array($valueJSON)) {
                return;
            }

            $newData = [];

            foreach ($languages as $language) {
                $newData[$language] = $value;
            }

            QUI::getDataBase()->update($table, [
                $type => \json_encode($newData)
            ], [
                'id' => $data['id']
            ]);
        };

        foreach ($result as $entry) {
            $updateEntry('title', $entry, $table);
            $updateEntry('short', $entry, $table);
            $updateEntry('alt', $entry, $table);
        }
    }

    /**
     * methods for usage
     */

    /**
     * Delete the complete media cache
     *
     * @throws QUI\Exception
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
     * @return QUI\Projects\Media\Folder
     *
     * @throws QUI\Exception
     */
    public function firstChild()
    {
        return $this->get(1);
    }

    /**
     * Return a media object
     *
     * @param integer $id - media id
     *
     * @return \QUI\Projects\Media\Item|\QUI\Projects\Media\Image|\QUI\Projects\Media\File|\QUI\Projects\Media\Folder
     * @throws \QUI\Exception
     */
    public function get($id)
    {
        $id = (int)$id;

        if (isset($this->children[$id])) {
            return $this->children[$id];
        }

        // If the RAM is full objects was once empty
        if (QUI\Utils\System::memUsageToHigh()) {
            $this->children = [];
        }

        $result = QUI::getDataBase()->fetch([
            'from'  => $this->getTable(),
            'where' => [
                'id' => $id
            ],
            'limit' => 1
        ]);

        if (!isset($result[0])) {
            throw new QUI\Exception('ID '.$id.' not found', 404);
        }


        $this->children[$id] = $this->parseResultToItem($result[0]);

        return $this->children[$id];
    }

    /**
     * Return the wanted children ids
     *
     * @param array $params - DataBase params
     *
     * @return array id list
     */
    public function getChildrenIds($params = [])
    {
        $params['select'] = 'id';
        $params['from']   = $this->getTable();

        try {
            $result = QUI::getDataBase()->fetch($params);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());

            return [];
        }

        $ids = [];

        foreach ($result as $entry) {
            $ids[] = $entry['id'];
        }

        return $ids;
    }

    /**
     * Return a file from its file oath
     *
     * @param string $filepath
     *
     * @return QUI\Projects\Media\Item
     * @throws QUI\Exception
     */
    public function getChildByPath($filepath)
    {
        $cache = $this->getCacheDir().'filePathIds/'.md5($filepath);

        try {
            $id = (int)QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception $Exception) {
            $table = $this->getTable();

            $result = QUI::getDataBase()->fetch([
                'select' => [$table.'.id'],
                'from'   => [$table],
                'where'  => [
                    $table.'.deleted' => 0,
                    $table.'.file'    => $filepath
                ],
                'limit'  => 1
            ]);

            if (!isset($result[0])) {
                throw new QUI\Exception('File '.$filepath.' not found', 404);
            }

            $id = (int)$result[0]['id'];
        }

        return $this->get($id);
    }

    /**
     * Replace a file with another
     *
     * @param integer $id
     * @param string $file - Path to the new file
     *
     * @return QUI\Interfaces\Projects\Media\File
     * @throws \QUI\Exception
     */
    public function replace($id, $file)
    {
        if (!\file_exists($file)) {
            throw new QUI\Exception('File could not be found', 404);
        }

        // use direct db not the objects, because
        // if file is not ok you can replace the file though
        $result = QUI::getDataBase()->fetch([
            'from'  => $this->getTable(),
            'where' => [
                'id' => $id
            ],
            'limit' => 1
        ]);


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
            $new_file  = $Parent->getPath().$name;
            $real_file = $Parent->getFullPath().$name;
        } else {
            $new_file  = $result[0]['file'];
            $real_file = $this->getFullPath().$result[0]['file'];
        }

        QUI::getDataBase()->update(
            $this->getTable(),
            [
                'file'         => $new_file,
                'name'         => $name,
                'mime_type'    => $info['mime_type'],
                'image_height' => $info['height'],
                'image_width'  => $info['width'],
                'type'         => QUI\Projects\Media\Utils::getMediaTypeByMimeType(
                    $info['mime_type']
                )
            ],
            ['id' => $id]
        );

        QUI\Utils\System\File::move($file, $real_file);

        if (isset($this->children[$id])) {
            unset($this->children[$id]);
        }

        $File = $this->get($id);
        $File->deleteCache();

        return $File;
    }

    /**
     * Return the parent id
     *
     * @param integer $id
     *
     * @return integer|false
     */
    public function getParentIdFrom($id)
    {
        $id = (int)$id;

        if ($id <= 1) {
            return false;
        }

        try {
            $result = QUI::getDataBase()->fetch([
                'select' => 'parent',
                'from'   => $this->getTable('relations'),
                'where'  => [
                    'child' => $id
                ],
                'limit'  => 1
            ]);
        } catch (QUI\Exception $Exception) {
            return false;
        }

        if (\is_array($result) && isset($result[0])) {
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
    public function parseResultToItem($result)
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
