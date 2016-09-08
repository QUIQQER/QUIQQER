<?php

/**
 * \QUI\System\Console\Tools\Nginx
 */

namespace QUI\System\Console\Tools;

use QUI;

/**
 * Generate the nginx.conf file
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Nginx extends QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('quiqqer:nginx')
            ->setDescription('Generate the nginx.conf File.');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $this->writeLn('Generating nginx.conf ...');

        $nginxBackupFile = VAR_DIR . 'backup/nginx.conf_' . date('Y-m-d__H_i_s');
        $nginxFile       = CMS_DIR . 'nginx.conf';

        //
        // generate backup
        //
        if (file_exists($nginxFile)) {
            file_put_contents(
                $nginxBackupFile,
                file_get_contents($nginxFile)
            );

            $this->writeLn('You can find a .nginx Backup File at:');
            $this->writeLn($nginxBackupFile);
        } else {
            $this->writeLn(
                'No .nginx File found. Could not create a backup.',
                'red'
            );
        }

        $this->resetColor();


        $nginxContent = $this->template();

        file_put_contents($nginxFile, $nginxContent);

        $this->writeLn('');
        $this->resetColor();
    }

    /**
     * nginx template
     *
     * @return string
     */
    protected function template()
    {
        $quiqqerDir           = CMS_DIR;
        $quiqqerHost          = HOST;
        $quiqqerUrlDir        = URL_DIR;
        $quiqqerUrlDirEscaped = str_replace("/", "\\/", URL_DIR);
        $quiqqerLib           = URL_OPT_DIR . 'quiqqer/quiqqer/lib';
        $quiqqerBin           = URL_OPT_DIR . 'quiqqer/quiqqer/bin';
        $quiqqerSys           = URL_OPT_DIR . 'quiqqer/quiqqer/admin';


        return <<<NGINX

#  _______          _________ _______  _______  _______  _______
# (  ___  )|\     /|\__   __/(  ___  )(  ___  )(  ____ \(  ____ )
# | (   ) || )   ( |   ) (   | (   ) || (   ) || (    \/| (    )|
# | |   | || |   | |   | |   | |   | || |   | || (__    | (____)|
# | |   | || |   | |   | |   | |   | || |   | ||  __)   |     __)
# | | /\| || |   | |   | |   | | /\| || | /\| || (      | (\ (
# | (_\ \ || (___) |___) (___| (_\ \ || (_\ \ || (____/\| ) \ \__
# (____\/_)(_______)\_______/(____\/_)(____\/_)(_______/|/   \__/
#
# Generated nginx.xonf File via QUIQQER
# Date: ' . date('Y-m-d H:i:s') . '
#
# Command to create new nginx:
# php quiqqer.php --username="" --password="" --tool=quiqqer:nginx


# Nginx configuration

server{

    listen 0.0.0.0:80;
    server_name {$quiqqerHost};
    
    
    root   {$quiqqerDir};
    index  index.php;
    
    # #########################
    # Virtual Folders & SEO & UX
    # #########################
    
    # Route /bin/ to its real location.
    location ^~{$quiqqerUrlDir}bin/ {
        rewrite ^{$quiqqerUrlDir}bin/(.*)$ {$quiqqerUrlDir}packages/quiqqer/quiqqer/bin/$1 break;
    }
    
    
    # Route /lib/ to its real location.
    location ^~{$quiqqerUrlDir}lib/ {
        rewrite ^{$quiqqerUrlDir}lib/(.*)$ {$quiqqerUrlDir}packages/quiqqer/quiqqer/lib/$1 break;
    }
    
    
    # #######################################################
    # Admin
    # #######################################################
    
    location = {$quiqqerUrlDir}admin {
        rewrite ^{$quiqqerUrlDir}admin$ {$quiqqerUrlDir}admin/ last;
    }
    
    location ~^{$quiqqerUrlDir}admin/ {    
        rewrite ^{$quiqqerUrlDir}admin/(.*)$ {$quiqqerUrlDir}packages/quiqqer/quiqqer/admin/$1 last;
        
    }
    
    # #######################################################
    # General Rules
    # #######################################################
    
    location ~*\.php$ {
        if ( \$uri !~ ^{$quiqqerUrlDirEscaped}(index\.php|media\/cache|(.*)\.html|(.*)\.txt|favicon\.ico|robots\.txt|image\.php|(.*)\/?bin\/(.*)|(packages\/quiqqer\/quiqqer\/admin\/(image.php|index.php|ajax.php|login.php)?$)|(admin\/(image.php|index.php|ajax.php)?$))){ 
            rewrite ^ {$quiqqerUrlDir}index.php?_url=error=403 last;
        }
        
        
        fastcgi_pass php;
        include snippets/fastcgi-php.conf;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }
    
    #Block Access to non-whitelisted Files
    location ~{$quiqqerUrlDir} {
    
        try_files \$uri \$uri/ {$quiqqerUrlDir}index.php?_uri=\$uri&\$query_string;
    
    
        if ( \$uri !~ ^{$quiqqerUrlDirEscaped}(index\.php|media\/cache\/(.*)|([a-zA-Z-\s0-9_+]*)\.html|([a-zA-Z-\s0-9_+]*)\.txt|favicon\.ico|robots\.txt|image\.php|(.*)\/?bin\/(.*)|packages\/ckeditor\/(.*)|(packages\/quiqqer\/quiqqer\/admin\/(image.php|index.php|ajax.php|login.php)?$)|(admin\/(image.php|index.php|ajax.php)?$))) {
            rewrite ^ {$quiqqerUrlDir}index.php?_url=error=403 last;
        }
        
    }
}

NGINX;
    }
}
