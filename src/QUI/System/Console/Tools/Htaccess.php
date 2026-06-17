<?php

/**
 * \QUI\System\Console\Tools\Htaccess
 */

namespace QUI\System\Console\Tools;

use QUI;

use function count;
use function date;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function ltrim;
use function parse_ini_file;
use function str_replace;
use function trim;

/**
 * Generate the system htaccess file
 */
class Htaccess extends QUI\System\Console\Tool
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->systemTool = true;

        $this->setName('quiqqer:htaccess')
            ->setDescription('Generate the htaccess File.');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute(): void
    {
        $this->writeLn('Generating HTACCESS ...');

        $htaccessBackupFile = VAR_DIR . 'backup/htaccess_' . date('Y-m-d__H_i_s');
        $htaccessFile = CMS_DIR . '.htaccess';

        # Create the custom htaccess file if it does not exist
        if (!file_exists(ETC_DIR . 'htaccess.custom.php')) {
            file_put_contents(ETC_DIR . 'htaccess.custom.php', "#<?php exit; ?>");
        }

        $config = parse_ini_file(ETC_DIR . "conf.ini.php", true);

        if (!isset($config['webserver']['type'])) {
            $this->writeLn('Webservertype is not configured!', "red");

            return;
        }

        //
        // generate backup
        //
        if (file_exists($htaccessFile)) {
            file_put_contents(
                $htaccessBackupFile,
                file_get_contents($htaccessFile)
            );

            $this->writeLn('You can find a .htaccess Backup File at:');
            $this->writeLn($htaccessBackupFile);
        } else {
            $this->writeLn(
                'No .htaccess File found. Could not create a backup.',
                'red'
            );
        }

        $this->resetColor();


        //
        // Generate htaccess file
        //
        $htaccessContent
            = '
#  _______          _________ _______  _______  _______  _______
# (  ___  )|\     /|\__   __/(  ___  )(  ___  )(  ____ \(  ____ )
# | (   ) || )   ( |   ) (   | (   ) || (   ) || (    \/| (    )|
# | |   | || |   | |   | |   | |   | || |   | || (__    | (____)|
# | |   | || |   | |   | |   | |   | || |   | ||  __)   |     __)
# | | /\| || |   | |   | |   | | /\| || | /\| || (      | (\ (
# | (_\ \ || (___) |___) (___| (_\ \ || (_\ \ || (____/\| ) \ \__
# (____\/_)(_______)\_______/(____\/_)(____\/_)(_______/|/   \__/
#
# Generated HTACCESS File via QUIQQER
# Date: ' . date('Y-m-d H:i:s') . '
#
# Command to create new htaccess:
# ./console --tool=quiqqer:htaccess
#
# How do I customize the .htaccess file:
# https://dev.quiqqer.com/quiqqer/core/wikis/htaccess
#';


        // Custom htaccess
        if (file_exists(ETC_DIR . 'htaccess.custom.php')) {
            $htaccessContent .= "\n\n# Custom htaccess (" . ETC_DIR . 'htaccess.custom.php' . ")\n";
            $htaccessContent .= file_get_contents(ETC_DIR . 'htaccess.custom.php');
            $htaccessContent .= "\n\n";
        }

        // module API
        try {
            QUI::getEvents()->fireEvent('onHtaccessGenerate', [&$htaccessContent]);
        } catch (\Exception $exception) {
            QUI\System\Log::addError($exception->getMessage());
        }

        $htaccessContent .= $this->template();

        file_put_contents($htaccessFile, $htaccessContent);

        $this->writeLn();
        $this->resetColor();
    }

    /**
     * htaccess template
     */
    protected function template(): string
    {
        $URL_DIR = URL_DIR;
        $URL_SYS_DIR = URL_SYS_DIR;

        if ($URL_DIR != '/') {
            $URL_SYS_DIR = str_replace($URL_DIR, '', URL_SYS_DIR);
        }

        $URL_SYS_DIR = ltrim($URL_SYS_DIR, '/');

        $Engine = QUI::getTemplateManager()->getEngine();
        $Engine->assign([
            'forceHttps' => (bool)QUI::conf("webserver", "forceHttps"),
            'quiqqerBin' => URL_OPT_DIR . 'quiqqer/core/bin',
            'quiqqerLib' => URL_OPT_DIR . 'quiqqer/core/src',
            'quiqqerSys' => URL_OPT_DIR . 'quiqqer/core/admin',
            'URL_DIR' => $URL_DIR,
            'URL_SYS_ADMIN_DIR' => trim($URL_SYS_DIR, '/'),
            'URL_SYS_DIR' => $URL_SYS_DIR,
        ]);

        return $Engine->fetch(OPT_DIR . 'quiqqer/core/src/templates/htaccess.tpl');
    }

    /**
     * Checks if the htaccess file would change if it gets generated again
     */
    public function hasModifications(): bool
    {
        $htaccessFile = CMS_DIR . '.htaccess';

        // Read old htaccess content and remove header
        $oldHtaccessContent = trim(file_get_contents($htaccessFile));
        $lines = explode(PHP_EOL, $oldHtaccessContent);
        $counter = count($lines);

        for ($i = 0; $i < $counter; $i++) {
            $line = $lines[$i];
            if (str_starts_with($line, "#")) {
                unset($lines[$i]);
                continue;
            }

            break;
        }

        $oldHtaccessContent = implode(PHP_EOL, $lines);


        //
        // Generate htaccess file
        //
        $htaccessContent = "";


        // Custom htaccess
        if (file_exists(ETC_DIR . 'htaccess.custom.php')) {
            $htaccessContent .= "\n\n# Custom htaccess (" . ETC_DIR . 'htaccess.custom.php' . ")\n";
            $htaccessContent .= file_get_contents(ETC_DIR . 'htaccess.custom.php');
            $htaccessContent .= "\n\n";
        }

        $htaccessContent .= $this->template();

        if (trim($oldHtaccessContent) === trim($htaccessContent)) {
            return false;
        }


        return true;
    }
}
