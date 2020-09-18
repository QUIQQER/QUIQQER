<?php

/**
 * This file contains the \QUI\Temp class
 */

namespace QUI;

use QUI;
use QUI\Utils\System\File as QUIFile;
use QUI\Utils\Security\Orthos;

/**
 * Temp managed the temp folder
 * It creates temp folders and delete it, provides methods for tempfiles / folders
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Temp
{
    /**
     * @var string
     */
    protected $folder;

    /**
     * constructor
     *
     * @param string $tempfolder - opath to the tempfolder
     */
    public function __construct($tempfolder)
    {
        $this->folder = \rtrim($tempfolder, '/').'/';

        if (!\is_dir($this->folder)) {
            QUIFile::mkdir($this->folder);
        }
    }

    /**
     * Create a temp folder and return the path to it
     *
     * @param string|boolean $name - (optional), if no name, a folder would be created with a random name
     *
     * @return string - Path to the folder
     * @throws QUI\Exception
     */
    public function createFolder($name = false)
    {
        if (!empty($name)) {
            $newFolder = $this->folder.$name.'/';
            $newFolder = Orthos::clearPath($newFolder);

            if (\is_dir($newFolder)) {
                return $newFolder;
            }

            QUIFile::mkdir($this->folder);
            QUIFile::mkdir($newFolder);

            if (!\is_dir($newFolder)) {
                throw new QUI\Exception(
                    'Folder '.$newFolder.' could not be created'
                );
            }

            if (!\realpath($newFolder)) {
                throw new QUI\Exception(
                    'Folder '.$newFolder.' could not be created'
                );
            }

            return $newFolder;
        }


        // create a var_dir temp folder
        do {
            $folder = $this->folder.\str_replace([' ', '.'], '', \microtime()).'/';
        } while (\file_exists($folder));

        QUIFile::mkdir($folder);

        return $folder;
    }

    /**
     * Clear the Temp folder
     */
    public function clear()
    {
        if (\system('rm -rf '.$this->folder)) {
            QUIFile::mkdir($this->folder);

            return;
        }

        // system is not allowed
        QUIFile::deleteDir($this->folder);
        QUIFile::mkdir($this->folder);
    }

    /**
     * Move a folder or a file to the temp folder
     * so it can be deleted
     *
     * @param string $folder - Path to file or folder
     * @throws QUI\Exception
     */
    public function moveToTemp($folder)
    {
        if (!\file_exists($folder)) {
            return;
        }

        QUIFile::move(
            $folder,
            self::createFolder().\md5($folder)
        );
    }
}
