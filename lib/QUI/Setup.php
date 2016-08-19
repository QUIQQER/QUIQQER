<?php

/**
 * This file contains \QUI\Setup
 */

namespace QUI;

use QUI;
use QUI\Utils\System\File as SystemFile;

/**
 * QUIQQER Setup
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 * @package com.pcsg.qui
 */
class Setup
{
    /**
     * Excute the QUIQQER Setup
     */
    public static function all()
    {
        // not at phpunit
        if (!isset($_SERVER['argv'])
            || (isset($_SERVER['argv'][0])
                && strpos($_SERVER['argv'][0], 'phpunit') === false)
        ) {
            // nur Super User darf dies
            Permissions\Permission::checkSU(
                QUI::getUserBySession()
            );
        }

        QUI::getSession()->setup();

        // create dirs
        SystemFile::mkdir(USR_DIR);
        SystemFile::mkdir(OPT_DIR);
        SystemFile::mkdir(VAR_DIR);

        self::generateFileLinks();

        // mail queue setup
        Mail\Queue::setup();

        // Gruppen erstellen
        QUI::getGroups()->setup();

        // Rechte setup
        QUI::getPermissionManager()->setup();

        // Benutzer erstellen
        QUI::getUsers()->setup();

        // Cron Setup
        QUI::getMessagesHandler()->setup();

        // Events Setup
        Events\Manager::setup();

        // workspaces
        Workspace\Manager::setup();

        // Upload Manager
        $UploadManager = new Upload\Manager();
        $UploadManager->setup();

        /**
         * header dateien
         */
        $str = "<?php require_once '" . CMS_DIR . "bootstrap.php'; ?>";

        if (file_exists(USR_DIR . 'header.php')) {
            unlink(USR_DIR . 'header.php');
        }

        if (file_exists(OPT_DIR . 'header.php')) {
            unlink(OPT_DIR . 'header.php');
        }

        file_put_contents(USR_DIR . 'header.php', $str);
        file_put_contents(OPT_DIR . 'header.php', $str);

        /**
         * Project Setup
         */
        $projects = Projects\Manager::getProjects(true);

        foreach ($projects as $Project) {
            /* @var $Project \QUI\Projects\Project */
            $Project->setup();

            // Plugin Setup @deprecated
            QUI::getPluginManager()->setup();

            // Media Setup
            // $Project->getMedia()->setup();
        }

        /**
         * composer setup
         */
        $PackageManager = QUI::getPackageManager();
        $packages       = SystemFile::readDir(OPT_DIR);

        $PackageManager->refreshServerList();
//        $Pool = new QUI\Threads\Pool(3, QUI\Threads\Worker::class);

        // first we need all databases
        foreach ($packages as $package) {
            if ($package == 'composer') {
                continue;
            }

            if ($package == 'bin') {
                continue;
            }

            if (!is_dir(OPT_DIR . '/' . $package)) {
                continue;
            }

            $package_dir = OPT_DIR . '/' . $package;
            $list        = SystemFile::readDir($package_dir);

            foreach ($list as $key => $sub) {
                $packageName = $package . '/' . $sub;
                $PackageManager->setup($packageName);
//                $Pool->submit(
//                    new QUI\Threads\Worker($key, function () use ($PackageManager, $packageName) {
//                        $PackageManager->setup($packageName);
//                    })
//                );
            }
        }

//        $Pool->process();
//        QUI\System\Log::writeRecursive($Pool->count());

        // generate translations
        Update::importAllLocaleXMLs();
        Translator::create();

        // generate menu
        Update::importAllMenuXMLs();

        // import permissions
        Update::importAllPermissionsXMLs();

        Rights\Manager::importPermissionsForGroups();


        // setup set the last update date
        QUI::getPackageManager()->setLastUpdateDate();

        // clear cache
        Cache\Manager::clearAll();
    }

    /**
     * Generate the main files,
     * the main link only to the internal quiqqer/quiqqer files
     */
    public static function generateFileLinks()
    {
        $fileHeader
            = '<?php

 /**
  * This file is part of QUIQQER.
  *
  * (c) Henning Leutz <leutz@pcsg.de>
  * Moritz Scholz <scholz@pcsg.de>
  *
  * For the full copyright and license information, please view the LICENSE
  * file that was distributed with this source code.
  *
  *  _______          _________ _______  _______  _______  _______
  * (  ___  )|\     /|\__   __/(  ___  )(  ___  )(  ____ \(  ____ )
  * | (   ) || )   ( |   ) (   | (   ) || (   ) || (    \/| (    )|
  * | |   | || |   | |   | |   | |   | || |   | || (__    | (____)|
  * | |   | || |   | |   | |   | |   | || |   | ||  __)   |     __)
  * | | /\| || |   | |   | |   | | /\| || | /\| || (      | (\ (
  * | (_\ \ || (___) |___) (___| (_\ \ || (_\ \ || (____/\| ) \ \__
  * (____\/_)(_______)\_______/(____\/_)(____\/_)(_______/|/   \__/
  *
  * Generated File via QUIQQER
  * Date: ' . date('Y-m-d H:i:s') . '
  *
  */

';

        $OPT_DIR = OPT_DIR;

        $image     = CMS_DIR . 'image.php';
        $index     = CMS_DIR . 'index.php';
        $quiqqer   = CMS_DIR . 'quiqqer.php';
        $bootstrap = CMS_DIR . 'bootstrap.php';

        // bootstrap
        $bootstrapContent = $fileHeader . "
\$etc_dir = dirname(__FILE__).'/etc/';

if (!file_exists(\$etc_dir.'conf.ini.php')) {
    require_once 'quiqqer.php';
    exit;
}

if (!defined('ETC_DIR')) {
    define('ETC_DIR', \$etc_dir);
}

\$boot = '{$OPT_DIR}quiqqer/quiqqer/bootstrap.php';

if (file_exists(\$boot)) {
    require \$boot;
}
";
        file_put_contents($bootstrap, $bootstrapContent);


        // rest
        file_put_contents(
            $image,
            $fileHeader .
            "define('QUIQQER_SYSTEM',true);".
            "require dirname(__FILE__) .'/bootstrap.php';\n" .
            "require '{$OPT_DIR}quiqqer/quiqqer/image.php';\n"
        );

        file_put_contents(
            $index,
            $fileHeader .
            "define('QUIQQER_SYSTEM',true);".
            "require dirname(__FILE__) .'/bootstrap.php';\n" .
            "require '{$OPT_DIR}quiqqer/quiqqer/index.php';\n"
        );

        file_put_contents(
            $quiqqer,
            $fileHeader .
            "require '{$OPT_DIR}quiqqer/quiqqer/quiqqer.php';\n"
        );
    }
}
