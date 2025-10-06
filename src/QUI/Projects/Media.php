<?php

/**
 * This file contains the \QUI\Projects\Media
 */

namespace QUI\Projects;

use Exception;
use Intervention\Image\Constraint;
use Intervention\Image\ImageManager;
use QUI;
use QUI\Projects\Media\Utils;
use QUI\Utils\System\File as FileUtils;

use function class_exists;
use function date;
use function file_exists;
use function is_array;
use function json_decode;
use function json_encode;
use function md5;
use function preg_replace;
use function str_replace;
use function trim;

/**
 * Media Manager for a project
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Media extends QUI\QDOM
{
    /**
     * This flag indicates if the creation of media item/folder cache is disabled
     * when createCache() is called.
     *
     * This should only be set to true if a lot of media items are created (e.g. in a mass import).
     */
    public static bool $globalDisableMediaCacheCreation = false;

    protected static mixed $mediaPermissions = null;

    /**
     * internal child cache
     */
    protected array $children = [];

    public function __construct(
        protected Project $Project
    ) {
    }

    /**
     * Use media permissions? Media permissions are available?
     */
    public static function useMediaPermissions(): ?bool
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
     * Return the Logo of the media / project
     */
    public function getLogo(): string
    {
        $Project = $this->getProject();

        if ($Project->getConfig('logo')) {
            try {
                $Image = Utils::getImageByUrl(
                    $Project->getConfig('logo')
                );

                return $Image->getUrl(true);
            } catch (QUI\Exception) {
            }
        }

        return $this->getPlaceholder();
    }

    /**
     * Return the project of the media
     */
    public function getProject(): Project
    {
        return $this->Project;
    }

    /**
     * Return the Placeholder of the media
     */
    public function getPlaceholder(): string
    {
        $Project = $this->getProject();

        if ($Project->getConfig('placeholder')) {
            try {
                $Image = Utils::getImageByUrl(
                    $Project->getConfig('placeholder')
                );

                return $Image->getUrl(true);
            } catch (QUI\Exception) {
            }
        }

        return URL_BIN_DIR . 'images/Q.png';
    }

    /**
     * Return the Logo image object of the media
     */
    public function getLogoImage(): QUI\Projects\Media\Image|null
    {
        $Project = $this->getProject();

        if ($Project->getConfig('logo')) {
            try {
                return Utils::getImageByUrl(
                    $Project->getConfig('logo')
                );
            } catch (QUI\Exception) {
            }
        }

        return $this->getPlaceholderImage();
    }

    /**
     * Return the Placeholder of the media
     *
     * @return QUI\Projects\Media\Image|null
     */
    public function getPlaceholderImage(): QUI\Projects\Media\Image|null
    {
        $Project = $this->getProject();

        if ($Project->getConfig('placeholder')) {
            try {
                return Utils::getImageByUrl(
                    $Project->getConfig('placeholder')
                );
            } catch (QUI\Exception) {
            }
        }

        return null;
    }

    /**
     * Setup for a media table
     *
     * @throws QUI\Exception
     * @throws QUI\Database\Exception
     */
    public function setup(): void
    {
        /**
         * Media Center
         */
        $table = $this->getTable();
        $DataBase = QUI::getDataBase();

        $DataBase->table()->addColumn($table, [
            'id' => 'bigint(20) NOT NULL',
            'name' => 'varchar(200) NOT NULL',
            'title' => 'text',
            'short' => 'text',
            'alt' => 'text',
            'type' => 'varchar(32) default NULL',
            'active' => 'tinyint(1) NOT NULL DEFAULT 0',
            'deleted' => 'tinyint(1) NOT NULL DEFAULT 0',
            'c_date' => 'timestamp NULL default NULL',
            'e_date' => 'timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
            'file' => 'text',
            'mime_type' => 'text',
            'image_height' => 'int(6) default NULL',
            'image_width' => 'int(6) default NULL',
            'image_effects' => 'text',
            'c_user' => 'varchar(50) default NULL',
            'e_user' => 'varchar(50) default NULL',
            'rate_users' => 'text',
            'rate_count' => 'float default NULL',
            'md5hash' => 'varchar(32)',
            'sha1hash' => 'varchar(40)',
            'priority' => 'int(6) default NULL',
            'order' => 'varchar(32) default NULL',
            'pathHistory' => 'text',
            'hidden' => 'int(1) default 0',
            'pathHash' => 'varchar(32) NOT NULL',
            'extra' => 'text NULL',
            'external' => 'text NULL',
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
            $DataBase->execSQL('UPDATE ' . $table . ' SET pathHash = MD5(file)');
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        // remove index (patch)
        $removeIndex = ['c_date', 'c_user', 'e_user', 'md5hash', 'sha1hash'];

        foreach ($removeIndex as $index) {
            if ($DataBase->table()->issetIndex($table, $index)) {
                try {
                    $DataBase->fetchSQL(
                        'ALTER TABLE `' . $table . '` DROP INDEX `' . $index . '`;'
                    );
                } catch (Exception $Exception) {
                    QUI\System\Log::writeException($Exception);
                }
            }
        }

        // create first site -> id 1 if not exist
        $firstChildResult = $DataBase->fetch([
            'from' => $table,
            'where' => [
                'id' => 1
            ],
            'limit' => 1
        ]);

        if (!isset($firstChildResult[0])) {
            $DataBase->insert($table, [
                'id' => 1,
                'name' => 'Media',
                'title' => 'Media',
                'c_date' => date('Y-m-d H:i:s'),
                'c_user' => QUI::getUserBySession()->getUUID(),
                'type' => 'folder',
                'pathHash' => md5('')
            ]);
        } elseif ($firstChildResult[0]['type'] != 'folder') {
            // check if id 1 is a folder, id 1 MUST BE a folder
            $DataBase->update(
                $table,
                ['type' => 'folder'],
                ['id' => 1]
            );
        }

        // Media Relations
        $table = $this->getTable('relations');

        $DataBase->table()->addColumn($table, [
            'parent' => 'bigint(20) NOT NULL',
            'child' => 'bigint(20) NOT NULL'
        ]);

        $DataBase->table()->setIndex($table, 'parent');
        $DataBase->table()->setIndex($table, 'child');

        // multilingual patch

        $table = $this->getTable();

        // check if patch needed
        $result = QUI::getDataBase()->fetch([
            'from' => $table,
            'where' => [
                'id' => 1
            ]
        ]);

        $title = $result[0]['title'];
        $title = json_decode($title, true);

        if (is_array($title)) {
            return;
        }

        // patch is needed
        $result = QUI::getDataBase()->fetch([
            'from' => $table
        ]);

        $languages = QUI::availableLanguages();

        $updateEntry = static function ($type, array $data, $table) use ($languages): void {
            $value = $data[$type];
            $valueJSON = json_decode($value, true);

            if (is_array($valueJSON)) {
                return;
            }

            $newData = [];

            foreach ($languages as $language) {
                $newData[$language] = $value;
            }

            QUI::getDataBase()->update($table, [
                $type => json_encode($newData)
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
     * Return the DataBase table name
     *
     * @param boolean|string $type - (optional) standard=false; other options: relations
     *
     * @return string
     */
    public function getTable(bool|string $type = false): string
    {
        if ($type == 'relations') {
            return QUI::getDBTableName($this->Project->getAttribute('name') . '_media_relations');
        }

        return QUI::getDBTableName($this->Project->getAttribute('name') . '_media');
    }

    /**
     * Delete the complete media cache
     *
     * @throws QUI\Exception
     */
    public function clearCache(): void
    {
        $dir = $this->getFullCachePath();

        QUI::getTemp()->moveToTemp($dir);
        QUI\Utils\System\File::mkdir($dir);
    }

    /**
     * Return the complete cache path from the media
     * (with the CMS_DIR)
     *
     * @return string - path to the directory, relative to the system
     */
    public function getFullCachePath(): string
    {
        return CMS_DIR . $this->getCacheDir();
    }

    /**
     * Return the cache directory from the media
     *
     * @return string - path to the directory, relative to the system
     */
    public function getCacheDir(): string
    {
        return 'media/cache/' . $this->getProject()->getAttribute('name') . '/';
    }

    /**
     * Return the first child in the media
     *
     * @throws QUI\Database\Exception
     * @throws QUI\Exception
     */
    public function firstChild(): QUI\Projects\Media\Folder
    {
        $Folder = $this->get(1);

        if ($Folder instanceof QUI\Projects\Media\Folder) {
            return $Folder;
        }

        return new QUI\Projects\Media\Folder([
            'id' => 1
        ], $this);
    }

    /**
     * Return a media object
     *
     * @param integer $id - media id
     *
     * @throws QUI\Database\Exception
     * @throws QUI\Exception
     */
    public function get(int $id): QUI\Interfaces\Projects\Media\File
    {
        if (isset($this->children[$id])) {
            return $this->children[$id];
        }

        // If the RAM is full objects was once empty
        if (QUI\Utils\System::memUsageToHigh()) {
            $this->children = [];
        }

        $result = QUI::getDataBase()->fetch([
            'from' => $this->getTable(),
            'where' => [
                'id' => $id
            ],
            'limit' => 1
        ]);

        if (!isset($result[0])) {
            throw new QUI\Exception('ID ' . $id . ' not found', 404);
        }

        if (QUI::isFrontend() && $result[0]['deleted']) {
            throw new QUI\Exception('ID ' . $id . ' not found', 404);
        }


        $this->children[$id] = $this->parseResultToItem($result[0]);

        return $this->children[$id];
    }

    /**
     * methods for usage
     */
    /**
     * Parse a database entry to a media object
     */
    public function parseResultToItem(array $result): QUI\Interfaces\Projects\Media\File
    {
        return match ($result['type']) {
            "image" => new Media\Image($result, $this),
            "folder" => new Media\Folder($result, $this),
            "video" => new Media\Video($result, $this),
            default => new Media\File($result, $this),
        };
    }

    /**
     * Return the wanted children ids
     *
     * @param array $params - DataBase params
     *
     * @return array id list
     */
    public function getChildrenIds(array $params = []): array
    {
        $params['select'] = 'id';
        $params['from'] = $this->getTable();

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
     * @throws QUI\Database\Exception
     * @throws QUI\Exception
     */
    public function getChildByPath(string $filepath): QUI\Interfaces\Projects\Media\File
    {
        $cache = $this->getCacheDir() . 'filePathIds/' . md5($filepath);

        try {
            $id = QUI\Cache\LongTermCache::get($cache);
        } catch (QUI\Exception) {
            $table = $this->getTable();

            $result = QUI::getDataBase()->fetch([
                'select' => [$table . '.id'],
                'from' => [$table],
                'where' => [
                    $table . '.deleted' => 0,
                    $table . '.file' => $filepath
                ],
                'limit' => 1
            ]);

            if (!isset($result[0])) {
                throw new QUI\Exception('File ' . $filepath . ' not found', 404);
            }

            $id = (int)$result[0]['id'];

            QUI\Cache\LongTermCache::set($cache, $id);
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
     *
     * @throws QUI\Exception
     */
    public function replace(int $id, string $file): QUI\Interfaces\Projects\Media\File
    {
        if (!file_exists($file)) {
            throw new QUI\Exception('File could not be found', 404);
        }

        // use direct db not the objects, because
        // if file is not ok you can replace the file though
        $result = QUI::getDataBase()->fetch([
            'from' => $this->getTable(),
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
            $name = trim($name, "_ \t\n\r\0\x0B"); // Trim the default characters and underscores
            $name = str_replace(' ', '_', $name);
            $name = preg_replace('#(_){2,}#', "$1", $name);
            $name = Utils::stripMediaName($name);
        }

        /**
         * get the parent and check, if a file, like the replaced file, exists
         */
        $parentId = $this->getParentIdFrom($data['id']);

        if (!$parentId) {
            throw new QUI\Exception('No Parent found.', 404);
        }

        $Parent = $this->get($parentId);

        if (
            $Parent instanceof QUI\Projects\Media\Folder
            && $data['name'] !== $name
            && $Parent->childWithNameExists($name)
        ) {
            throw new QUI\Exception(
                'A file with the name ' . $name . ' already exist.',
                403
            );
        }

        // check file size if needed and if the file is an image
        $imageType = Utils::getMediaTypeByMimeType($info['mime_type']);

        if ($imageType === 'image') {
            $maxConfigSize = (int)$this->getProject()->getConfig('media_maxUploadSize');
            $info = FileUtils::getInfo($file, ['imagesize' => true]);

            // create image
            $Image = $this->getImageManager()->make($file);
            $sizes = QUI\Utils\Math::resize($info['width'], $info['height'], $maxConfigSize);

            $Image->resize(
                $sizes[1],
                $sizes[2],
                static function ($Constraint): void {
                    /* @var $Constraint Constraint; */
                    $Constraint->aspectRatio();
                    $Constraint->upsize();
                }
            );

            $Image->save($file);
            $info = QUI\Utils\System\File::getInfo($file);
        }

        // delete the file
        if (!empty($data['file'])) {
            QUI\Utils\System\File::unlink(
                $this->getFullPath() . $data['file']
            );
        }

        if ($data['name'] != $name) {
            $new_file = $Parent->getPath() . $name;
            $real_file = $Parent->getFullPath() . $name;
        } else {
            $new_file = $result[0]['file'];
            $real_file = $this->getFullPath() . $result[0]['file'];
        }

        $imageHeight = null;
        $imageWidth = null;

        if (!empty($info['height'])) {
            $imageHeight = $info['height'];
        }

        if (!empty($info['width'])) {
            $imageWidth = $info['width'];
        }

        QUI::getDataBase()->update(
            $this->getTable(),
            [
                'file' => $new_file,
                'name' => $name,
                'mime_type' => $info['mime_type'],
                'image_height' => $imageHeight,
                'image_width' => $imageWidth,
                'type' => $imageType
            ],
            ['id' => $id]
        );

        QUI\Utils\System\File::move($file, $real_file);

        if (isset($this->children[$id])) {
            unset($this->children[$id]);
        }

        $File = $this->get($id);
        $File->deleteCache();

        QUI::getEvents()->fireEvent('mediaReplace', [$this, $File]);

        return $File;
    }

    /**
     * Return the parent id
     */
    public function getParentIdFrom(int $id): bool|int
    {
        if ($id <= 1) {
            return false;
        }

        try {
            $result = QUI::getDataBase()->fetch([
                'select' => 'parent',
                'from' => $this->getTable('relations'),
                'where' => [
                    'child' => $id
                ],
                'limit' => 1
            ]);
        } catch (QUI\Exception) {
            return false;
        }

        if (isset($result[0])) {
            return (int)$result[0]['parent'];
        }

        return false;
    }

    /**
     * Return the ImageManager of the Media
     */
    public function getImageManager(): ImageManager
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

        if (class_exists('Imagick') && ($library === '' || $library === 'imagick')) {
            return new ImageManager(['driver' => 'imagick']);
        }

        return new ImageManager(['driver' => 'gd']);
    }

    /**
     * Return the main media directory
     * Here are all files located
     * (with the CMS_DIR)
     *
     * @return string - path to the directory
     */
    public function getFullPath(): string
    {
        return CMS_DIR . $this->getPath();
    }

    /**
     * Return the main media directory
     * Here are all files located
     * (without the CMS_DIR)
     *
     * @return string - path to the directory, relative to the system
     */
    public function getPath(): string
    {
        return 'media/sites/' . $this->getProject()->getAttribute('name') . '/';
    }

    /**
     * Returns the Media Trash
     */
    public function getTrash(): Media\Trash
    {
        return new Media\Trash($this);
    }

    /**
     * Updates all external images
     */
    public function updateExternalImages(): void
    {
        try {
            $result = QUI::getDataBase()->fetch([
                'from' => $this->getTable(),
                'where' => [
                    'external' => [
                        'type' => 'NOT LIKE',
                        'value' => ''
                    ]
                ]
            ]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return;
        }

        foreach ($result as $item) {
            try {
                $Image = $this->get($item['id']);

                if ($Image instanceof QUI\Projects\Media\Image) {
                    $Image->updateExternalImage();
                }
            } catch (QUI\Exception) {
            }
        }
    }
}
