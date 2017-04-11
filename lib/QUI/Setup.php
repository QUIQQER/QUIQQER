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

        // look at media trash
        $mediaTrash = VAR_DIR . 'media/trash';

        if (!is_dir($mediaTrash)) {
            SystemFile::mkdir($mediaTrash);

            $folders = SystemFile::readDir(VAR_DIR . 'media');

            foreach ($folders as $folder) {
                if ($folder === 'trash') {
                    continue;
                }

                SystemFile::move(
                    VAR_DIR . 'media/' . $folder,
                    $mediaTrash . '/' . $folder
                );
            }
        }

        self::generateFileLinks();

        // mail queue setup
        Mail\Queue::setup();

        // Rechte setup
        QUI::getPermissionManager()->setup();

        // Gruppen erstellen
        QUI::getGroups()->setup();

        // Benutzer erstellen
        QUI::getUsers()->setup();

        // Cron Setup
        QUI::getMessagesHandler()->setup();

        // WYSIWYG
        QUI\Editor\Manager::setup();

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

        /* @var $Project \QUI\Projects\Project */
        foreach ($projects as $Project) {
            try {
                $Project->setup();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        /**
         * composer setup
         */
        $PackageManager = QUI::getPackageManager();
        $packages       = SystemFile::readDir(OPT_DIR);

        $PackageManager->refreshServerList();

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

            $list = SystemFile::readDir(OPT_DIR . '/' . $package);

            foreach ($list as $key => $sub) {
                $packageName = $package . '/' . $sub;
                $PackageManager->setup($packageName);
            }
        }

        QUI\Permissions\Manager::importPermissionsForGroups();

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
        $CMS_DIR = CMS_DIR;

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


        // image.php
        $content = $fileHeader .
                   "define('QUIQQER_SYSTEM',true);" .
                   "require dirname(__FILE__) .'/bootstrap.php';\n" .
                   "require '{$OPT_DIR}quiqqer/quiqqer/image.php';\n";

        file_put_contents($image, $content);

        // index.php
        $content = $fileHeader .
                   "define('QUIQQER_SYSTEM',true);" .
                   "require dirname(__FILE__) .'/bootstrap.php';\n" .
                   "require '{$OPT_DIR}quiqqer/quiqqer/index.php';\n";

        file_put_contents($index, $content);

        // quiqqer.php
        $content = $fileHeader .
                   "define('CMS_DIR', '{$CMS_DIR}');\n" .
                   "require '{$OPT_DIR}quiqqer/quiqqer/quiqqer.php';\n";

        file_put_contents($quiqqer, $content);
    }
}
