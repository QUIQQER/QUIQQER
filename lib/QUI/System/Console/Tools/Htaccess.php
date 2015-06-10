<?php

/**
 * \QUI\System\Console\Tools\Htaccess
 */

namespace QUI\System\Console\Tools;

use QUI;

/**
 * Execute the system setup
 *
 * @author www.pcsg.de (Henning Leutz)
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

        $htaccessBackupFile = VAR_DIR.'backup/htaccess_'.date('Y-m-d__H_i_s');
        $htaccessFile = CMS_DIR.'.htaccess';

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
            $this->writeLn('No .htaccess File found. Could not create a backup.', 'red');
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
# Date: '.date('Y-m-d H:i:s') .'
#
# Command to create new htaccess:
# php quiqqer.php --username="" --password="" --tool=quiqqer:htaccess
#';
        
        $htaccessContent .= $this->_template();

        file_put_contents($htaccessFile, $htaccessContent);

        $this->writeLn('');
        $this->resetColor();
    }

    /**
     * htaccess template
     *
     * @return string
     */
    protected function _template()
    {
        $URL_DIR = URL_DIR;
        $URL_LIB_DIR = ltrim(URL_LIB_DIR, '/');
        $URL_BIN_DIR = ltrim(URL_BIN_DIR, '/');
        $URL_SYS_DIR = ltrim(URL_SYS_DIR, '/');
        $URL_VAR_DIR = ltrim(URL_VAR_DIR, '/');

        $quiqqerLib = URL_OPT_DIR.'quiqqer/quiqqer/lib';
        $quiqqerBin = URL_OPT_DIR.'quiqqer/quiqqer/bin';
        $quiqqerSys = URL_OPT_DIR.'quiqqer/quiqqer/admin';

        return "

<IfModule mod_rewrite.c>

    SetEnv HTTP_MOD_REWRITE On

    RewriteEngine On
    RewriteBase {$URL_DIR}

    RewriteRule ^{$URL_BIN_DIR}(.*)$ {$quiqqerBin}/$1 [L]
    RewriteRule ^{$URL_LIB_DIR}(.*)$ {$quiqqerLib}/$1 [L]
    RewriteRule ^{$URL_SYS_DIR}(.*)$ {$quiqqerSys}/$1 [L]

    RewriteCond %{REQUEST_FILENAME} !^.*bin/
    RewriteRule ^.*{$URL_VAR_DIR}|^.*media/sites/ / [L]
    RewriteRule ^/(.*)     /$

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d

    RewriteRule ^(.*)$ index.php?_url=$1&%{QUERY_STRING}
</IfModule>
        ";
    }
}
