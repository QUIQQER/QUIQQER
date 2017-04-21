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
        $quiqqerDir    = CMS_DIR;
        $domain        = HOST;
        $quiqqerUrlDir = URL_DIR;


        return <<<NGINX
        
        server {
            listen 80;
            listen [::]:80;
            
            server_name {$domain}
            
            return 301 https://\$http_host\$request_uri;
        }
        
        
        server {
        
            listen 443;
            listen [::]:443;
    
            root {$quiqqerDir};
    
            index index.php index.html index.htm;
    
            server_name {$domain};
    
            error_log  /var/log/nginx/{$domain}_error.log;
    
            ###############################
            #  Virtual Folder/File Check  #
            ###############################
    
            set \$virtual 0;
    
            # make all virtual folders redirect to the index php 
            if ( !-e \$request_filename ) {
                    set \$virtual 1;
            }
    
            # Virtual folders, that should not be redirected to the index.php
            if ( \$uri ~* '{$quiqqerUrlDir}admin(.*)'){
                    set \$virtual 0;
            }
    
            if ( \$uri ~* '{$quiqqerUrlDir}bin/(.*)'){
                    set \$virtual 0;
            }
    
            if ( \$uri ~* '{$quiqqerUrlDir}lib/(.*)'){
                    set \$virtual 0;
            }
    
    
            # Execute virtual folder redirect if neccessary
            if ( \$virtual = 1){
                    rewrite ^ {$quiqqerUrlDir}index.php?_url=\$uri;
            }
            
            
            ################################
            #          Redirects           #
            ################################
            
            location ^~ {$quiqqerUrlDir}bin/ {
                rewrite ^{$quiqqerUrlDir}bin/(.*) {$quiqqerUrlDir}packages/quiqqer/quiqqer/bin/$1 last;
            }
    
            location ^~ {$quiqqerUrlDir}lib/ {
                rewrite ^{$quiqqerUrlDir}lib/(.*) {$quiqqerUrlDir}packages/quiqqer/quiqqer/lib/$1 last;                                                                                              
            }                                                                                                                                     
    
            location = {$quiqqerUrlDir}admin {
                return 301 https://\$http_host{$quiqqerUrlDir}admin/;
            }
    
            location = {$quiqqerUrlDir}admin/ {
                    rewrite {$quiqqerUrlDir}admin/(.*) {$quiqqerUrlDir}packages/quiqqer/quiqqer/admin/index.php last;
            }
                                                                                                                                                
            location ^~ {$quiqqerUrlDir}admin/ {                                                                                                                    
                    rewrite {$quiqqerUrlDir}admin/(.*) {$quiqqerUrlDir}packages/quiqqer/quiqqer/admin/$1 last;
            }        
    
            ################################
            #         Whitelist            #
            ################################
    
    
            # /////////////////////////////////////////////////////////////////////////////////
            # Whitelisted php
            # ////////////////////////////////////////////////////////////////////////////////
    
            location = {$quiqqerUrlDir}index.php {
                    fastcgi_param   QUERY_STRING            \$query_string;
                    fastcgi_param   REQUEST_METHOD          \$request_method;
                    fastcgi_param   CONTENT_TYPE            \$content_type;
                    fastcgi_param   CONTENT_LENGTH          \$content_length;
                    
                    fastcgi_param   SCRIPT_FILENAME         \$request_filename;
                    fastcgi_param   SCRIPT_NAME             \$fastcgi_script_name;
                    fastcgi_param   REQUEST_URI             \$request_uri;
                    fastcgi_param   DOCUMENT_URI            \$document_uri;
                    fastcgi_param   DOCUMENT_ROOT           \$document_root;
                    fastcgi_param   SERVER_PROTOCOL         \$server_protocol;
                    
                    fastcgi_param   GATEWAY_INTERFACE       CGI/1.1;
                    fastcgi_param   SERVER_SOFTWARE         nginx/\$nginx_version;
                    
                    fastcgi_param   REMOTE_ADDR             \$remote_addr;
                    fastcgi_param   REMOTE_PORT             \$remote_port;
                    fastcgi_param   SERVER_ADDR             \$server_addr;
                    fastcgi_param   SERVER_PORT             \$server_port;
                    fastcgi_param   SERVER_NAME             \$server_name;
                    
                    fastcgi_param   HTTPS                   \$https if_not_empty;
                    
                    fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
                    
                    # PHP only, required if PHP was built with --enable-force-cgi-redirect
                    fastcgi_param   REDIRECT_STATUS         200;
                    fastcgi_pass php;
           }
    
    
            location = {$quiqqerUrlDir}image.php {
                    fastcgi_param   QUERY_STRING            \$query_string;
                    fastcgi_param   REQUEST_METHOD          \$request_method;
                    fastcgi_param   CONTENT_TYPE            \$content_type;
                    fastcgi_param   CONTENT_LENGTH          \$content_length;
                    
                    fastcgi_param   SCRIPT_FILENAME         \$request_filename;
                    fastcgi_param   SCRIPT_NAME             \$fastcgi_script_name;
                    fastcgi_param   REQUEST_URI             \$request_uri;
                    fastcgi_param   DOCUMENT_URI            \$document_uri;
                    fastcgi_param   DOCUMENT_ROOT           \$document_root;
                    fastcgi_param   SERVER_PROTOCOL         \$server_protocol;
                    
                    fastcgi_param   GATEWAY_INTERFACE       CGI/1.1;
                    fastcgi_param   SERVER_SOFTWARE         nginx/\$nginx_version;
                    
                    fastcgi_param   REMOTE_ADDR             \$remote_addr;
                    fastcgi_param   REMOTE_PORT             \$remote_port;
                    fastcgi_param   SERVER_ADDR             \$server_addr;
                    fastcgi_param   SERVER_PORT             \$server_port;
                    fastcgi_param   SERVER_NAME             \$server_name;
                    
                    fastcgi_param   HTTPS                   \$https if_not_empty;
                    
                    fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
                    
                    # PHP only, required if PHP was built with --enable-force-cgi-redirect
                    fastcgi_param   REDIRECT_STATUS         200;
                    fastcgi_pass php;
            }
    
            location ~* ^(.*)/bin/(.*)\.php$ {
                    fastcgi_param   QUERY_STRING            \$query_string;
                    fastcgi_param   REQUEST_METHOD          \$request_method;
                    fastcgi_param   CONTENT_TYPE            \$content_type;
                    fastcgi_param   CONTENT_LENGTH          \$content_length;
                    
                    fastcgi_param   SCRIPT_FILENAME         \$request_filename;
                    fastcgi_param   SCRIPT_NAME             \$fastcgi_script_name;
                    fastcgi_param   REQUEST_URI             \$request_uri;
                    fastcgi_param   DOCUMENT_URI            \$document_uri;
                    fastcgi_param   DOCUMENT_ROOT           \$document_root;
                    fastcgi_param   SERVER_PROTOCOL         \$server_protocol;
                    
                    fastcgi_param   GATEWAY_INTERFACE       CGI/1.1;
                    fastcgi_param   SERVER_SOFTWARE         nginx/\$nginx_version;
                    
                    fastcgi_param   REMOTE_ADDR             \$remote_addr;
                    fastcgi_param   REMOTE_PORT             \$remote_port;
                    fastcgi_param   SERVER_ADDR             \$server_addr;
                    fastcgi_param   SERVER_PORT             \$server_port;
                    fastcgi_param   SERVER_NAME             \$server_name;
                    
                    fastcgi_param   HTTPS                   \$https if_not_empty;
                    
                    fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
                    
                    # PHP only, required if PHP was built with --enable-force-cgi-redirect
                    fastcgi_param   REDIRECT_STATUS         200;
                    fastcgi_pass php;
    
            }
    
             location ~* {$quiqqerUrlDir}packages/quiqqer/quiqqer/admin/(.*).php$ {
                    fastcgi_param   QUERY_STRING            \$query_string;
                    fastcgi_param   REQUEST_METHOD          \$request_method;
                    fastcgi_param   CONTENT_TYPE            \$content_type;
                    fastcgi_param   CONTENT_LENGTH          \$content_length;
                    
                    fastcgi_param   SCRIPT_FILENAME         \$request_filename;
                    fastcgi_param   SCRIPT_NAME             \$fastcgi_script_name;
                    fastcgi_param   REQUEST_URI             \$request_uri;
                    fastcgi_param   DOCUMENT_URI            \$document_uri;
                    fastcgi_param   DOCUMENT_ROOT           \$document_root;
                    fastcgi_param   SERVER_PROTOCOL         \$server_protocol;
                    
                    fastcgi_param   GATEWAY_INTERFACE       CGI/1.1;
                    fastcgi_param   SERVER_SOFTWARE         nginx/\$nginx_version;
                    
                    fastcgi_param   REMOTE_ADDR             \$remote_addr;
                    fastcgi_param   REMOTE_PORT             \$remote_port;
                    fastcgi_param   SERVER_ADDR             \$server_addr;
                    fastcgi_param   SERVER_PORT             \$server_port;
                    fastcgi_param   SERVER_NAME             \$server_name;
                    
                    fastcgi_param   HTTPS                   \$https if_not_empty;
                    
                    fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
                    
                    # PHP only, required if PHP was built with --enable-force-cgi-redirect
                    fastcgi_param   REDIRECT_STATUS         200;

                    fastcgi_pass php;
            }
            
            location ~* {$quiqqerUrlDir}[^/]*\.php$ {
                    fastcgi_param   QUERY_STRING            \$query_string;
                    fastcgi_param   REQUEST_METHOD          \$request_method;
                    fastcgi_param   CONTENT_TYPE            \$content_type;
                    fastcgi_param   CONTENT_LENGTH          \$content_length;
                    
                    fastcgi_param   SCRIPT_FILENAME         \$request_filename;
                    fastcgi_param   SCRIPT_NAME             \$fastcgi_script_name;
                    fastcgi_param   REQUEST_URI             \$request_uri;
                    fastcgi_param   DOCUMENT_URI            \$document_uri;
                    fastcgi_param   DOCUMENT_ROOT           \$document_root;
                    fastcgi_param   SERVER_PROTOCOL         \$server_protocol;
                    
                    fastcgi_param   GATEWAY_INTERFACE       CGI/1.1;
                    fastcgi_param   SERVER_SOFTWARE         nginx/\$nginx_version;
                    
                    fastcgi_param   REMOTE_ADDR             \$remote_addr;
                    fastcgi_param   REMOTE_PORT             \$remote_port;
                    fastcgi_param   SERVER_ADDR             \$server_addr;
                    fastcgi_param   SERVER_PORT             \$server_port;
                    fastcgi_param   SERVER_NAME             \$server_name;
                    
                    fastcgi_param   HTTPS                   \$https if_not_empty;
                    
                    fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
                    
                    # PHP only, required if PHP was built with --enable-force-cgi-redirect
                    fastcgi_param   REDIRECT_STATUS         200;

                    fastcgi_pass php;
            }

    
            # /////////////////////////////////////////////////////////////////////////////////
            # Whitelisted static files
            # /////////////////////////////////////////////////////////////////////////////////
    
            location ~ (.*)/bin/(.*){
                # Do not block this
            }
    
            location {$quiqqerUrlDir}media/cache/ {
                # Do not block this
            }
    
            location {$quiqqerUrlDir}packages/ckeditor/ {
                # Do not block this
            }
    
            location ~ {$quiqqerUrlDir}([a-zA-Z-\s0-9_+]*)\.html{
                # Do not block this
            }
    
            location ~ {$quiqqerUrlDir}([a-zA-Z-\s0-9_+]*)\.txt{
                # Do not block this
            }
    
            location ~ {$quiqqerUrlDir}.*\.crt {
                # Do not block this
            }
    
            location ~ {$quiqqerUrlDir}.*\.pem {
                # Do not block this
            }
            
            location ~ {$quiqqerUrlDir}[^/]*$ {
                # Do not block this (all files in the root directory)
            }
            
            location = {$quiqqerUrlDir}robots.txt {
                # Do not block this
            }
    
            location = {$quiqqerUrlDir}favicon.ico {
                # Do not block this
            }
    
            location = {$quiqqerUrlDir} {
                # Do not block this
            }
            
            # /////////////////////////////////////////////////////////////////////////////////
            # Block everything not whitelisted
            # /////////////////////////////////////////////////////////////////////////////////
    
            location / {
                    rewrite ^ {$quiqqerUrlDir}index.php?_url=error403;
            }

        }
NGINX;
    }
}
