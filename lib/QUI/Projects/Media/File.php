<?php

/**
 * This file contains the \QUI\Projects\Media\File
 */

namespace QUI\Projects\Media;

use Exception;
use QUI;
use QUI\Projects\Media;
use QUI\Utils\System\File as QUIFile;

use function dirname;
use function file_exists;
use function in_array;
use function md5_file;
use function sha1_file;

/**
 * A media file
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class File extends Item implements QUI\Interfaces\Projects\Media\File
{
    /**
     * (non-PHPdoc)
     *
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     *
     * @see QUI\Interfaces\Projects\Media\File::createCache
     */
    public function createCache()
    {
        if (Media::$globalDisableMediaCacheCreation) {
            return false;
        }

        if (!$this->getAttribute('active')) {
            return false;
        }

        $this->checkPermission('quiqqer.projects.media.view');

        $Media = $this->Media;
        /* @var $Media Media */

        $mdir = CMS_DIR . $Media->getPath();
        $cdir = CMS_DIR . $Media->getCacheDir();
        $file = $this->getAttribute('file');

        $original = $mdir . $file;
        $cacheFile = $cdir . $file;


        if (
            $this->hasPermission('quiqqer.projects.media.view') &&
            $this->hasPermission('quiqqer.projects.media.view', QUI::getUsers()->getNobody()) === false
        ) {
            return $original;
        }


        $extension = QUI\Utils\StringHelper::pathinfo($original, PATHINFO_EXTENSION);

        if (in_array($extension, Utils::getWhiteListForNoMediaCache())) {
            QUIFile::unlink($cacheFile);

            return $original;
        }

        // Nur wenn Extension in Whitelist ist dann Cache machen
        if (file_exists($cacheFile)) {
            return $cacheFile;
        }

        // Cachefolder erstellen
        QUIFile::mkdir(dirname($cacheFile));

        try {
            QUIFile::copy($original, $cacheFile);
        } catch (QUI\Exception) {
            // nothing
        }

        return $cacheFile;
    }

    /**
     * Deletes the cache for the current media file.
     *
     * The method deletes the cache file corresponding to the current media file. The media object stores
     * the cache directory path, and the file name is retrieved from the current media file's attribute 'file'.
     * The cache file is then unlinked using the QUIFile::unlink() method.
     *
     * @throws Exception if the cache file cannot be deleted.
     */
    public function deleteCache()
    {
        $media = $this->Media;
        $cacheDirectory = CMS_DIR . $media->getCacheDir();
        $fileName = $this->getAttribute('file');

        QUIFile::unlink($cacheDirectory . $fileName);
    }

    /**
     * Generates the MD5 hash for the file associated with this instance.
     *
     * @return void *@throws QUI\Exception If the file associated with this instance does not exist.
     *
     * void
     * @throws QUI\Database\Exception
     * @throws QUI\Exception
     */
    public function generateMD5()
    {
        if (!file_exists($this->getFullPath())) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.file.not.found', [
                    'file' => $this->getAttribute('file')
                ]),
                ErrorCodes::FILE_NOT_FOUND
            );
        }

        $md5 = md5_file($this->getFullPath());

        $this->setAttribute('md5hash', $md5);

        QUI::getDataBase()->update(
            $this->Media->getTable(),
            ['md5hash' => $md5],
            ['id' => $this->getId()]
        );
    }

    /**
     * Generate the SHA1 file hash and set it to the Database and to the Object
     *
     * @throws QUI\Exception
     */
    public function generateSHA1()
    {
        if (!file_exists($this->getFullPath())) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.file.not.found', [
                    'file' => $this->getAttribute('file')
                ]),
                ErrorCodes::FILE_NOT_FOUND
            );
        }

        $sha1 = sha1_file($this->getFullPath());

        $this->setAttribute('sha1hash', $sha1);

        QUI::getDataBase()->update(
            $this->Media->getTable(),
            ['sha1hash' => $sha1],
            ['id' => $this->getId()]
        );
    }
}
