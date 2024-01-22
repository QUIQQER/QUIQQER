<?php

/**
 * This file contains QUI\System\Backup
 */

namespace QUI\System;

use QUI\Utils\System;
use QUI\Utils\System\File;
use QUI\Utils\Uuid;

use function implode;
use function is_dir;
use function shell_exec;
use function str_replace;

use const ETC_DIR;
use const VAR_DIR;

class Backup
{
    /**
     * @return false|string
     */
    public static function createEtcBackup()
    {
        $uuid = Uuid::get();
        $cpIsEnabled = System::isSystemFunctionCallable('cp');
        $backupDir = VAR_DIR . '/backup/etc/' . $uuid . '/';

        File::mkdir($backupDir);

        if ($cpIsEnabled === false) {
            return false;
        }

        shell_exec('cp -r ' . ETC_DIR . '* ' . $backupDir . '.');

        return $uuid;
    }

    public static function deleteEtcBackup($folder)
    {
        $backupFolder = VAR_DIR . 'backup/etc/' . $folder;

        if (is_dir($backupFolder)) {
            File::deleteDir($backupFolder);
        }
    }

    public static function diff($backupFolder): string
    {
        $diffIsEnabled = System::isSystemFunctionCallable('diff');

        if ($diffIsEnabled === false) {
            return '';
        }

        $backupFolder = VAR_DIR . 'backup/etc/' . $backupFolder;

        $params = [
            '-ar ',
            '--minimal ',
            '--suppress-common-lines ',
            "'--color=always' ",
            "'--exclude=last_update.ini.php' ",
        ];

        // "--ignore-trailing-space" does not exist on Darwin/macOS and FreeBSD, it's enabled by default
        if (!stristr(php_uname('s'), 'Darwin') && !stristr(php_uname('s'), 'FreeBSD')) {
            $params[] = '--ignore-trailing-space ';
        }

        $command = 'diff ' . implode('', $params) . ' ' . $backupFolder . ' ' . ETC_DIR;
        $result = shell_exec($command);

        $result = str_replace($backupFolder . '/', 'Old config: ', $result);
        $result = str_replace(ETC_DIR, " - New config: ", $result);
        $result = str_replace('\ No newline at end of file', "", $result);
        $result = str_replace('diff', "\nFile: ", $result);
        $result = str_replace($params, '', $result);
        $result = str_replace("''", '', $result);
        $result = ltrim($result);

        return $result;
    }
}
