<?php

namespace QUI\System\Console\Tools;

use QUI;
use QUI\Exception;

use function chdir;
use function system;

use const PHP_BINARY;
use const VAR_DIR;

/**
 * Update QUIQQER to v2
 */
class UpdateToV2 extends QUI\System\Console\Tool
{
    public function __construct()
    {
        $this->setName('quiqqer:upgrade-to-v2')
            ->setDescription('This tool upgrade your QUIQQER v1 to v2. ATTENTION! this cannot be undone!');
    }

    /**
     * @throws Exception
     */
    public function execute(): void
    {
        // check php version
        if (version_compare(PHP_VERSION, '8.1.0', '<=')) {
            throw new Exception('QUIQQER V2 can only be run with PHP 8.1.0 or higher.');
        }

        $this->writeLn(
            'Are you sure you want to upgrade QUIQQER to version 2? Write YES if you want to perform the upgrade:'
        );

        $response = $this->readInput();

        if ($response !== 'YES') {
            return;
        }

        // update composer.phar
        chdir(VAR_DIR . 'composer');

        $this->writeLn('- Download newest composer.phar');
        system('wget https://getcomposer.org/download/latest-2.x/composer.phar -O composer.phar');

        // edit composer json
        $this->writeLn('- Set newest versions for all packages');
        $this->writeLn('');

        // -> set newest packages versions
        $composerFile = 'composer.json';
        $composerData = json_decode(file_get_contents($composerFile), true);

        foreach ($composerData['require'] as $package => $version) {
            $composerData['require'][$package] = $this->getLatestMajorVersion($package);
        }

        $this->writeLn('- Change quiqqer/quiqqer to quiqqer/core in composer.json');
        $composerData['require']['quiqqer/core'] = '2.*';
        unset($composerData['require']['quiqqer/quiqqer']);

        file_put_contents($composerFile, json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // edit etc/conf
        // -> set quiqqer version 2.*
        $this->writeLn('- Change quiqqer/quiqqer to quiqqer/core in etc/conf');
        $Config = new QUI\Config(ETC_DIR . 'conf.ini.php');
        $Config->setValue('globals', 'quiqqer_version', '2.*');
        $Config->save();

        $this->writeLn('- Start upgrade process');
        system(PHP_BINARY . ' composer.phar update');

        // sometimes we need a second one
        system(PHP_BINARY . ' composer.phar update');

        $this->writeLn('- Execute QUIQQER v2 setup');
        chdir(CMS_DIR);
        system('./console update');

        $this->writeLn('- Execute QUIQQER v2 migration');
        system('./console quiqqer:migration-v2');
    }

    public function getLatestMajorVersion($package): string
    {
        $output = shell_exec("composer show " . escapeshellarg($package) . " --all --format=json");
        $packageInfo = json_decode($output, true);

        $versions = array_filter($packageInfo['versions'], function ($version) {
            return preg_match('/^\d+/', $version);
        });

        usort($versions, 'version_compare');

        return '^' . explode('.', end($versions))[0];
    }
}
