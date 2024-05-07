<?php

/**
 * This file contains \QUI\System\Checks\Health
 */

namespace QUI\System\Checks;

use QUI;
use QUI\Exception;

use function array_merge;
use function asort;
use function explode;
use function file;
use function file_exists;
use function is_dir;
use function is_writable;
use function md5_file;
use function shell_exec;
use function str_replace;
use function trim;

/**
 * Healthcheck
 * Checks the system or a package to health
 * Are all files correct?
 *
 * the check uses the checklist.md5, the checklist.md5 contains all md5 hashes of all files
 *
 * @author www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Health
{
    const STATUS_NOT_FOUND = 0;
    const STATUS_OK = 1;
    const STATUS_ERROR = -1;

    /**
     * System Healthcheck
     * Return the result of the sytem healthcheck
     *
     * @return array
     */
    public static function systemCheck()
    {
        $File = new QUI\Utils\System\File();
        $md5 = CMS_DIR . 'checklist.md5';

        $bin_dir = str_replace(CMS_DIR, '', BIN_DIR);
        $lib_dir = str_replace(CMS_DIR, '', LIB_DIR);
        $sys_dir = str_replace(CMS_DIR, '', SYS_DIR);

        $binList = $File->readDirRecursiv(BIN_DIR, true);
        $libList = $File->readDirRecursiv(LIB_DIR, true);
        $sysList = $File->readDirRecursiv(SYS_DIR, true);

        foreach ($binList as $key => $val) {
            $binList[$key] = $bin_dir . $val;
        }

        foreach ($libList as $key => $val) {
            $libList[$key] = $lib_dir . $val;
        }

        foreach ($sysList as $key => $val) {
            $sysList[$key] = $sys_dir . $val;
        }


        $list = array_merge($binList, $libList, $sysList);

        return self::checkArray($md5, $list, CMS_DIR);
    }

    /**
     * compare the folder with an file list array
     *
     * @param string $md5Checkfile - path to the md5 checkfile
     * @param array $fileList - file list array
     * @param string $dir - directory
     *
     * @return array
     */
    public static function checkArray($md5Checkfile, $fileList, $dir)
    {
        $md5Entries = file($md5CheckFile);
        $md5List = [];

        $result = [];

        // explode the md5 list
        foreach ($md5Entries as $line) {
            $parts = explode('  ', $line);

            $md5List[trim($parts[1])] = trim($parts[0]);
        }


        // check files in the system
        foreach ($fileList as $file) {
            if (!isset($md5List[$file])) {
                $result[$file] = self::STATUS_NOT_FOUND;
                continue;
            }

            $md5 = $md5List[$file];

            if (md5_file($dir . $file) != $md5) {
                $result[$file] = self::STATUS_ERROR;
                continue;
            }

            $result[$file] = self::STATUS_OK;
        }


        // check if all files from the md5 exist
        foreach ($md5List as $file => $md5) {
            if (!isset($result[$file])) {
                $result[$file] = self::STATUS_NOT_FOUND;
            }
        }

        asort($result);

        return $result;
    }

    /**
     * Package Healthcheck
     * Return the result of the package healthcheck
     *
     * @param string $plugin
     *
     * @return array
     */
    public static function packageCheck($plugin)
    {
        $dir = OPT_DIR . $plugin;
        $md5 = $dir . '/checklist.md5';

        return self::check($md5, $dir);
    }

    /**
     * compare the folder with the checkfile
     *
     * @param string $md5Checkfile - path to the md5 checkfile
     * @param string $dir - dir name, path to the dir
     *
     * @return array
     * @throws Exception
     */
    public static function check($md5Checkfile, $dir)
    {
        if (!file_exists($md5CheckFile)) {
            throw new Exception(
                'Check file not exist. Could not check the directory'
            );
        }

        if (!is_dir($dir)) {
            throw new Exception(
                'Could not read directory.'
            );
        }

        $File = new QUI\Utils\System\File();
        $dirList = $File->readDirRecursiv($dir);

        return self::checkArray($md5Checkfile, $dirList, $dir);
    }

    /**
     * check if all files are writable
     *
     * @throws \QUI\Exception
     */
    public static function checkWritable()
    {
        // check files
        $md5hashFile = CMS_DIR . 'checklist.md5';

        if (!file_exists($md5hashFile)) {
            throw new Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.system.health.checklist.md5.not.found'
                )
            );
        }

        $lines = file($md5hashFile);
        $notWritable = [];

        foreach ($lines as $line) {
            $line = explode(' ', $line);

            if (!is_writable(CMS_DIR . $line[1])) {
                $notWritable[] = CMS_DIR . $line[1];
            }
        }

        if (!empty($notWritable)) {
            throw new Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.system.health.not.writable'
                )
            );
        }

        // check folders
        $result = shell_exec('find ' . CMS_DIR . ' -not -path \'*/\.*\' -type d');
        $lines = explode("\n", trim($result));

        foreach ($lines as $line) {
            if (!is_writable($line)) {
                $notWritable[] = $line;
            }
        }

        if (!empty($notWritable)) {
            throw new Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.system.health.not.writable'
                )
            );
        }
    }
}
