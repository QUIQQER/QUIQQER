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


        //
        // Generate nginx file
        //
        $nginxContent
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
# Generated nginx.xonf File via QUIQQER
# Date: ' . date('Y-m-d H:i:s') . '
#
# Command to create new nginx:
# php quiqqer.php --username="" --password="" --tool=quiqqer:nginx
#';

        $nginxContent .= $this->template();

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
        $quiqqerDir = CMS_DIR;
        $quiqqerLib = URL_OPT_DIR . 'quiqqer/quiqqer/lib';
        $quiqqerBin = URL_OPT_DIR . 'quiqqer/quiqqer/bin';
        $quiqqerSys = URL_OPT_DIR . 'quiqqer/quiqqer/admin';

        return <<<NGINX

# nginx configuration

root   {$quiqqerDir};
index  index.php;

location /bin/ {
    alias {$quiqqerBin};
}

location /lib/ {
    alias {$quiqqerLib};
}

location /admin/ {
    alias {$quiqqerSys};

    location ~ \\.php$ {
        fastcgi_split_path_info ^(.+?\\.php)(/.*)?$;
        fastcgi_pass unix:/var/run/php5-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}

location = / {
    
}

location / {

    try_files \$uri \$uri/;

    #Block Requests other than following exceptions
    if( \$request_uri !~ ^(.*)bin(.*)$ &&
        \$request_uri !~ ^/media/cache/$ &&
        \$request_uri !~ ^/([a-zA-Z-\s0-9_+]*)\.html$ &&
        \$request_uri !~ ^/([a-zA-Z-\s0-9_+]*)\.txt$ &&
        \$request_uri !~ ^/favicon\.ico$ &&
        \$request_uri !~ ^/robots\.txt  &&
        \$request_uri !~ ^/image.php   &&
        \$request_uri !~ ^/index\.php$ &&
        \$request_uri !~ ^/$ 
    ){
        rewrite /index.php?error=403 permanent;
    }
        
    

    if (!-e \$request_filename){
        rewrite  ^/(.*)$  /index.php?_url=$1&\$query_string  last;
        break;
    }
}

NGINX;

    }
}
