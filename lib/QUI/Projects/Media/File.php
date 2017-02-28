<?php

/**
 * This file contains the \QUI\Projects\Media\File
 */

namespace QUI\Projects\Media;

use QUI;
use QUI\Utils\System\File as QUIFile;

/**
 * A media file
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.projects.media
 * @licence For copyright and license information, please view the /README.md
 */
class File extends Item implements QUI\Interfaces\Projects\Media\File
{
    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Projects\Media\File::createCache()
     */
    public function createCache()
    {
        if (!$this->getAttribute('active')) {
            return false;
        }

        $WHITE_LIST_EXTENSION = array(
            'pdf',
            'txt',
            'xml',
            'doc',
            'pdt',
            'xls',
            'csv',
            'txt',
            'swf',
            'flv',
            'mp3',
            'ogg',
            'wav',
            'mpeg',
            'avi',
            'mpg',
            'divx',
            'mov',
            'wmv',
            'zip',
            'rar',
            '7z',
            'gzip',
            'tar',
            'tgz',
            'ace',
            'psd'
        );

        $Media = $this->Media;
        /* @var $Media \QUI\Projects\Media */

        $mdir = CMS_DIR.$Media->getPath();
        $cdir = CMS_DIR.$Media->getCacheDir();
        $file = $this->getAttribute('file');

        $original = $mdir.$file;
        $cachefile = $cdir.$file;

        $extension = QUI\Utils\StringHelper::pathinfo($original, PATHINFO_EXTENSION);

        if (!in_array($extension, $WHITE_LIST_EXTENSION)) {
            QUIFile::unlink($cachefile);

            return $original;
        }

        // Nur wenn Extension in Whitelist ist dann Cache machen
        if (file_exists($cachefile)) {
            return $cachefile;
        }

        // Cachefolder erstellen
        $this->getParent()->createCache();

        try {
            QUIFile::copy($original, $cachefile);
        } catch (QUI\Exception $Exception) {
            // nothing
        }

        return $cachefile;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Projects\Media\File::deleteCache()
     */
    public function deleteCache()
    {
        $Media = $this->Media;

        $cdir = CMS_DIR.$Media->getCacheDir();
        $file = $this->getAttribute('file');

        QUIFile::unlink($cdir.$file);
    }

    /**
     * Generate the MD5 file hash and set it to the Database and to the Object
     */
    public function generateMD5()
    {
        if (!file_exists($this->getFullPath())) {
            throw new QUI\Exception(
                QUI::getLocale()
                   ->get('quiqqer/system', 'exception.file.not.found', array(
                       'file' => $this->getAttribute('file')
                   )),
                404
            );
        }

        $md5 = md5_file($this->getFullPath());

        $this->setAttribute('md5hash', $md5);

        QUI::getDataBase()->update(
            $this->Media->getTable(),
            array('md5hash' => $md5),
            array('id' => $this->getId())
        );
    }

    /**
     * Generate the SHA1 file hash and set it to the Database and to the Object
     */
    public function generateSHA1()
    {
        if (!file_exists($this->getFullPath())) {
            throw new QUI\Exception(
                QUI::getLocale()
                   ->get('quiqqer/system', 'exception.file.not.found', array(
                       'file' => $this->getAttribute('file')
                   )),
                404
            );
        }

        $sha1 = sha1_file($this->getFullPath());

        $this->setAttribute('sha1hash', $sha1);

        QUI::getDataBase()->update(
            $this->Media->getTable(),
            array('sha1hash' => $sha1),
            array('id' => $this->getId())
        );
    }
}
