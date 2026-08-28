
<IfModule mod_rewrite.c>

    SetEnv HTTP_MOD_REWRITE On

    RewriteEngine On
    RewriteBase {$URL_DIR}
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{ldelim}HTTP:Authorization{rdelim}]

{if $forceHttps}
    # Redirect non https traffic to https. For a safer web.
    RewriteCond %{ldelim}HTTPS{rdelim} !on
    RewriteRule (.*) https://%{ldelim}HTTP_HOST{rdelim}%{ldelim}REQUEST_URI{rdelim} [R=301,END]
{/if}

    RewriteRule ^{$URL_SYS_ADMIN_DIR}$ {$URL_DIR}{$URL_SYS_DIR} [R=301,END]

    #Block .git directories and their contents
    RewriteCond %{ldelim}REQUEST_URI{rdelim} ^(.*\/)?.git(\/.*)?$ [OR]
    RewriteCond %{ldelim}REQUEST_URI{rdelim} ^/console
    RewriteRule ^(.*)$ – [END,R=403]

    ## bin dir
    RewriteRule ^bin/(.*)$ {$quiqqerBin}/$1 [END]
    ## lib dir
    RewriteRule ^lib/(.*)$ {$quiqqerLib}/$1 [END]


    ## admin
    RewriteRule ^{$URL_SYS_DIR}$ {$quiqqerSys}/index.php [END]

    RewriteCond %{ldelim}REQUEST_URI{rdelim} ^{$URL_DIR}{$URL_SYS_DIR}image.php$
    RewriteRule ^(.*)$ {$URL_DIR}image.php?%{ldelim}QUERY_STRING{rdelim} [END]

    # Existing active-document media caches must pass through the SVG sanitizer path as well.
    RewriteCond %{ldelim}REQUEST_URI{rdelim} ^{$URL_DIR}media/cache/.*\.(svgz?|html?|xhtml|xml)$ [NC]
    RewriteRule ^(.*)$ index.php?_url=$1&%{ldelim}QUERY_STRING{rdelim} [END]

    RewriteCond %{ldelim}REQUEST_URI{rdelim} ^{$URL_DIR}{$URL_SYS_DIR}$ [OR]
    RewriteCond %{ldelim}REQUEST_URI{rdelim} ^{$URL_DIR}{$URL_SYS_DIR}index.php$ [OR]
    RewriteCond %{ldelim}REQUEST_URI{rdelim} ^{$URL_DIR}{$URL_SYS_DIR}image.php$ [OR]
    RewriteCond %{ldelim}REQUEST_URI{rdelim} ^{$URL_DIR}{$URL_SYS_DIR}ajax.php$ [OR]
    RewriteRule ^{$URL_SYS_DIR}(.*)$ {$quiqqerSys}/$1 [END]

    RewriteCond %{ldelim}REQUEST_FILENAME{rdelim} !-f
    RewriteCond %{ldelim}REQUEST_FILENAME{rdelim} !-d
    RewriteRule ^(.*)$ index.php?_url=$1&%{ldelim}QUERY_STRING{rdelim} [END]

    RewriteCond %{ldelim}REQUEST_URI{rdelim} !^/.well-known/.*$
    RewriteCond %{ldelim}REQUEST_URI{rdelim} !^(.*)bin(.*)$
    RewriteCond %{ldelim}REQUEST_URI{rdelim} !^{$URL_DIR}media/cache/(.*)$
    RewriteCond %{ldelim}REQUEST_URI{rdelim} !^{$URL_DIR}packages/ckeditor/(.*)$
    RewriteCond %{ldelim}REQUEST_URI{rdelim} !^{$URL_DIR}packages/pcsg/ckeditor/(.*)$
    RewriteCond %{ldelim}REQUEST_URI{rdelim} !^{$URL_DIR}([a-zA-Z-\s0-9_+]*)\.html$
    RewriteCond %{ldelim}REQUEST_URI{rdelim} !^{$URL_DIR}([a-zA-Z-\s0-9_+]*)\.txt$
    RewriteCond %{ldelim}REQUEST_URI{rdelim} !^{$URL_DIR}.*\.crt$
    RewriteCond %{ldelim}REQUEST_URI{rdelim} !^{$URL_DIR}.*\.pem$
    RewriteCond %{ldelim}REQUEST_URI{rdelim} !^{$URL_DIR}favicon\.ico$
    RewriteCond %{ldelim}REQUEST_URI{rdelim} !^{$URL_DIR}robots\.txt$
    RewriteCond %{ldelim}REQUEST_URI{rdelim} !^{$URL_DIR}image\.php$
    RewriteCond %{ldelim}REQUEST_URI{rdelim} !^{$URL_DIR}index\.php$
    RewriteCond %{ldelim}REQUEST_URI{rdelim} !^{$URL_DIR}ajax\.php$
    RewriteCond %{ldelim}REQUEST_URI{rdelim} !^{$URL_DIR}ajaxBundler\.php$
    RewriteCond %{ldelim}REQUEST_URI{rdelim} !^{$URL_DIR}$
    RewriteCond %{ldelim}REQUEST_URI{rdelim} !^{$URL_DIR}([^/]*)$

    RewriteRule ^(.*)$ {$URL_DIR}?error=403 [R=301,END]
</IfModule>
