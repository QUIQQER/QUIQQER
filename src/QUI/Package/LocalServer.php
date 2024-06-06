<?php

/**
 * This file contains QUI\Package\LocalServer
 */

namespace QUI\Package;

use Exception;
use QUI;
use QUI\Utils\System\File;
use ZipArchive;

use function chdir;
use function file_exists;
use function file_get_contents;
use function is_dir;
use function json_decode;
use function json_encode;
use function pathinfo;
use function rtrim;

use const JSON_PRETTY_PRINT;

/**
 * Class LocalServer
 */
class LocalServer extends QUI\Utils\Singleton
{
    /**
     * activate the locale repository,
     * if the repository is not in the server list, the repository would be added
     *
     * @throws QUI\Exception
     */
    public function activate(): void
    {
        $serverDir = $this->getDir();
        $Packages = QUI::getPackageManager();

        $Packages->addServer($serverDir, [
            "type" => "artifact"
        ]);

        $Packages->setServerStatus($serverDir, 1);
    }

    public function getDir(): string
    {
        $updatePath = QUI::conf('update', 'updatePath');

        if (!empty($updatePath) && is_dir($updatePath)) {
            return rtrim($updatePath, '/') . '/';
        }

        $localeUpdateFolder = VAR_DIR . 'update/packages/';

        if (!is_dir($localeUpdateFolder)) {
            File::mkdir($localeUpdateFolder);
        }

        return $localeUpdateFolder;
    }

    /**
     * deactivate the locale repository,
     * @throws QUI\Exception
     */
    public function deactivate(): void
    {
        $serverDir = $this->getDir();
        $Packages = QUI::getPackageManager();
        $Packages->removeServer($serverDir);
    }

    /**
     * Move a file to the locale server
     *
     * @param $file
     * @throws QUI\Exception
     */
    public function uploadPackage($file): void
    {
        $serverDir = $this->getDir();
        $info = File::getInfo($file);
        $filename = $info['filename'] . '.' . $info['extension'];

        if ($info['mime_type'] !== 'application/zip') {
            throw new QUI\Exception('File is not a Package Archive');
        }

        if (!file_exists($file)) {
            throw new QUI\Exception('Package Archive File not found');
        }

        if (file_exists($serverDir . $filename)) {
            unlink($serverDir . $filename);
        }

        File::move($file, $serverDir . $filename);

        // add master / dev version as repository
        $version = false;

        if (str_contains($filename, '-dev-master-')) {
            $version = 'dev-master';
        } elseif (str_contains($filename, '-dev-dev-')) {
            $version = 'dev-dev';
        }

        if (!$version) {
            return;
        }

        $Zip = new ZipArchive();

        if (!$Zip->open($serverDir . $filename)) {
            return;
        }

        $composerJson = $Zip->getFromName('composer.json');
        $composerJson = json_decode($composerJson, true);

        if (empty($composerJson['version'])) {
            $composerJson['version'] = $version;

            $Zip->addFromString('composer.json', json_encode($composerJson, JSON_PRETTY_PRINT));
        }

        $Zip->close();

        QUI::getPackageManager()->addServer(
            $serverDir . $filename,
            [
                'type' => 'package',
                'name' => $composerJson['name'],
                'version' => $version
            ]
        );
    }

    /**
     * Return all not installed packages in the local server
     */
    public function getNotInstalledPackage(): array
    {
        $result = [];
        $packages = $this->getPackageList();

        foreach ($packages as $package) {
            try {
                QUI::getPackage($package['name']);
            } catch (QUI\Exception) {
                $result[] = $package;
            }
        }

        return $result;
    }

    /**
     * Return the package list in the locale server
     */
    public function getPackageList(): array
    {
        $dir = $this->getDir();

        if (!is_dir($dir)) {
            return [];
        }

        $files = File::readDir($dir);
        $result = [];

        chdir($dir);

        foreach ($files as $package) {
            try {
                $composerJson = file_get_contents(
                    "zip://$package#composer.json"
                );
            } catch (Exception) {
                // maybe gitlab package?
                try {
                    $packageName = pathinfo($package);
                    $composerJson = file_get_contents(
                        "zip://$package#{$packageName['filename']}/composer.json"
                    );
                } catch (Exception $Exception) {
                    QUI\System\Log::addDebug($Exception->getMessage());
                    continue;
                }
            }

            if (empty($composerJson)) {
                continue;
            }

            $composerJson = json_decode($composerJson, true);

            if (!isset($composerJson['name'])) {
                continue;
            }

            if (is_dir(OPT_DIR . $composerJson['name'])) {
                continue;
            }

            // consider dev master versions
            if (!isset($composerJson['version']) && str_contains($package, '-dev-master-')) {
                $composerJson['version'] = 'dev-master';
            } elseif (!isset($composerJson['version']) && str_contains($package, '-dev-dev-')) {
                $composerJson['version'] = 'dev-dev';
            }

            $result[] = $composerJson;
        }

        return $result;
    }
}
