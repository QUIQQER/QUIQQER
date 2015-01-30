<?php

/**
 * This file contains \QUI\Rewrite
 */

namespace QUI;

use QUI;
use QUI\Projects\Media\Utils as MediaUtils;

/**
 * Rewrite - URL Verwaltung (sprechende URLS)
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 *
 * @todo must be rewrited, spaghetti code :(
 * @todo comments translating
 *
 * @event onQUI::Request
 * @event onQUI::Access
 * @event onQUI::RewriteOutput [ Rewrite ]
 */

class Rewrite
{
    const URL_PARAM_SEPERATOR   = '_';
    const URL_SPACE_CHARACTER   = '-';
    const URL_PROJECT_CHARACTER = '^';
    const URL_DEFAULT_SUFFIX    = '.html';

    /**
     * site request parameter
     * @var array
     */
    public $site_params = array();

    /**
     * active project
     * @var \QUI\Projects\Project
     */
    private $_project;

    /**
     * active project
     * @var String
     */
    private $_project_str = '';

    /**
     * active template
     * @var String
     */
    private $_template_str = false;

    /**
     * if project prefix is set
     * @var String
     */
    private $_project_prefix = '';

    /**
     * project lang
     * @var String
     */
    private $_lang = false;

    /**
     * active site
     * @var \QUI\Projects\Site
     */
    private $_site = null;

    /**
     * first site of the project
     * @var \QUI\Projects\Site
     */
    private $_first_child;

    /**
     * current site path
     * @var array
     */
    private $_path = array();

    /**
     * current site path - but only the ids
     * @var array
     */
    private $_ids_in_path = array();

    /**
     * internal url cache
     * @var array
     */
    private $_url_cache = array();

    /**
     * loaded vhosts
     * @var array
     */
    private $_vhosts = false;

    /**
     * current suffix, (.html, .pdf, .print)
     * @var String
     */
    private $_suffix = '.html';

    /**
     * the html output
     * @var String
     */
    private $_output_content = '';

    /**
     * Standard header code
     * @var int
     */
    private $_headerCode = 200;

    /**
     *
     */
    public function __construct()
    {
        $this->Events = new QUI\Events\Event();
    }

    /**
     * Request verarbeiten
     */
    public function exec()
    {
        if ( !isset( $_REQUEST['_url'] ) ) {
            $_REQUEST['_url'] = '';
        }

        //wenn seite existiert, dann muss nichts mehr gemacht werden
        if ( isset( $this->_site ) && $this->_site )
        {
            \QUI::getEvents()->fireEvent( 'request', array( $this, $_REQUEST['_url'] ) );
            return;
        }

        $vhosts = $this->getVHosts();

        if ( !isset( $_SERVER['HTTP_HOST'] ) ) {
            $_SERVER['HTTP_HOST'] = '';
        }

        // 301 abfangen
        if ( isset( $vhosts['301'] ) &&
             isset( $vhosts['301'][ $_SERVER['HTTP_HOST'] ] ) )
        {
            $url  = $_REQUEST['_url'];
            $host = $vhosts['301'][ $_SERVER['HTTP_HOST'] ];

            \QUI::getEvents()->fireEvent( 'request', array( $this, $_REQUEST['_url'] ) );

            $this->showErrorHeader( 301, $host .'/'. $url );
            exit;
        }

        // Kategorien aufruf
        // Aus url/kat/ wird url/kat.html
        if ( !empty( $_REQUEST['_url'] ) &&
             substr( $_REQUEST['_url'], -1 ) == '/' &&
             strpos( $_REQUEST['_url'], 'media/cache' ) === false )
        {
            $_REQUEST['_url'] = substr( $_REQUEST['_url'], 0, -1 ) .'.html';

            \QUI::getEvents()->fireEvent( 'request', array( $this, $_REQUEST['_url'] ) );

            // 301 weiterleiten
            $this->showErrorHeader( 301, URL_DIR . $_REQUEST['_url'] );
        }

        // Suffix
        if ( substr( $_REQUEST['_url'], -6 ) == '.print' ) {
            $this->_suffix = '.print';
        }

        if ( substr( $_REQUEST['_url'], -4 ) == '.pdf' ) {
            $this->_suffix = '.pdf';
        }

        if ( !empty( $_REQUEST['_url'] ) )
        {
            $_url = explode('/', $_REQUEST['_url']);

            // projekt
            if ( isset( $_url[0] ) && substr( $_url[0], 0, 1 ) == self::URL_PROJECT_CHARACTER )
            {
                $this->_project_str = str_replace('.html', '', substr($_url[0], 1 ));

                // if a second project_character, its the template
                if ( strpos( $this->_project_str, self::URL_PROJECT_CHARACTER ) )
                {
                    $_project_split = explode(
                        self::URL_PROJECT_CHARACTER,
                        $this->_project_str
                    );

                    $this->_project_str  = $_project_split[0];
                    $this->_template_str = $_project_split[1];
                }

                $this->_project_prefix = self::URL_PROJECT_CHARACTER . $this->_project_str .'/';

                if ( $this->_template_str )
                {
                    $this->_project_prefix  = self::URL_PROJECT_CHARACTER . $this->_project_str;
                    $this->_project_prefix .= self::URL_PROJECT_CHARACTER . $this->_template_str .'/';
                }


                unset( $_url[0] );

                $_new_url = array();

                foreach ( $_url as $elm ) {
                    $_new_url[] = $elm;
                }

                $_url = $_new_url;
            }

            // Sprache
            if ( isset( $_url[0] ) &&
                 (strlen($_url[0]) == 2 || strlen( str_replace('.html', '', $_url[0]) ) == 2))
            {
                $this->_lang = str_replace( self::URL_DEFAULT_SUFFIX , '', $_url[0] );
                QUI::getLocale()->setCurrent( $this->_lang );

                unset( $_url[0] );

                $_new_url = array();

                foreach ($_url as $elm) {
                      $_new_url[] = $elm;
                }

                $_url = $_new_url;

                // Wenns ein Hosteintrag mit der Sprache gibt, dahin leiten
                // @todo https host nicht über den port prüfen, zu ungenau
                if (
                    isset($_SERVER['HTTP_HOST']) &&
                    isset($vhosts[$_SERVER['HTTP_HOST']]) &&
                    isset($vhosts[$_SERVER['HTTP_HOST']][$this->_lang]) &&

                    // und es nicht der https host ist
                    (int)$_SERVER['SERVER_PORT'] !== 443 &&
                    QUI::conf('globals', 'httpshost') != 'https://'.$_SERVER['HTTP_HOST'])
                {
                    $url = implode('/', $_url);
                    $url = $vhosts[$_SERVER['HTTP_HOST']][$this->_lang] . URL_DIR . $url;
                    $url = QUI\Utils\String::replaceDblSlashes($url);
                    $url = 'http://'. $this->_project_prefix . $url;

                    \QUI::getEvents()->fireEvent( 'request', array( $this, $_REQUEST['_url'] ) );

                    $this->showErrorHeader(301, $url);
                }
            }

            $_REQUEST['_url'] = implode('/', $_url);

            if ( !count( $_url ) ) {
                 unset( $_REQUEST['_url'] );
            }
        }

        // Media Center Datei, falls nicht im Cache ist
        if ( isset( $_REQUEST['_url'] ) && strpos( $_REQUEST['_url'], 'media/cache' ) !== false )
        {
            \QUI::getEvents()->fireEvent( 'request', array( $this, $_REQUEST['_url'] ) );

            try
            {
                $Item = MediaUtils::getElement( $_REQUEST['_url'] );

                if (strpos($_REQUEST['_url'], '__') !== false)
                {
                    $lastpos_ul = strrpos( $_REQUEST['_url'], '__' ) + 2;
                    $pos_dot    = strpos( $_REQUEST['_url'], '.', $lastpos_ul );

                    $size      = substr( $_REQUEST['_url'], $lastpos_ul, ( $pos_dot-$lastpos_ul ) );
                    $part_size = explode( 'x', $size );

                    if ( isset( $part_size[0] ) ) {
                        $width = (int)$part_size[0];
                    }

                    if ( isset( $part_size[1] ) ) {
                        $height = (int)$part_size[1];
                    }
                }

            } catch ( QUI\Exception $Exception )
            {
                // Falls Bild nicht mehr existiert oder ein falscher Aufruf gemacht wurde
                $this->showErrorHeader( 404 );
                exit;
            }

            if ( $Item->getType() === 'QUI\\Projects\\Media\\Image' )
            {
                /* @var $Item \QUI\Projects\Media\Image */
                if ( !isset( $width ) || empty( $width ) ) {
                    $width = false;
                }

                if ( !isset( $height ) || empty( $height ) ) {
                    $height = false;
                }

                $file = $Item->createSizeCache( $width, $height );
            } else
            {
                /* @var $Item \QUI\Projects\Media\File */
                $file = $Item->createCache();
            }

            if ( !file_exists($file) )
            {
                QUI\System\Log::write('File not exist: '. $file , 'error');
                exit;
            }

            // Dateien direkt im Browser ausgeben, da Cachedatei noch nicht verfügbar war
            header("Content-Type: ". $Item->getAttribute('mime_type'));
            header("Expires: ". gmdate("D, d M Y H:i:s") . " GMT");
            header("Pragma: public");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Accept-Ranges: bytes");
            header("Content-Disposition: inline; filename=\"". pathinfo($file, PATHINFO_BASENAME) ."\"");
            header("Content-Size: ". filesize($file));
            header("Content-Length: ". filesize($file));
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") ." GMT");
            header("Connection: Keep-Alive");

            $fo_image = fopen($file, "r");
            $fr_image = fread($fo_image, filesize($file));
            fclose($fo_image);

            echo $fr_image;
            exit;
        }

        \QUI::getEvents()->fireEvent( 'request', array( $this, $_REQUEST['_url'] ) );

        // Falls kein suffix dann 301 weiterleiten auf .html
        if ( !empty( $_REQUEST['_url'] ) &&
             substr( $_REQUEST['_url'], -1 ) != '/' )
        {
            $pathinfo = pathinfo( $_REQUEST['_url'] );

            if ( !isset( $pathinfo['extension'] ) )
            {
                $url = URL_DIR . $_REQUEST['_url'] .'.html';
                $url = QUI\Utils\String::replaceDblSlashes( $url );

                // Falls keine Extension (.html) dann auf .html
                $this->showErrorHeader( 301, $url );
            }
        }

        $this->_first_child = $this->getProject()->firstChild();

        if ( !$this->_site ) {
            $this->_site = $this->_first_child;
        }

        if ( !empty( $_REQUEST['_url'] ) ) // URL Parameter filtern
        {
            try
            {

                $this->_site = $this->getSiteByUrl( $_REQUEST['_url'], true );

            } catch ( QUI\Exception $Exception )
            {
                if ( $this->showErrorHeader( 404 ) ) {
                    return;
                }

                $this->_site = $this->_first_child;
            }

            // Sprachen Host finden
            if (
                isset($_SERVER['HTTP_HOST']) &&
                isset($vhosts[$_SERVER['HTTP_HOST']]) &&
                isset($vhosts[$_SERVER['HTTP_HOST']][$this->_lang]) &&
                $_SERVER['HTTP_HOST'] != $vhosts[$_SERVER['HTTP_HOST']][$this->_lang] &&

                // und es nicht der https host ist
                (int)$_SERVER['SERVER_PORT'] !== 443 &&
                QUI::conf('globals', 'httpshost') != 'https://'.$_SERVER['HTTP_HOST']
            )
            {
                $url = $this->_site->getUrlRewrited();
                $url = $vhosts[$_SERVER['HTTP_HOST']][$this->_lang] . URL_DIR . $url;
                $url = QUI\Utils\String::replaceDblSlashes($url);
                $url = 'http://'. $this->_project_prefix . $url;

                $this->showErrorHeader(301, $url);
            }

            // REQUEST setzen
            $site_params = $this->site_params;

            if ( is_array( $site_params ) && isset( $site_params[1] ) )
            {
                for ( $i = 1; $i < count($site_params); $i++ )
                {
                    if ( $i %2 != 0 )
                    {
                        $value = false;

                        if ( isset( $site_params[ $i + 1 ] ) ) {
                            $value = $site_params[ $i + 1 ];
                        }

                        $_REQUEST[ $site_params[ $i ] ] = $value;
                    }
                }
            }

        } else
        {
            $vhosts = $this->getVHosts();

            //$url = $this->_first_child->getUrlRewrited();

            /**
             * Sprache behandeln
             * Falls für die Sprache ein Host Eintrag existiert
             */
            if ( isset( $_SERVER['HTTP_HOST'] ) &&
                 isset( $vhosts[ $_SERVER['HTTP_HOST'] ] ) &&
                 isset( $vhosts[ $_SERVER['HTTP_HOST'] ][ $this->_lang ] ) )
            {
                $url = $vhosts[ $_SERVER['HTTP_HOST'] ][ $this->_lang ] . URL_DIR;
                $url = QUI\Utils\String::replaceDblSlashes( $url );
                $url = 'http://'. $this->_project_prefix . $url;

                if ( isset( $_SERVER['REQUEST_URI'] ) && $_SERVER['REQUEST_URI'] != URL_DIR )
                {
                    $message  = "\n\n===================================\n\n";
                    $message .= 'Rewrite 301 bei der wir nicht wissen wann es kommt. Rewrite.php Zeile 391 '."\n";
                    $message .= print_r( $_SERVER, true );

                    error_log($message, 3,
                        VAR_DIR .'log/rewrite'. date('-Y-m-d').'.log'
                    );

                    //$this->showErrorHeader(301, $url);
                }
            }
        }

        // Prüfen ob die aufgerufene URL gleich der von der Seite ist
        // Wenn nicht 301 auf die richtige
        $url = $this->getUrlFromSite(array(
            'site' => $this->_site
        ));

        $request_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '' ;
        $pos = strpos( $request_url, self::URL_PARAM_SEPERATOR );
        $end = strpos( $request_url, '.' );


        if ( $pos !== false )
        {
            $request_url = substr( $request_url, 0, $pos ) . substr( $request_url, $end );

            if ( $this->_site->getId() == 1 ) {
                $request_url = substr( $request_url, 0, $pos );
            }
        }

        $request_url = urldecode( $request_url );

        if ( strpos( $request_url, '?' ) !== false )
        {
            $request_url = explode( '?', $request_url );
            $request_url = $request_url[0];
        }

        if ( $request_url != $url ) {
            $this->_site->setAttribute( 'canonical', $url );
        }
    }

    /**
     * Parameter der Rewrite
     *
     * @param String $name
     * @return String|Bool
     */
    public function getParam($name)
    {
        $result = '';

        switch ( $name )
        {
            case 'project':
                $result = $this->_project_str;
            break;

            case 'project_prefix':
                $result = $this->_project_prefix;
            break;

            case 'template':
                $result = $this->_template_str;
            break;

            case 'lang':
                $result = $this->_lang;
            break;
        }

        if ( empty( $result ) ) {
            return false;
        }

        return $result;
    }

    /**
     * Return the current header code
     * @return Int
     */
    public function getHeaderCode()
    {
        return $this->_headerCode;
    }

    /**
     * Enter description here...
     *
     * @return String
     */
    public function getProjectPrefix()
    {
        return $this->_project_prefix;
    }

    /**
     * Enter description here...
     *
     * @param String $url
     * @param Bool $setpath
     * @return \QUI\Projects\Site|false
     */
    public function getSiteByUrl($url, $setpath=true)
    {
        // Sprache raus
        if ( $url == '' ) {
            return $this->_first_child;
        }

        $_url = explode( '/', $url );

        if ( count( $_url ) <= 1 )
        {
            // Erste Ebene
            $site_url          = explode( '.', $_url[0] );
            $this->site_params = explode( self::URL_PARAM_SEPERATOR, $site_url[0] );

            // für was? :
            // $this->_first_child->getAttribute('name') == str_replace('-', ' ', $this->site_params[0])
            if ( empty( $this->site_params[0] ) ) {
                return $this->_first_child;
            }

            $id = $this->_first_child->getChildIdByName(
                $this->site_params[ 0 ]
            );

            $Site = $this->getProject()->get( $id );

            if ( $setpath ) {
                $this->_set_path( $Site );
            }

            return $Site;
        }

        $Child = false;

        foreach ( $_url as $key => $val )
        {
            if ( $Child == false ) {
                $Child = $this->_first_child;
            }

            if ( strpos( $val, '.' ) !== false)
            {
                $site_url          = explode( '.', $val );
                $this->site_params = explode( self::URL_PARAM_SEPERATOR, $site_url[0] );

                $val = $this->site_params[0];
            }

            $id    = $Child->getChildIdByName( $val );
            $Child = $this->getProject()->get( $id );

            if ( $setpath ) {
                $this->_set_path( $Child );
            }
        }

        return $Child;
    }

    /**
     * Gibt das aktuelle Projekt zurück
     * Die Daten werden aus der URL gehohlt
     *
     * @return \QUI\Projects\Project
     */
    public function getProject()
    {
        if ( $this->_project ) {
            return $this->_project;
        }

        if ( is_string( $this->_project_str ) && !empty( $this->_project_str ) ) {
            return QUI\Projects\Manager::get();
        }

        // Vhosts
        $Project = $this->_getProjectByVhost();

        if ( $Project )
        {
            if ( $this->_lang &&
                 $this->_lang != $Project->getLang() )
            {
                $Project = QUI\Projects\Manager::getProject(
                    $Project->getName(),
                    $this->_lang
                );
            }

            return $Project;
        }

        /**
         * If a vhost wasn't found
         */

        // Falls keine Projekt Parameter existieren wird das standard Projekt verwendet
//        $Config = QUI\Projects\Manager::getConfig();
//        $config = $Config->toArray();

        // wenn standard vhost nicht der gewünschte ist, dann 404
        $host = '';

        if ( defined( 'HOST' ) ) {
            $host = str_replace( array('http://', 'https://'), '', HOST );
        }

        if ( $host != $_SERVER['HTTP_HOST'] && $this->_project )
        {
            $this->showErrorHeader( 404 );
            return $this->_project;
        }

        // Standard Projekt verwenden wenn kein vhost existiert
//        foreach ( $config as $p => $e )
//        {
//            if ( isset( $e['standard']) && $e['standard'] == 1 )
//            {
//                $pname = $p;
//                break;
//            }
//        }

        try
        {
            $Project = QUI\Projects\Manager::get();

        } catch ( QUI\Exception $Exception )
        {
            $Project = false;
        }

        if ( $Project && is_object( $Project ) )
        {
            $this->_project = $Project;

            return $this->_project;
        }

        // Projekt mit der Sprache exitiert nicht
        $this->showErrorHeader( 404 );

        $Project = QUI\Projects\Manager::getStandard();

        $this->_project = $Project;
        $this->_lang    = $Project->getLang();

        QUI::getLocale()->setCurrent( $Project->getLang() );

        return $Project;
    }

    /**
     * Return the prject by the vhost, if a vhost exist
     *
     * @return \QUI\Projects\Project|false
     */
    protected function _getProjectByVhost()
    {
        $vhosts = $this->getVHosts();

        // Vhosts
        $http_host = '';

        if ( isset( $_SERVER['HTTP_HOST'] ) ) {
            $http_host = $_SERVER['HTTP_HOST'];
        }

        if ( !isset( $vhosts[ $http_host ] ) ) {
             return false;
        }

        if ( !isset( $vhosts[ $http_host ]['project'] ) ) {
            return false;
        }

        $pname = $vhosts[ $http_host ]['project'];

        //$lang = false;
        if ( isset( $vhosts[ $http_host ]['lang'] ) && !$this->_lang ) {
            $this->_lang = $vhosts[ $http_host ]['lang'];
        }

        $template = false;

        if ( isset( $vhosts[ $_SERVER['HTTP_HOST'] ]['template'] ) ) {
            $template = $vhosts[ $_SERVER['HTTP_HOST'] ]['template'];
        }

        try
        {
            $Project = \QUI::getProject(
                $pname,
                $this->_lang,
                $template
            );

        } catch( QUI\Exception $Exception )
        {
            // nothing todo
            $Project = false;
        }

        if ( $Project )
        {
            $this->_project = $Project;

            QUI::getLocale()->setCurrent(
                $Project->getAttribute( 'lang' )
            );

            return $Project;
        }

        return false;
    }

    /**
     * Gibt die Vhosts zurück
     *
     * @return Array
     */
    public function getVHosts()
    {
        if ( !empty( $this->_vhosts ) || is_array( $this->_vhosts ) ) {
            return $this->_vhosts;
        }

        $this->_vhosts = QUI::vhosts();

        return $this->_vhosts;
    }

    /**
     * Gibt das Suffix des Aufrufs zurück
     *
     * @return String .print / .html
     */
    public function getSuffix()
    {
        return $this->_suffix;
    }

    /**
     * Error Header übermitteln
     *
     * @param Integer $code - Error Code
     * @param String $url - Bei manchen Error Codes muss eine URL übergeben werden (30*)
     * @return Bool
     */
    public function showErrorHeader($code=404, $url='')
    {
        // Im Admin gibt es keine Error Header
        if ( defined( 'ADMIN' ) ) {
            return false;
        }

        $this->_headerCode = $code;

        switch ( $code )
        {
            // Client Request Redirected
            case 301:
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: ".$url);
            break;

            case 302:
                header("HTTP/1.1 302 Moved Temporarily");
                header("Location: ".$url);
            break;

            case 303:
                header("HTTP/1.1 303 See Other");
                header("Location: ".$url);
            break;

            case 304:
                header("HTTP/1.1 304 Not Modified");
                header("Location: ".$url);
            break;

            case 305:
                header("HTTP/1.1 305 Use Proxy");
                header("Location: ".$url);
            break;

            // Client Request Errors
            case 404:
            default:

                $this->_headerCode = 404;

                header("HTTP/1.0 404 Not Found");

                if (!defined('ERROR_HEADER')) {
                    define('ERROR_HEADER', 404);
                }

                try
                {
                    $ErrorSite = $this->getErrorSite();

                    $this->_project = $ErrorSite->getProject();
                    $this->_site    = $ErrorSite;

                    return true;

                } catch ( QUI\Exception $Exception )
                {
                    QUI\System\Log::writeException( $Exception );
                }

            break;

            case 503:
                header('HTTP/1.1 503 Service Temporarily Unavailable');
                header('Status: 503 Service Temporarily Unavailable');
                header('Retry-After: 3600');
                header('X-Powered-By:');
            break;
        }

        return true;
    }

    /**
     * Shows the 404 site
     *
     * @return QUI\Projects\Site
     * @throws QUI\Exception
     */
    public function getErrorSite()
    {
        $vhosts = $this->getVHosts();

        // Falls der Host eine eigene Fehlerseite zugewiesen bekommen hat
        if ( isset( $vhosts[ $_SERVER['HTTP_HOST'] ]) &&
             isset( $vhosts[ $_SERVER['HTTP_HOST'] ]['error'] ) )
        {
            $error = $vhosts[ $_SERVER['HTTP_HOST'] ]['error'];
            $error = explode( ',', $error );

            try
            {
                if ( !isset( $error[0] ) || !isset( $error[1] ) || !isset( $error[2] ))
                {
                    $Standard = QUI::getProjectManager()->getStandard();

                    $error[0] = $Standard->getName();
                    $error[1] = $Standard->getLang();
                    $error[2] = 1;
                }

                $Project = QUI::getProject( $error[0], $error[1] );
                $Site    = $Project->get( $error[2] );

                return $Site;

            } catch ( QUI\Exception $Exception )
            {
                // no error site found, dry it global
            }
        }

        if ( isset( $vhosts[ 404 ] ) &&
             isset( $vhosts[ 404 ]['id'] ) &&
             isset( $vhosts[ 404 ]['project'] ) &&
             isset( $vhosts[ 404 ]['lang'] ) )
        {
            try
            {
                $Project = \QUI::getProject(
                    $vhosts[ 404 ]['project'],
                    $vhosts[ 404 ]['lang']
                );

                $Site = $Project->get( $vhosts[404]['id'] );

                return $Site;

            } catch ( QUI\Exception $Exception )
            {

            }
        }

        throw new QUI\Exception( 'Error Site not exist', 404 );
    }

    /**
     * Gibt die aktuelle Seite zurück
     * @return \QUI\Projects\Site
     */
    public function getSite()
    {
        if ( isset( $this->_site ) && is_object( $this->_site ) ) {
            return $this->_site;
        }

        if ( $this->showErrorHeader() ) {
            return $this->_site;
        }

        $Project = $this->getProject();

        return $Project->firstChild();
    }

    /**
     * Aktuelles Site Objekt überschreiben
     *
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     */
    public function setSite($Site)
    {
        $this->_site = $Site;
    }

    /**
     * Den aktuelle Pfad bekommen
     *
     * @param Bool $start - where to start
     * @param Bool $me    - Pfad mit der aktuellen Seite ausgeben
     * @return array
     */
    public function getPath($start=true, $me=true)
    {
        $path = $this->_path;

        if ( !isset( $path[0] ) ) {
            return array();
        }

        if ( $start == true )
        {
            if ( isset( $path ) && is_array( $path ) &&
               ( !isset( $path[0] ) || $path[0]->getId() != 1 ) )
            {
                array_unshift( $path, $this->_first_child );
            }
        }

        if ( $me == false )
        {

        }

        return $path;
    }

    /**
     * Set the current path
     *
     * @param array $path
     */
    public function setPath($path)
    {
        $this->_path = $path;
    }

    /**
     * Prüft ob die Seite im Pfad ist
     *
     * @param Int $id - ID der Seite welche geprüft werden soll
     * @return Bool
     */
    public function isIdInPath($id)
    {
        return in_array( $id, $this->_ids_in_path ) ? true : false;
    }

    /**
     * Setzt eine Seite in den Path
     *
     * @param \QUI\Projects\Site $Site - seite die hinzugefügt wird
     */
    private function _set_path(QUI\Projects\Site $Site)
    {
        $this->_path[] = $Site;
        array_push($this->_ids_in_path, $Site->getId());
    }

    /**
     * Outputfilter
     * Geht HTML durch und ruft die dazugehörigen Funktionen auf um URLs umzuwandeln
     *
     * @param String $output - html, text
     * @return String
     */
    public function outputFilter($output)
    {
        // Bilder umschreiben
        $output = preg_replace_callback(
            '#<img([^>]*)>#i',
            array(&$this, "_output_images"),
            $output
        );

        // restliche Dateien umschreiben
        $output = preg_replace_callback(
            '#(href|src|value)="(image.php)\?([^"]*)"#',
            array(&$this, "_output_files"),
            $output
        );

        //Links umschreiben
        $output = preg_replace_callback(
            '#(href|src|action|value|data)="(index.php)\?([^"]*)"#',
            array(&$this, "_output_links"),
            $output
        );

        // SPAM Protection
        if ( MAIL_PROTECT )
        {
            $output = str_replace('</body>', '<!-- [begin] QUIQQER Mail SPAM Bot Protection --><iframe src="'. URL_BIN_DIR .'mail_protection.php" style="position: absolute; display: none; width: 1px; height: 1px;" name="mail_protection" title="mail_protection"></iframe><!-- [begin] P.MS Mail SPAM Bot Protection --></body>', $output);

            $output = preg_replace_callback(
              '#(href)="(mailto:)([^"]*)"#',
              array(&$this, "_output_mail"),
              $output
            );
        }

        $this->setOutputContent( $output );

        // fire Rewrite::onOutput
        \QUI::getEvents()->fireEvent('QUI::rewriteOutput', array(
            'Rewrite' => $this
        ));

        return $this->getOutputContent();
    }

    /**
     * Output Content setzen
     *
     * @param String $str
     */
    public function setOutputContent($str)
    {
        $this->_output_content = $str;
    }

    /**
     * Output Content bekommen
     *
     * @return String
     */
    public function getOutputContent()
    {
        return $this->_output_content;
    }


    /**
     * Mail Protection gegen SPAM
     * Wandelt die Mail Addressen so um das ein BOT nichts mit anfangen kann
     *
     * @param String $output
     * @return String
     */
    public function _output_mail($output)
    {
        if ( isset( $output[3] ) && strpos( $output[3], '@' ) !== false )
        {
            list($user, $domain) = explode("@", $output[3]);
            return 'href="'.URL_DIR.'[mailto]'.$user.'[at]'.$domain.'" target="mail_protection"';
        }

        return $output[0];
    }

    /**
     * Wandelt den Bildepfad in einen sprechenden Pfad um
     *
     * @param String $output
     * @return String
     */
    public function _output_files($output)
    {
        try
        {
            $url = MediaUtils::getRewritedUrl( 'image.php?'. $output[3] );

        } catch ( \QUI\Exception $Excxeption )
        {
            $url = '';
        }


        return $output[1].'="'. $url .'"';
    }

    /**
     * Wandelt den Bilderpfad in einen sprechenden Pfad um
     *
     * @param String $output
     * @return String
     */
    public function _output_images($output)
    {
        $img = $output[0];

        // Falls in der eigenen Sammlung schon vorhanden
        if ( isset( $this->_image_cache[ $img ] ) ) {
            return $this->_image_cache[ $img ];
        }

        if ( !MediaUtils::isMediaUrl( $img ) ) {
            return $output[0];
        }

        $att = QUI\Utils\String::getHTMLAttributes( $img );

        if ( !isset( $att['src'] ) ) {
            return $output[0];
        }

        $src = str_replace( '&amp;', '&', $att['src'] );

        unset( $att['src'] );

        if ( !isset( $att['alt'] ) || !isset( $att['title'] ) )
        {
            try
            {
                $Image = MediaUtils::getImageByUrl( $src );

                $att['alt']   = $Image->getAttribute('alt') ? $Image->getAttribute('alt') : '';
                $att['title'] = $Image->getAttribute('title') ? $Image->getAttribute('title') : '';

            } catch ( QUI\Exception $Exception )
            {

            }
        }

        $this->_image_cache[ $img ] = MediaUtils::getImageHTML( $src, $att );

        return $this->_image_cache[ $img ];
    }

    /**
     * Wandelt eine PCSG URL in eine sprechende URL um
     *
     * @param String $output
     * @return String
     */
    public function _output_links($output)
    {
        // no php url
        if ( $output[2] !== 'index.php' ) {
            return $output[0];
        }

        $output = str_replace( '&amp;','&', $output );   // &amp; fix
        $output = str_replace( '〈=','&lang=', $output ); // URL FIX

        $components = $output[3];

        // Falls in der eigenen Sammlung schon vorhanden
        if ( isset( $this->_url_cache[ $components ] ) ) {
            return $output[1] .'="'. $this->_url_cache[ $components ] .'"';
        }

        $parseUrl = parse_url( $output[2] .'?'. $components );

        if ( !isset( $parseUrl['query'] ) || empty( $parseUrl['query'] ) ) {
            return $output[0];
        }

        $urlQuery = $parseUrl['query'];

        if ( strpos( $urlQuery, 'project' ) === false ||
             strpos( $urlQuery, 'lang' ) === false ||
             strpos( $urlQuery, 'id' ) === false )
        {
            // no quiqqer url
            return $output[0];
        }

        // maybe a quiqqer url ?
        parse_str( $urlQuery, $urlQueryParams );

        try
        {
            $url    = $this->getUrlFromSite( $urlQueryParams );
            $anchor = '';

            if ( isset( $parseUrl['fragment'] ) && !empty( $parseUrl['fragment'] ) ) {
                $anchor = '#'. $parseUrl['fragment'];
            }

            $this->_url_cache[ $components ] = $url . $anchor;

            return $output[1] .'="'. $url . $anchor .'"';

        } catch ( \Exception $Exception )
        {
            QUI\System\Log::writeException( $Exception );
        }

        return $output[0];
    }

    /**
     * Sonderzeichen aus dem Namen entfernen damit die URL rein aussieht
     *
     * @param String $url
     * @param Bool $slash - Soll Slash ersetzt werden oder nicht
     * @return String
     */
    static function replaceUrlSigns($url, $slash=false)
    {
        $search = array('%20', '.', ' ', '_');

        if ( $slash ) {
            $search[] = '/';
        }

        $url = str_replace($search, '-', $url);

        if ( substr($url,-5) == '_html' ) {
            $url = substr($url, 0, -5).'.html';
        }

        return $url;
    }

    /**
     * Return the url params as index array
     *
     * @return Array
     */
    public function getUrlParamsList()
    {
        if ( !isset( $_REQUEST['_url'] ) ) {
            return array();
        }

        $url = $_REQUEST['_url'];
        $url = explode('.', $url );
        $url = explode('_', $url[ 0 ] );

        array_shift( $url );

        return $url;
    }

    /**
     * Gibt die sprechende URL einer Seite zurück
     *
     * @param array $params
     * 	$params['site'] => (object) Site
     *
     * oder
     * 	$params['id'] => (int) Id - Id der Seite
     * 	$params['lang'] => (String) lang - Sprache der Seite
     * 	$params['project'] => (String) project - Projektnamen
     *
     * @return String
     * @throws QUI\Exception
     */
    public function getUrlFromSite($params=array())
    {
        // Falls ein Objekt übergeben wird
        if ( isset( $params['site'] ) && is_object( $params['site'] ) )
        {
            $Project = $params['site']->getProject();
            $id      = $params['site']->getId();

            $lang    = $Project->getAttribute( 'lang' );
            $project = $Project->getAttribute( 'name' );

            unset( $params['site'] );

        } else
        {
            if ( isset( $params['id'] ) ) {
                $id = $params['id'];
            }

            if ( isset( $params['project'] ) ) {
                $project = $params['project'];
            }

            if ( isset( $params['lang'] ) ) {
                $lang = $params['lang'];
            }

            unset( $params['project'] );
            unset( $params['id'] );
            unset( $params['lang'] );
        }

        // Wenn nicht alles da ist dann wird ein Exception geworfen
        if ( !isset( $id ) || !isset( $project ) )
        {
            throw new QUI\Exception(
                'Params missing Rewrite::getUrlFromPage'
            );
        }

        QUI\Utils\System\File::mkdir( VAR_DIR .'cache/links' );

        $link_cache_dir = VAR_DIR .'cache/links/'. $project .'/';
        QUI\Utils\System\File::mkdir( $link_cache_dir );

        $link_cache_file = $link_cache_dir . $id .'_'. $project .'_'. $lang;

        $url = URL_DIR;
        // Falls es das Cachefile schon gibt
        if ( file_exists( $link_cache_file ) )
        {
            $url = file_get_contents( $link_cache_file );
            $url = $this->_extendUrlWidthPrams( $url, $params );

        } else
        {
            // Wenn nicht erstellen
            try
            {
                $Project = \QUI::getProject( $project, $lang ); /* @var $Project \QUI\Projects\Project */
                $Site    = $Project->get( (int)$id ); /* @var $s \QUI\Projects\Site */

            } catch ( QUI\Exception $Exception )
            {
                // Seite existiert nicht
                return '';
            }

            $_params = array(); // Temp Params, nur um die Endung mitzuliefern

            if ( isset( $params['suffix'] ) ) {
                $_params['suffix'] = $params['suffix'];
            }

            $url = URL_DIR . $Site->getUrlRewrited( $_params );

            // Link Cache
            file_put_contents(
                $link_cache_file,
                str_replace( '.print', '.html', $url )
            );

            $url = $this->_extendUrlWidthPrams( $url, $params );
        }

        $vhosts = $this->getVHosts();

        if ( !isset( $Project ) ) {
            $Project = $this->getProject();
        }

        /**
         * Sprache behandeln
         */
        if ( isset( $vhosts[ $_SERVER['HTTP_HOST'] ] ) &&
             isset( $vhosts[ $_SERVER['HTTP_HOST'] ][ $lang ] ) )
        {
            if (// wenn ein Host eingetragen ist
                $lang != $Project->getAttribute('lang') ||

                // falls der jetzige host ein anderer ist als der vom link,
                // dann den host an den link setzen
                $vhosts[$_SERVER['HTTP_HOST']][$lang] != $_SERVER['HTTP_HOST']
            )
            {
                // und die Sprache nicht die vom jetzigen Projekt ist
                // dann Host davor setzen
                $url = $vhosts[$_SERVER['HTTP_HOST']][$lang] . URL_DIR . $url;
                $url = QUI\Utils\String::replaceDblSlashes($url);
                $url = 'http://'. $this->_project_prefix . $url;

                return $url;
            }

            $url = URL_DIR . $this->_project_prefix . $url;
            $url = QUI\Utils\String::replaceDblSlashes( $url );

        } else if ( $Project->getAttribute('default_lang') !== $lang )
        {
            // Falls kein Host Eintrag gibt
            // Und nicht die Standardsprache dann das Sprachenflag davor setzen
            $url = URL_DIR . $this->_project_prefix . $lang .'/'. $url;
            $url = QUI\Utils\String::replaceDblSlashes( $url );
        }

        // falls host anderst ist, dann muss dieser dran gehängt werden
        // damit kein doppelter content entsteht
        if ( $_SERVER['HTTP_HOST'] != $Project->getHost() )
        {
            $url = $Project->getHost() . QUI\Utils\String::replaceDblSlashes( URL_DIR . $url );

            if ( strpos( $url, 'http://' ) === false ) {
                $url = 'http://'. $url;
            }
        }

        return $url;
    }

    /**
     * Erweitert die URL um Params
     *
     * @param String $url
     * @param Array $params
     * @return String
     */
    private function _extendUrlWidthPrams($url, $params)
    {
        if ( count( $params ) <= 0 ) {
            return $url;
        }

        $exp = explode( '.', $url );
        $url = $exp[0];

        foreach ( $params as $param => $value )
        {
            if ( is_integer( $param ) )
            {
                $url .= self::URL_PARAM_SEPERATOR . $value;
                continue;
            }

            if ( $param == 'suffix' ) {
                continue;
            }

            if ( $param === "0" )
            {
                $url .= self::URL_PARAM_SEPERATOR . $value;
                continue;
            }

            $url .= self::URL_PARAM_SEPERATOR . $param . self::URL_PARAM_SEPERATOR . $value;
        }

        if ( isset( $params['suffix'] ) ) {
            return $url .'.'. $params['suffix'];
        }

        return $url.'.'. ( isset($exp[1] ) ? $exp[1] : 'html' );
    }
}
