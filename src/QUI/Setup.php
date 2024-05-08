<?php

/**
 * This file contains \QUI\Setup
 */

namespace QUI;

use Exception;
use IntlDateFormatter;
use QUI;
use QUI\Projects\Project;
use QUI\System\License;
use QUI\Utils\System\File as SystemFile;

use function date;
use function file_exists;
use function file_put_contents;
use function is_array;
use function is_dir;
use function system;
use function unlink;

/**
 * QUIQQER Setup
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Setup
{
    /**
     * Execute the QUIQQER Setup
     *
     * @param QUI\Interfaces\System\SystemOutput|null $Output
     *
     * @throws QUI\Exception
     * @throws QUI\ExceptionStack
     * @throws Exception
     */
    public static function all(?QUI\Interfaces\System\SystemOutput $Output = null): void
    {
        if (!$Output) {
            $Output = new QUI\System\VoidOutput();
        }

        QUI\System\Log::write(
            'Execute Setup',
            QUI\System\Log::LEVEL_NOTICE,
            [],
            'setup',
            true
        );

        QUI::getEvents()->fireEvent('setupAllBegin');

        // not at phpunit
        if (
            !isset($_SERVER['argv'])
            || (isset($_SERVER['argv'][0]) && !str_contains($_SERVER['argv'][0], 'phpunit'))
        ) {
            // nur Super User und system user darf dies
            if (!QUI::getUsers()->isSystemUser(QUI::getUserBySession())) {
                Permissions\Permission::checkSU(QUI::getUserBySession());
            }
        }

        $Output->writeLn('> Start Session setup');
        QUI::getSession()->setup();

        $Output->writeLn('> Create directories');
        self::makeDirectories();

        $Output->writeLn('> Generate file links');
        self::generateFileLinks();

        $Output->writeLn('> Execute main setup (groups, users, workspace)');
        self::executeMainSystemSetup();

        $Output->writeLn('> Execute communication setup (mail, messages, events)');
        self::executeCommunicationSetup();

        $Output->writeLn('> Create header files');
        self::makeHeaderFiles();

        $Output->writeLn('> Execute project setups');
        self::executeEachProjectSetup();

        $Output->writeLn('> Execute package setups');
        self::executeEachPackageSetup([], $Output);

        $Output->writeLn('> Import permissions');
        self::importPermissions();

        $Output->writeLn('> Cleanup');
        self::finish();

        QUI::getEvents()->fireEvent('setupAllEnd');
        $Output->writeLn('> Done');
    }

    /**
     * Create the default directories for QUIQQER
     *
     * @throws QUI\Exception
     * @throws QUI\ExceptionStack
     */
    public static function makeDirectories(): void
    {
        QUI::getEvents()->fireEvent('setupMakeDirectoriesBegin');

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

        QUI\Cache\LongTermCache::setup();

        QUI::getEvents()->fireEvent('setupMakeDirectoriesEnd');
    }

    /**
     * Generate the main files,
     * the main link only to the internal quiqqer/core files
     */
    public static function generateFileLinks(): void
    {
        $date = date('Y-m-d H:i:s');

        $fileHeader = <<<EOF
<?php

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
  * Date: {$date}
  * 
  * This file was auto-generated and will be overwritten. Any changes to this file will be lost.
  * 
  * To change the PHP version in the first/shebang line, use the QUIQQER administration: 
  * -> Settings > QUIQQER > System > CLI PHP Command          
  */


EOF;

        $OPT_DIR = OPT_DIR;
        $CMS_DIR = CMS_DIR;
        $SYS_DIR = SYS_DIR;

        $ajax = CMS_DIR . 'ajax.php';
        $ajaxBundler = CMS_DIR . 'ajaxBundler.php';
        $image = CMS_DIR . 'image.php';
        $index = CMS_DIR . 'index.php';
        $quiqqer = CMS_DIR . 'quiqqer.php';
        $bootstrap = CMS_DIR . 'bootstrap.php';
        $console = CMS_DIR . 'console';
        $systemId = License::getSystemId();


        ////////
        // bootstrap.php
        ////////
        $bootstrapContent = <<<EOT
{$fileHeader}
\$etc_dir = dirname(__FILE__).'/etc/';

if (!file_exists(\$etc_dir.'conf.ini.php')) {
    require_once 'quiqqer.php';
    exit;
}

if (!defined('ETC_DIR')) {
    define('ETC_DIR', \$etc_dir);
}

\$boot = '{$OPT_DIR}quiqqer/core/bootstrap.php';

if (file_exists(\$boot)) {
    require \$boot;
}
EOT;
        file_put_contents($bootstrap, $bootstrapContent);


        ////////
        // ajax.php
        ////////
        $content = <<<EOT
{$fileHeader}
// maintenance mode
\$maintenanceFile = dirname(__FILE__).'/maintenance.html';

if (file_exists(\$maintenanceFile)) {
    http_response_code(503);
    header('x-powered-by:');
    header('Retry-After:10');

    echo json_encode([
        'Exception' => [
            'message' => 'Site is under maintenance',
            'code'    => 503
        ]
    ]);
    exit;
}

define('QUIQQER_SYSTEM',true);
require '{$OPT_DIR}quiqqer/core/ajax.php';
EOT;
        file_put_contents($ajax, $content);


        ////////
        // ajaxBundler.php
        ////////
        $content = <<<EOT
{$fileHeader}
// maintenance mode
\$maintenanceFile = dirname(__FILE__).'/maintenance.html';

if (file_exists(\$maintenanceFile)) {
    http_response_code(503);
    header('x-powered-by:');
    header('Retry-After:10');

    echo json_encode([
        'Exception' => [
            'message' => 'Site is under maintenance',
            'code'    => 503
        ]
    ]);
    exit;
}

define('QUIQQER_SYSTEM',true);
require '{$SYS_DIR}ajaxBundler.php';
EOT;

        file_put_contents($ajaxBundler, $content);


        ////////
        // image.php
        ////////
        $content = $fileHeader .
            "define('QUIQQER_SYSTEM',true);" .
            "require dirname(__FILE__) .'/bootstrap.php';\n" .
            "require '{$OPT_DIR}quiqqer/core/image.php';\n";

        file_put_contents($image, $content);

        // index.php
        $content = <<<EOT
{$fileHeader}
// maintenance mode
\$maintenanceFile = dirname(__FILE__).'/maintenance.html';

\$ignoreMaintenance = !empty(\$_REQUEST['systemId']) &&
                        \$_REQUEST['systemId'] === '$systemId' &&
                        !empty(\$_REQUEST['ignoreMaintenance']);

if (!\$ignoreMaintenance && file_exists(\$maintenanceFile)) {
    http_response_code(503);
    header('x-powered-by:');
    header('Retry-After:10');

    echo file_get_contents(\$maintenanceFile);
    exit;
}

define('QUIQQER_SYSTEM',true);
require dirname(__FILE__) .'/bootstrap.php';
require '{$OPT_DIR}quiqqer/core/index.php';
EOT;

        file_put_contents($index, $content);

        ////////
        // quiqqer.php
        // @deprecated for quiqqer v2.0
        // quiqqer.php is not needed anymore -> use ./console
        ////////
        $content = $fileHeader .
            "define('CMS_DIR', '$CMS_DIR');\n" .
            "require '{$OPT_DIR}quiqqer/core/quiqqer.php';\n";

        file_put_contents($quiqqer, $content);


        ////////
        // console
        ////////
        $phpCommand = QUI::conf('globals', 'phpCommand');

        if (empty($phpCommand)) {
            $phpCommand = 'php';
        }

        $content = "#!/usr/bin/env $phpCommand\n" .
            $fileHeader .
            "define('CMS_DIR', '$CMS_DIR');\n" .
            "require '{$OPT_DIR}quiqqer/core/quiqqer.php';\n";

        file_put_contents($console, $content);
        system("chmod +x $console");
    }

    /**
     * Execute the main System Setup
     *
     * - Permissions
     * - Groups
     * - Users
     * - Workspace
     *
     * @throws QUI\Exception
     * @throws QUI\ExceptionStack
     */
    public static function executeMainSystemSetup(): void
    {
        QUI::getEvents()->fireEvent('setupMainSystemBegin');

        // Rechte setup
        QUI::getPermissionManager()->setup();

        // Gruppen erstellen
        QUI::getGroups()->setup();

        // Benutzer erstellen
        QUI::getUsers()->setup();

        // workspaces
        Workspace\Manager::setup();

        QUI::getEvents()->fireEvent('setupMainSystemEnd');
    }

    /**
     * Execute the setup of the main communication classes
     *
     * - Mail
     * - Messages
     * - Editor
     * - Events
     *
     * @throws QUI\Exception
     * @throws QUI\ExceptionStack
     */
    public static function executeCommunicationSetup(): void
    {
        QUI::getEvents()->fireEvent('setupCommunicationBegin');

        // mail queue setup
        Mail\Queue::setup();

        // Cron Setup
        QUI::getMessagesHandler()->setup();

        // WYSIWYG
        QUI\Editor\Manager::setup();

        // Events Setup
        Events\Manager::setup();

        QUI::getEvents()->fireEvent('setupCommunicationEnd');
    }

    /**
     * Create the header files
     *
     * @throws QUI\Exception
     * @throws QUI\ExceptionStack
     */
    public static function makeHeaderFiles(): void
    {
        QUI::getEvents()->fireEvent('setupMakeHeaderFilesBegin');

        $str = "<?php require_once '" . CMS_DIR . "bootstrap.php'; ?>";

        if (file_exists(USR_DIR . 'header.php')) {
            unlink(USR_DIR . 'header.php');
        }

        if (file_exists(OPT_DIR . 'header.php')) {
            unlink(OPT_DIR . 'header.php');
        }

        file_put_contents(USR_DIR . 'header.php', $str);
        file_put_contents(OPT_DIR . 'header.php', $str);

        QUI::getEvents()->fireEvent('setupMakeHeaderFilesEnd');
    }

    /**
     * Execute for each project the setup
     *
     * @param array $setupOptions - options for the package setup [executePackageSetup]
     */
    public static function executeEachProjectSetup(array $setupOptions = []): void
    {
        $projects = Projects\Manager::getProjects(true);

        if (!isset($setupOptions['executePackagesSetup'])) {
            $setupOptions['executePackagesSetup'] = false;
        }

        /* @var $Project Project */
        foreach ($projects as $Project) {
            try {
                $Project->setup($setupOptions);
            } catch (Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }
    }

    /**
     * Execute for each package the setup
     *
     * @param array $setupOptions - options for the package setup
     * @param QUI\Interfaces\System\SystemOutput|null $Output
     *
     * @throws QUI\Exception
     * @throws QUI\ExceptionStack
     * @throws Exception
     */
    public static function executeEachPackageSetup(
        array $setupOptions = [],
        ?QUI\Interfaces\System\SystemOutput $Output = null
    ): void {
        if (!$Output) {
            $Output = new QUI\System\VoidOutput();
        }

        QUI::getEvents()->fireEvent('setupPackageSetupBegin');

        $PackageManager = QUI::getPackageManager();
        $packages = SystemFile::readDir(OPT_DIR);

        $PackageManager->refreshServerList();

        if (!is_array($setupOptions)) {
            $setupOptions = [];
        }

        if (!isset($setupOptions['localePublish'])) {
            $setupOptions['localePublish'] = false;
        }

        QUI\Cache\Manager::$noClearing = true;

        // first we need all databases
        $Formatter = QUI::getLocale()->getDateFormatter(
            IntlDateFormatter::NONE,
            IntlDateFormatter::MEDIUM
        );

        foreach ($packages as $package) {
            if ($package == 'composer') {
                continue;
            }

            if ($package == 'bin') {
                continue;
            }

            if (!is_dir(OPT_DIR . $package)) {
                continue;
            }

            $list = SystemFile::readDir(OPT_DIR . $package);

            foreach ($list as $sub) {
                $packageName = $package . '/' . $sub;

                try {
                    $Package = $PackageManager->getInstalledPackage($packageName);
                } catch (QUI\Exception) {
                    continue;
                }

                if (!$Package->isQuiqqerPackage() && !$Package->isQuiqqerAsset()) {
                    continue;
                }

                $Output->writeLn(
                    '>> ' . $Formatter->format(time()) . ' - run setup for package ' . $packageName
                );

                $Package->setup($setupOptions);
            }
        }

        QUI\Cache\Manager::$noClearing = false;
        QUI\Cache\Manager::clearCompleteQuiqqerCache();

        QUI::getEvents()->fireEvent('setupPackageSetupEnd');
    }

    /**
     * Import all important permissions
     */
    public static function importPermissions(): void
    {
        QUI\Permissions\Manager::importPermissionsForGroups();
    }

    /**
     * Finish the setup
     *
     * - set last update
     * - clear the cache
     *
     * @throws QUI\Exception
     */
    public static function finish(): void
    {
        QUI\Translator::create();

        // clear cache
        QUI\Cache\Manager::clearCompleteQuiqqerCache();
    }
}
