<?php

/**
 * \QUI\System\Console\Tools\Htaccess
 */

namespace QUI\System\Console\Tools;

use QUI;

/**
 * Execute the system setup
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Htaccess extends QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('quiqqer:htaccess')
            ->setDescription('Generate the htaccess File.');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $this->writeLn('Generating HTACCESS ...');

        $htaccessBackupFile = VAR_DIR . 'backup/htaccess_' . date('Y-m-d__H_i_s');
        $htaccessFile       = CMS_DIR . '.htaccess';

        # Create the custom htaccess file if it does not exist
        if (!file_exists(ETC_DIR . 'htaccess.custom.php')) {
            file_put_contents(ETC_DIR . 'htaccess.custom.php', "");
        }

        $oldTemplate = false;
        $config = parse_ini_file(ETC_DIR."conf.ini.php", true);

        if (!isset($config['webserver']['type'])) {
            $this->writeLn('Webservertype is not configured!', "red");
            return;
        }
        $webserverType = $config['webserver']['type'];

        if ($webserverType == "apache2.2") {
            $oldTemplate = true;
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
# php quiqqer.php --username="" --password="" --tool=quiqqer:htaccess
#
# How do I customize the .htaccess file:
# https://dev.quiqqer.com/quiqqer/quiqqer/wikis/htaccess
#';


        // Custom htaccess
        if (file_exists(ETC_DIR . 'htaccess.custom.php')) {
            $htaccessContent .= "\n\n# Custom htaccess (" . ETC_DIR . 'htaccess.custom.php' . ")\n";
            $htaccessContent .= file_get_contents(ETC_DIR . 'htaccess.custom.php');
            $htaccessContent .= "\n\n";
        }

        if ($oldTemplate) {
            $htaccessContent .= $this->templateOld();
        } else {
            $htaccessContent .= $this->template();
        }


        file_put_contents($htaccessFile, $htaccessContent);

        $this->writeLn('');
        $this->resetColor();
    }

    /**
     * Checks if the htaccess file would change if it gets generated again
     *
     * @return bool
     */
    public function hasModifications()
    {
        $htaccessFile = CMS_DIR . '.htaccess';
        $oldTemplate  = false;

        // Read old htaccess content and remove header
        $oldHtaccessContent = trim(file_get_contents($htaccessFile));
        $lines              = explode(PHP_EOL, $oldHtaccessContent);
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            if (substr($line, 0, 1) === "#") {
                unset($lines[$i]);
                continue;
            }

            break;
        }
        $oldHtaccessContent = implode(PHP_EOL, $lines);


        try {
            $version = $this->getApacheVersion();

            if (!isset($version[1])) {
                throw new QUI\Exception("Couldnt detect Webserver version");
            }

            $this->writeLn("Apache version detected : " . $version[0] . "." . $version[1]);
            if ($version[1] <= 2) {
                $oldTemplate = true;
            }
        } catch (\Exception $Exception) {
            $this->writeLn("Please select your Apache Version.");
            $this->writeLn("[1] Apache 2.3 and higher.");
            $this->writeLn("[2] Apache 2.2 and lower.");
            $this->writeLn("Please type a number [1]");
            $input = $this->readInput();
            if ($input == "2") {
                $oldTemplate = true;
            }
        }


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

        if ($oldTemplate) {
            $htaccessContent .= $this->templateOld();
        } else {
            $htaccessContent .= $this->template();
        }

        if (trim($oldHtaccessContent) == trim($htaccessContent)) {
            return false;
        }


        return true;
    }

    /**
     * htaccess template
     *
     * @return string
     */
    protected function template()
    {
        $URL_DIR     = URL_DIR;
        $URL_LIB_DIR = URL_LIB_DIR;
        $URL_BIN_DIR = URL_BIN_DIR;
        $URL_SYS_DIR = URL_SYS_DIR;
        $URL_VAR_DIR = URL_VAR_DIR;

        if ($URL_DIR != '/') {
            $URL_LIB_DIR = str_replace($URL_DIR, '', URL_LIB_DIR);
            $URL_BIN_DIR = str_replace($URL_DIR, '', URL_BIN_DIR);
            $URL_SYS_DIR = str_replace($URL_DIR, '', URL_SYS_DIR);
            $URL_VAR_DIR = str_replace($URL_DIR, '', URL_VAR_DIR);
        }

        $URL_LIB_DIR = ltrim($URL_LIB_DIR, '/');
        $URL_BIN_DIR = ltrim($URL_BIN_DIR, '/');
        $URL_SYS_DIR = ltrim($URL_SYS_DIR, '/');
        $URL_VAR_DIR = ltrim($URL_VAR_DIR, '/');

        $quiqqerLib = URL_OPT_DIR . 'quiqqer/quiqqer/lib';
        $quiqqerBin = URL_OPT_DIR . 'quiqqer/quiqqer/bin';
        $quiqqerSys = URL_OPT_DIR . 'quiqqer/quiqqer/admin';
        $quiqqerDir = URL_OPT_DIR . 'quiqqer/quiqqer';

        $URL_SYS_ADMIN_DIR = trim($URL_SYS_DIR, '/');


        # Check for QUIQQERs webserver configuration
        $forceHttps = "";
        if (QUI::conf("webserver", "forceHttps")) {
            $forceHttps = "# Redirect non https traffic to https. For a safer web." . PHP_EOL;
            $forceHttps .= "    RewriteCond %{HTTPS} !on" . PHP_EOL;
            $forceHttps .= "    RewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI} [R=301,END]" . PHP_EOL;
        }


        return "
<IfModule mod_rewrite.c>

    SetEnv HTTP_MOD_REWRITE On

    RewriteEngine On
    RewriteBase {$URL_DIR}
    
    {$forceHttps}
    
    RewriteRule ^{$URL_SYS_ADMIN_DIR}$ {$URL_DIR}{$URL_SYS_DIR} [R=301,END]

    #Block .git directories and their contents
    RewriteCond %{REQUEST_URI} ^(.*\/)?.git(\/.*)?$
    RewriteRule ^(.*)$ – [END,R=403]

    ## bin dir
    RewriteRule ^bin/(.*)$ {$quiqqerBin}/$1 [END]" .

            # This is a temporary workaround. needs to be removed when the media upload is relocated
            "
    ## lib dir
    RewriteRule ^lib/(.*)$ {$quiqqerLib}/$1 [END]


    ## admin
    RewriteRule ^{$URL_SYS_DIR}$ {$quiqqerSys}/index.php [END]

    RewriteCond %{REQUEST_URI} ^{$URL_DIR}{$URL_SYS_DIR}image.php$
    RewriteRule ^(.*)$ {$URL_DIR}image.php?%{QUERY_STRING} [END]

    RewriteCond %{REQUEST_URI} ^{$URL_DIR}{$URL_SYS_DIR}$ [OR]
    RewriteCond %{REQUEST_URI} ^{$URL_DIR}{$URL_SYS_DIR}index.php$ [OR]
    RewriteCond %{REQUEST_URI} ^{$URL_DIR}{$URL_SYS_DIR}image.php$ [OR]
    RewriteCond %{REQUEST_URI} ^{$URL_DIR}{$URL_SYS_DIR}ajax.php$ [OR]
    RewriteRule ^{$URL_SYS_DIR}(.*)$ {$quiqqerSys}/$1 [END]

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?_url=$1&%{QUERY_STRING} [END]

    
    RewriteCond %{REQUEST_URI} !^(.*)bin(.*)$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}media/cache/(.*)$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}packages/ckeditor/(.*)$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}([a-zA-Z-\s0-9_+]*)\.html$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}([a-zA-Z-\s0-9_+]*)\.txt$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}.*\.crt$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}.*\.pem$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}favicon\.ico$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}robots\.txt$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}image.php$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}index\.php$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}([^/]*)$

    RewriteRule ^(.*)$ {$URL_DIR}?error=403 [R=301,END]
</IfModule>
        ";
    }

    protected function templateOld()
    {
        $URL_DIR     = URL_DIR;
        $URL_LIB_DIR = URL_LIB_DIR;
        $URL_BIN_DIR = URL_BIN_DIR;
        $URL_SYS_DIR = URL_SYS_DIR;
        $URL_VAR_DIR = URL_VAR_DIR;

        if ($URL_DIR != '/') {
            $URL_LIB_DIR = str_replace($URL_DIR, '', URL_LIB_DIR);
            $URL_BIN_DIR = str_replace($URL_DIR, '', URL_BIN_DIR);
            $URL_SYS_DIR = str_replace($URL_DIR, '', URL_SYS_DIR);
            $URL_VAR_DIR = str_replace($URL_DIR, '', URL_VAR_DIR);
        }

        $URL_LIB_DIR = ltrim($URL_LIB_DIR, '/');
        $URL_BIN_DIR = ltrim($URL_BIN_DIR, '/');
        $URL_SYS_DIR = ltrim($URL_SYS_DIR, '/');
        $URL_VAR_DIR = ltrim($URL_VAR_DIR, '/');

        $quiqqerLib = URL_OPT_DIR . 'quiqqer/quiqqer/lib';
        $quiqqerBin = URL_OPT_DIR . 'quiqqer/quiqqer/bin';
        $quiqqerSys = URL_OPT_DIR . 'quiqqer/quiqqer/admin';
        $quiqqerDir = URL_OPT_DIR . 'quiqqer/quiqqer';

        $URL_SYS_ADMIN_DIR = trim($URL_SYS_DIR, '/');

        # Check for QUIQQERs webserver configuration
        $forceHttps = "";
        if (QUI::conf("webserver", "forceHttps")) {
            $forceHttps = "# Redirect non https traffic to https. For a safer web." . PHP_EOL;
            $forceHttps .= "    RewriteCond %{HTTPS} !on" . PHP_EOL;
            $forceHttps .= "    RewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]" . PHP_EOL;
        }

        return "
<IfModule mod_rewrite.c>

    SetEnv HTTP_MOD_REWRITE On

    RewriteEngine On
    RewriteBase {$URL_DIR}
    
    {$forceHttps}
    
    RewriteRule ^{$URL_SYS_ADMIN_DIR}$ {$URL_DIR}{$URL_SYS_DIR} [R=301,L]

    #Block .git directories and their contents
    RewriteCond %{REQUEST_URI} ^(.*\/)?.git(\/.*)?$
    RewriteRule ^(.*)$ – [L,R=403]

    # pass-through if another rewrite rule has been applied already
    RewriteCond %{ENV:REDIRECT_STATUS} 200
    RewriteRule ^ - [L]

    ## bin dir
    RewriteRule ^bin/(.*)$ {$quiqqerBin}/$1 [L]" .

            # This is a temporary workaround. needs to be removed when the media upload is relocated
            "
    ## lib dir
    RewriteRule ^lib/(.*)$ {$quiqqerLib}/$1 [L]

    ## admin
    RewriteRule ^{$URL_SYS_DIR}$ {$quiqqerSys}/index.php [L]

    RewriteCond %{REQUEST_URI} ^{$URL_DIR}{$URL_SYS_DIR}image.php$
    RewriteRule ^(.*)$ {$URL_DIR}image.php?%{QUERY_STRING} [L]

    RewriteCond %{REQUEST_URI} ^{$URL_DIR}{$URL_SYS_DIR}$ [OR]
    RewriteCond %{REQUEST_URI} ^{$URL_DIR}{$URL_SYS_DIR}index.php$ [OR]
    RewriteCond %{REQUEST_URI} ^{$URL_DIR}{$URL_SYS_DIR}image.php$ [OR]
    RewriteCond %{REQUEST_URI} ^{$URL_DIR}{$URL_SYS_DIR}ajax.php$ [OR]
    RewriteRule ^{$URL_SYS_DIR}(.*)$ {$quiqqerSys}/$1 [L]

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?_url=$1&%{QUERY_STRING} [L]

    # quiqqer API allowed requests
    RewriteCond %{REQUEST_URI} !^(.*)bin(.*)$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}media/cache/(.*)$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}packages/ckeditor/(.*)$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}([a-zA-Z-\s0-9_+]*)\.html$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}([a-zA-Z-\s0-9_+]*)\.txt$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}favicon\.ico$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}robots\.txt$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}image.php$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}index\.php$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}.*\.crt$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}.*\.pem$
    RewriteCond %{REQUEST_URI} !^{$URL_DIR}$
    RewriteRule ^(.*)$ {$URL_DIR}?error=403 [R=301,L]
</IfModule>
        ";
    }


    /**
     * Attempts to detect the apache webserver version
     * Returnformat array("major version","minor version","point release");
     *
     * @return array
     * @throws QUI\Exception
     */
    protected function getApacheVersion()
    {
        # Attempt detection by apache2 module
        if (function_exists('apache_get_version')) {
            $version = apache_get_version();
            $regex   = "/Apache\\/([0-9\\.]*)/i";
            $res     = preg_match($regex, $version, $matches);

            if ($res && isset($matches[1])) {
                $version     = $matches[1];
                $verionParts = explode(".", $version);

                return $verionParts;
            }
        }

        # Attempt detection by system shell
        if (\QUI\Utils\System::isShellFunctionEnabled("shell_exec")) {
            $version = shell_exec('apache2 -v');
            $regex   = "/Apache\\/([0-9\\.]*)/i";
            $res     = preg_match($regex, $version, $matches);
            if ($res && isset($matches[1])) {
                $version = $matches[1];

                $verionParts = explode(".", $version);

                return $verionParts;
            }
        }

        throw new QUI\Exception("Could not detect Apache Version");
    }
}
