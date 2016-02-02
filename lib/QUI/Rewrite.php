<?php

/**
 * This file contains \QUI\Rewrite
 */

namespace QUI;

use QUI;
use QUI\Projects\Media\Utils as MediaUtils;

use \Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\HttpFoundation\Response;

/**
 * Rewrite - URL Verwaltung (sprechende URLS)
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 * @package QUI
 *
 * @todo  must be rewrited, spaghetti code :(
 * @todo  create new concept
 * @todo  translate comments
 *
 * @event onQUI::Request
 * @event onQUI::Access
 * @event onQUI::RewriteOutput [ Rewrite ]
 */
class Rewrite
{
    const URL_PARAM_SEPERATOR = '_';
    const URL_SPACE_CHARACTER = '-';
    const URL_PROJECT_CHARACTER = '^';
    const URL_DEFAULT_SUFFIX = '.html';

    static public $SUFFIX = false;

    /**
     * site request parameter
     *
     * @var array
     */
    public $site_params = array();

    /**
     * active project
     *
     * @var \QUI\Projects\Project
     */
    private $project;

    /**
     * active project
     *
     * @var string
     */
    private $project_str = '';

    /**
     * active template
     *
     * @var string
     */
    private $template_str = false;

    /**
     * if project prefix is set
     *
     * @var string
     */
    private $project_prefix = '';

    /**
     * project lang
     *
     * @var string
     */
    private $lang = false;

    /**
     * active site
     *
     * @var \QUI\Projects\Site
     */
    private $site = null;

    /**
     * first site of the project
     *
     * @var \QUI\Projects\Site
     */
    private $first_child;

    /**
     * current site path
     *
     * @var array
     */
    private $path = array();

    /**
     * current site path - but only the ids
     *
     * @var array
     */
    private $ids_in_path = array();

    /**
     * internal url cache
     *
     * @var array
     */
    private $url_cache = array();

    /**
     * internal image link cache
     *
     * @var array
     */
    private $image_cache = array();

    /**
     * loaded vhosts
     *
     * @var array
     */
    private $vhosts = false;

    /**
     * current suffix, (.html, .pdf, .print)
     *
     * @var string
     */
    private $suffix = '.html';

    /**
     * the html output
     *
     * @var string
     */
    private $output_content = '';

    /**
     * Standard header code
     *
     * @var int
     */
    private $headerCode = 200;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->Events = new QUI\Events\Event();
    }

    /**
     * Return the default suffix eq: .html or ''
     *
     * @return string
     */
    public static function getDefaultSuffix()
    {
        if (self::$SUFFIX !== false) {
            return self::$SUFFIX;
        }

        $conf = (int)QUI::conf('globals', 'htmlSuffix');

        if ($conf === 0) {
            self::$SUFFIX = '';
        } else {
            self::$SUFFIX = '.html';
        }

        return self::$SUFFIX;
    }

    /**
     * Request verarbeiten
     */
    public function exec()
    {
        if (!isset($_REQUEST['_url'])) {
            $_REQUEST['_url'] = '';
        }

        //wenn seite existiert, dann muss nichts mehr gemacht werden
        if (isset($this->site) && $this->site) {
            QUI::getEvents()
                ->fireEvent('request', array($this, $_REQUEST['_url']));

            return;
        }

        $vhosts        = $this->getVHosts();
        $defaultSuffix = self::getDefaultSuffix();

        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = '';
        }

        // 301 abfangen
        if (isset($vhosts['301'])
            && isset($vhosts['301'][$_SERVER['HTTP_HOST']])
        ) {
            $url  = $_REQUEST['_url'];
            $host = $vhosts['301'][$_SERVER['HTTP_HOST']];

            QUI::getEvents()
                ->fireEvent('request', array($this, $_REQUEST['_url']));

            $this->showErrorHeader(301, $host . '/' . $url);
            exit;
        }

        // Kategorien aufruf
        // Aus url/kat/ wird url/kat.html
        if (!empty($_REQUEST['_url']) && substr($_REQUEST['_url'], -1) == '/'
            && strpos($_REQUEST['_url'], 'media/cache') === false
        ) {
            $_REQUEST['_url'] = substr($_REQUEST['_url'], 0, -1) . $defaultSuffix;

            QUI::getEvents()
                ->fireEvent('request', array($this, $_REQUEST['_url']));

            // 301 weiterleiten
            $this->showErrorHeader(301, URL_DIR . $_REQUEST['_url']);
        }

        // Suffix
        if (substr($_REQUEST['_url'], -6) == '.print') {
            $this->suffix = '.print';
        }

        if (substr($_REQUEST['_url'], -4) == '.pdf') {
            $this->suffix = '.pdf';
        }

        if (!empty($_REQUEST['_url'])) {
            $_url = explode('/', $_REQUEST['_url']);

            // projekt
            if (isset($_url[0])
                && substr($_url[0], 0, 1) == self::URL_PROJECT_CHARACTER
            ) {
                $this->project_str = str_replace(
                    $defaultSuffix,
                    '',
                    substr($_url[0], 1)
                );

                // if a second project_character, its the template
                if (strpos($this->project_str, self::URL_PROJECT_CHARACTER)) {
                    $_project_split = explode(
                        self::URL_PROJECT_CHARACTER,
                        $this->project_str
                    );

                    $this->project_str  = $_project_split[0];
                    $this->template_str = $_project_split[1];
                }

                $this->project_prefix
                    = self::URL_PROJECT_CHARACTER . $this->project_str . '/';

                if ($this->template_str) {
                    $this->project_prefix = self::URL_PROJECT_CHARACTER .
                                            $this->project_str;

                    $this->project_prefix .= self::URL_PROJECT_CHARACTER .
                                             $this->template_str . '/';
                }


                unset($_url[0]);

                $_new_url = array();

                foreach ($_url as $elm) {
                    $_new_url[] = $elm;
                }

                $_url = $_new_url;
            }

            // Sprache
            if (isset($_url[0])
                && (strlen($_url[0]) == 2
                    || strlen(str_replace($defaultSuffix, '', $_url[0])) == 2)
            ) {
                $this->lang = str_replace($defaultSuffix, '', $_url[0]);

                QUI::getLocale()->setCurrent($this->lang);

                unset($_url[0]);

                $_new_url = array();

                foreach ($_url as $elm) {
                    $_new_url[] = $elm;
                }

                $_url = $_new_url;

                // Wenns ein Hosteintrag mit der Sprache gibt, dahin leiten
                // und es nicht der https host ist
                // @todo https host nicht über den port prüfen, zu ungenau
                if (isset($_SERVER['HTTP_HOST'])
                    && isset($vhosts[$_SERVER['HTTP_HOST']])
                    && isset($vhosts[$_SERVER['HTTP_HOST']][$this->lang])
                    && !empty($vhosts[$_SERVER['HTTP_HOST']][$this->lang])
                    && (int)$_SERVER['SERVER_PORT'] !== 443
                    && QUI::conf('globals', 'httpshost') !=
                       'https://' . $_SERVER['HTTP_HOST']
                ) {
                    $url = implode('/', $_url);
                    $url = $vhosts[$_SERVER['HTTP_HOST']][$this->lang] . URL_DIR
                           . $url;
                    $url = QUI\Utils\StringHelper::replaceDblSlashes($url);
                    $url = 'http://' . $this->project_prefix . $url;

                    QUI::getEvents()
                        ->fireEvent('request', array($this, $_REQUEST['_url']));

                    $this->showErrorHeader(301, $url);
                }
            }

            $_REQUEST['_url'] = implode('/', $_url);

            if (!count($_url)) {
                unset($_REQUEST['_url']);
            }
        }

        // Media Center Datei, falls nicht im Cache ist
        if (isset($_REQUEST['_url'])
            && strpos($_REQUEST['_url'], 'media/cache') !== false
        ) {
            QUI::getEvents()
                ->fireEvent('request', array($this, $_REQUEST['_url']));

            $imageNotError = false;
            $Item          = false;

            try {
                $Item = MediaUtils::getElement($_REQUEST['_url']);

                if (strpos($_REQUEST['_url'], '__') !== false) {
                    $lastpos_ul = strrpos($_REQUEST['_url'], '__') + 2;
                    $pos_dot    = strpos($_REQUEST['_url'], '.', $lastpos_ul);

                    $size = substr(
                        $_REQUEST['_url'],
                        $lastpos_ul,
                        ($pos_dot - $lastpos_ul)
                    );

                    $part_size = explode('x', $size);

                    if (count($part_size) > 2) {
                        $imageNotError = true;
                    }

                    if (isset($part_size[0])) {
                        $width = (int)$part_size[0];
                    }

                    if (isset($part_size[1])) {
                        $height = (int)$part_size[1];
                    }
                }

            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addDebug($Exception->getMessage());

                $imageNotError = true;
            }

            if ($Item === false || $imageNotError) {
                $Redirect = new RedirectResponse(
                    $this->getErrorSite()->getUrlRewritten()
                );

                $Redirect->setStatusCode(Response::HTTP_NOT_FOUND);
                $Redirect->send();
                exit;
            }

            if ($Item->getType() === 'QUI\\Projects\\Media\\Image') {
                /* @var $Item \QUI\Projects\Media\Image */
                if (!isset($width) || empty($width)) {
                    $width = false;
                }

                if (!isset($height) || empty($height)) {
                    $height = false;
                }

                $file = $Item->createSizeCache($width, $height);
            } else {
                /* @var $Item \QUI\Projects\Media\File */
                $file = $Item->createCache();
            }

            if (!file_exists($file)) {
                $Redirect = new RedirectResponse(
                    $this->getErrorSite()->getUrlRewritten()
                );

                $Redirect->setStatusCode(Response::HTTP_NOT_FOUND);
                $Redirect->send();
                exit;
            }

            // Dateien direkt im Browser ausgeben, da Cachedatei noch nicht verfügbar war
            header("Content-Type: " . $Item->getAttribute('mime_type'));
            header("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Pragma: public");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Accept-Ranges: bytes");
            header("Content-Disposition: inline; filename=\"" . pathinfo($file, PATHINFO_BASENAME) . "\"");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

            $fo_image = fopen($file, "r");
            $fr_image = fread($fo_image, filesize($file));
            fclose($fo_image);

            echo $fr_image;
            exit;
        }

        if (!isset($_REQUEST['_url'])) {
            $_REQUEST['_url'] = '';
        }

        QUI::getEvents()
            ->fireEvent('request', array($this, $_REQUEST['_url']));

        // Falls kein suffix dann 301 weiterleiten auf .html
        if (!empty($_REQUEST['_url']) && substr($_REQUEST['_url'], -1) != '/') {
            $pathinfo = pathinfo($_REQUEST['_url']);

            if (!isset($pathinfo['extension']) && $defaultSuffix !== '') {
                $url = URL_DIR . $_REQUEST['_url'] . $defaultSuffix;
                $url = QUI\Utils\StringHelper::replaceDblSlashes($url);

                // Falls keine Extension (.html) dann auf .html
                // nur wenn $defaultSuffix == ''
                $this->showErrorHeader(301, $url);

            } elseif ($defaultSuffix === ''
                      && isset($pathinfo['extension'])
                      && $pathinfo['extension'] == 'html'
            ) {
                // Falls Extension .html und suffix leer ist
                // dann auf kein suffix leiten
                $this->showErrorHeader(
                    301,
                    str_replace('.html', '', URL_DIR . $_REQUEST['_url'])
                );
            }
        }

        $this->first_child = $this->getProject()->firstChild();

        if (!$this->site) {
            $this->site = $this->first_child;
        }

        if (!empty($_REQUEST['_url'])) {
            // URL Parameter filtern
            try {
                $this->site = $this->getSiteByUrl($_REQUEST['_url'], true);

            } catch (QUI\Exception $Exception) {
                $Site = $this->existRegisterPath(
                    $_REQUEST['_url'],
                    $this->getProject()
                );

                if ($Site) {
                    $Site->setAttribute('canonical', $_REQUEST['_url']);

                    $this->site = $Site;

                    return;
                }

                if ($this->showErrorHeader(404)) {
                    return;
                }

                $this->site = $this->first_child;
            }

            // Sprachen Host finden
            // und es nicht der https host ist
            if (isset($_SERVER['HTTP_HOST'])
                && isset($vhosts[$_SERVER['HTTP_HOST']])
                && isset($vhosts[$_SERVER['HTTP_HOST']][$this->lang])
                && $_SERVER['HTTP_HOST']
                   != $vhosts[$_SERVER['HTTP_HOST']][$this->lang]
                && (int)$_SERVER['SERVER_PORT'] !== 443
                && QUI::conf('globals', 'httpshost') !=
                   'https://' . $_SERVER['HTTP_HOST']
            ) {
                $url = $this->site->getUrlRewritten();
                $url
                     = $vhosts[$_SERVER['HTTP_HOST']][$this->lang] . URL_DIR . $url;
                $url = QUI\Utils\StringHelper::replaceDblSlashes($url);
                $url = 'http://' . $this->project_prefix . $url;

                $this->showErrorHeader(301, $url);
            }

            // REQUEST setzen
            $site_params = $this->site_params;

            if (is_array($site_params) && isset($site_params[1])) {
                for ($i = 1; $i < count($site_params); $i++) {
                    if ($i % 2 != 0) {
                        $value = false;

                        if (isset($site_params[$i + 1])) {
                            $value = $site_params[$i + 1];
                        }

                        $_REQUEST[$site_params[$i]] = $value;
                    }
                }
            }

        } else {
            $vhosts = $this->getVHosts();

            //$url = $this->first_child->getUrlRewrited();

            /**
             * Sprache behandeln
             * Falls für die Sprache ein Host Eintrag existiert
             */
            if (isset($_SERVER['HTTP_HOST'])
                && isset($vhosts[$_SERVER['HTTP_HOST']])
                && isset($vhosts[$_SERVER['HTTP_HOST']][$this->lang])
            ) {
//                $url = $vhosts[$_SERVER['HTTP_HOST']][$this->lang] . URL_DIR;
//                $url = QUI\Utils\string::replaceDblSlashes($url);
//                $url = 'http://' . $this->project_prefix . $url;

                if (isset($_SERVER['REQUEST_URI'])
                    && $_SERVER['REQUEST_URI'] != URL_DIR
                ) {
                    $message = "\n\n===================================\n\n";
                    $message .= 'Rewrite 301 bei der wir nicht wissen wann es kommt. Rewrite.php Zeile 391 ';
                    $message .= "\n";
                    $message .= print_r($_SERVER, true);

                    error_log(
                        $message,
                        3,
                        VAR_DIR . 'log/rewrite' . date('-Y-m-d') . '.log'
                    );

                    //$this->showErrorHeader(301, $url);
                }
            }
        }

        // Prüfen ob die aufgerufene URL gleich der von der Seite ist
        // Wenn nicht 301 auf die richtige
        $url = $this->getUrlFromSite(array(
            'site' => $this->site
        ));

        $request_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI']
            : '';

        $pos = strpos($request_url, self::URL_PARAM_SEPERATOR);
        $end = strpos($request_url, '.');


        if ($pos !== false) {
            $request_url
                = substr($request_url, 0, $pos) . substr($request_url, $end);

            if ($this->site->getId() == 1) {
                $request_url = substr($request_url, 0, $pos);
            }
        }

        $request_url = urldecode($request_url);

        if (strpos($request_url, '?') !== false) {
            $request_url = explode('?', $request_url);
            $request_url = $request_url[0];
        }

        if ($request_url != $url) {
            $this->site->setAttribute('canonical', $url);
        }
    }

    /**
     * Parameter der Rewrite
     *
     * @param string $name
     *
     * @return string|boolean
     */
    public function getParam($name)
    {
        $result = '';

        switch ($name) {
            case 'project':
                $result = $this->project_str;
                break;

            case 'project_prefix':
                $result = $this->project_prefix;
                break;

            case 'template':
                $result = $this->template_str;
                break;

            case 'lang':
                $result = $this->lang;
                break;
        }

        if (empty($result)) {
            return false;
        }

        return $result;
    }

    /**
     * Return the current header code
     *
     * @return Int
     */
    public function getHeaderCode()
    {
        return $this->headerCode;
    }

    /**
     * Enter description here...
     *
     * @return string
     */
    public function getProjectPrefix()
    {
        return $this->project_prefix;
    }

    /**
     * Enter description here...
     *
     * @param string $url
     * @param boolean $setpath
     *
     * @return \QUI\Projects\Site|false
     */
    public function getSiteByUrl($url, $setpath = true)
    {
        // Sprache raus
        if ($url == '') {
            return $this->first_child;
        }

        $_url = explode('/', $url);

        if (count($_url) <= 1) {
            // Erste Ebene
            $site_url = explode('.', $_url[0]);

            $this->site_params = explode(
                self::URL_PARAM_SEPERATOR,
                $site_url[0]
            );

            // für was? :
            // $this->first_child->getAttribute('name') == str_replace('-', ' ', $this->site_params[0])
            if (empty($this->site_params[0])) {
                return $this->first_child;
            }

            $id = $this->first_child->getChildIdByName(
                $this->site_params[0]
            );

            $Site = $this->getProject()->get($id);

            if ($setpath) {
                $this->setIntoPath($Site);
            }

            return $Site;
        }

        $Child = false;

        for ($i = 0, $len = count($_url); $i < $len; $i++) {
            if ($Child == false) {
                $Child = $this->first_child;
            }

            $val = $_url[$i];

            // letzte seite = url params raushohlen
            if ($len === $i + 1) {
                $defaultSuffix = QUI\Rewrite::getDefaultSuffix();
                $suffixLen     = mb_strlen($defaultSuffix);

                if ($defaultSuffix != ''
                    && mb_substr($val, $suffixLen * -1) == $defaultSuffix
                ) {
                    $site_url = mb_substr($val, 0, $suffixLen * -1);
                } else {
                    $site_url = $val;
                }

                $this->site_params = explode(
                    self::URL_PARAM_SEPERATOR,
                    $site_url
                );

                $val = $this->site_params[0];
            }

            $id    = $Child->getChildIdByName($val);
            $Child = $this->getProject()->get($id);

            if ($setpath) {
                $this->setIntoPath($Child);
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
        if ($this->project) {
            return $this->project;
        }

        if (is_string($this->project_str) && !empty($this->project_str)) {
            return QUI\Projects\Manager::get();
        }

        // ajax?
        if (defined('QUIQQER_AJAX') && QUIQQER_AJAX) {
            if (isset($_REQUEST['lang'])) {
                $this->lang = $_REQUEST['lang'];
            }
        }

        // Vhosts
        $Project = $this->getProjectByVhost();

        if ($Project) {
            if ($this->lang && $this->lang != $Project->getLang()) {
                $Project = QUI\Projects\Manager::getProject(
                    $Project->getName(),
                    $this->lang
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

        if (defined('HOST')) {
            $host = str_replace(array('http://', 'https://'), '', HOST);
        }

        if (isset($_SERVER['HTTP_HOST']) && $host != $_SERVER['HTTP_HOST'] && $this->project) {
            $this->showErrorHeader(404);

            return $this->project;
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

        try {
            $Project = QUI\Projects\Manager::get();

        } catch (QUI\Exception $Exception) {
            $Project = false;
        }

        if ($Project && is_object($Project)) {
            $this->project = $Project;

            return $this->project;
        }

        // Projekt mit der Sprache exitiert nicht
        $this->showErrorHeader(404);

        $Project = QUI\Projects\Manager::getStandard();

        $this->project = $Project;
        $this->lang    = $Project->getLang();

        QUI::getLocale()->setCurrent($Project->getLang());

        return $Project;
    }

    /**
     * Return the prject by the vhost, if a vhost exist
     *
     * @return \QUI\Projects\Project|false
     */
    protected function getProjectByVhost()
    {
        $vhosts = $this->getVHosts();

        // Vhosts
        $http_host = '';

        if (isset($_SERVER['HTTP_HOST'])) {
            $http_host = $_SERVER['HTTP_HOST'];
        }

        if (!isset($vhosts[$http_host])) {
            return false;
        }

        if (!isset($vhosts[$http_host]['project'])) {
            return false;
        }

        $pname = $vhosts[$http_host]['project'];

        //$lang = false;
        if (isset($vhosts[$http_host]['lang']) && !$this->lang) {
            $this->lang = $vhosts[$http_host]['lang'];
        }

        $template = false;

        if (isset($vhosts[$_SERVER['HTTP_HOST']]['template'])) {
            $template = $vhosts[$_SERVER['HTTP_HOST']]['template'];
        }

        try {
            $Project = \QUI::getProject(
                $pname,
                $this->lang,
                $template
            );

        } catch (QUI\Exception $Exception) {
            // nothing todo
            $Project = false;
        }

        if ($Project) {
            $this->project = $Project;

            QUI::getLocale()->setCurrent(
                $Project->getAttribute('lang')
            );

            return $Project;
        }

        return false;
    }

    /**
     * Gibt die Vhosts zurück
     *
     * @return array
     */
    public function getVHosts()
    {
        if (!empty($this->vhosts) || is_array($this->vhosts)) {
            return $this->vhosts;
        }

        $this->vhosts = QUI::vhosts();

        return $this->vhosts;
    }

    /**
     * Gibt das Suffix des Aufrufs zurück
     *
     * @return string .print / .html
     */
    public function getSuffix()
    {
        return $this->suffix;
    }

    /**
     * Error Header übermitteln
     *
     * @param integer $code - Error Code
     * @param string $url - Bei manchen Error Codes muss eine URL übergeben werden (30*)
     *
     * @return boolean
     */
    public function showErrorHeader($code = 404, $url = '')
    {
        // Im Admin gibt es keine Error Header
        if (defined('ADMIN')) {
            return false;
        }

        $Response = QUI::getGlobalResponse();

        $this->headerCode = $code;

        switch ($code) {
            // Client Request Redirected
            case 301:
                $Redirect = new RedirectResponse($url);
                $Redirect->setStatusCode(Response::HTTP_MOVED_PERMANENTLY);
                $Redirect->send();
                break;

            case 302:
                $Redirect = new RedirectResponse($url);
                $Redirect->setStatusCode(Response::HTTP_FOUND);
                $Redirect->send();
                break;

            case 303:
                $Redirect = new RedirectResponse($url);
                $Redirect->setStatusCode(Response::HTTP_SEE_OTHER);
                $Redirect->send();
                break;

            case 304:
                $Redirect = new RedirectResponse($url);
                $Redirect->setStatusCode(Response::HTTP_NOT_MODIFIED);
                $Redirect->send();
                break;

            case 305:
                $Redirect = new RedirectResponse($url);
                $Redirect->setStatusCode(Response::HTTP_USE_PROXY);
                $Redirect->send();
                break;

            // Client Request Errors
            case 404:
            default:
                $this->headerCode = 404;
                $Response->setStatusCode(Response::HTTP_NOT_FOUND);

                if (!defined('ERROR_HEADER')) {
                    define('ERROR_HEADER', 404);
                }

                if (!empty($url)) {
                    $Response->headers->set('Location', $url);
                }

                try {
                    $ErrorSite = $this->getErrorSite();

                    $this->project = $ErrorSite->getProject();
                    $this->site    = $ErrorSite;

                    return true;

                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);
                }

                break;

            case 503:
                $Response->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
                $Response->headers->set('X-Powered-By', '');
                $Response->headers->set('Retry-After', 3600);
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
        if (isset($vhosts[$_SERVER['HTTP_HOST']])
            && isset($vhosts[$_SERVER['HTTP_HOST']]['error'])
        ) {
            $host = $_SERVER['HTTP_HOST'];

            $error = $vhosts[$host]['error'];
            $error = explode(',', $error);

            try {
                if (!isset($error[0]) || !isset($error[1])
                    || !isset($error[2])
                ) {
                    $Standard = QUI::getProjectManager()->getStandard();

                    $error[0] = $Standard->getName();
                    $error[1] = $Standard->getLang();
                    $error[2] = 1;
                }

                $template = false;

                if (isset($vhosts[$host]['template'])) {
                    $template = $vhosts[$host]['template'];
                }

                $Project = QUI::getProject($error[0], $error[1], $template);
                $Site    = $Project->get((int)$error[2]);

                return $Site;

            } catch (QUI\Exception $Exception) {
                // no error site found, dry it global
                echo $Exception->getMessage();
            }
        }

        if (isset($vhosts[404]) && isset($vhosts[404]['id'])
            && isset($vhosts[404]['project'])
            && isset($vhosts[404]['lang'])
        ) {
            try {
                $Project = \QUI::getProject(
                    $vhosts[404]['project'],
                    $vhosts[404]['lang']
                );

                $Site = $Project->get($vhosts[404]['id']);

                return $Site;

            } catch (QUI\Exception $Exception) {
            }
        }

        $Standard = QUI::getProjectManager()->getStandard();

        if (!$Standard) {
            throw new QUI\Exception('Error Site not exist', 404);
        }

        return $Standard->firstChild();
    }

    /**
     * Gibt die aktuelle Seite zurück
     *
     * @return \QUI\Projects\Site
     */
    public function getSite()
    {
        if (isset($this->site) && is_object($this->site)) {
            return $this->site;
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
        $this->site = $Site;
    }

    /**
     * Den aktuelle Pfad bekommen
     *
     * @param boolean $start - where to start
     * @param boolean $me - Pfad mit der aktuellen Seite ausgeben
     *
     * @return array
     */
    public function getPath($start = true, $me = true)
    {
        $path = $this->path;

        if (!isset($path[0])) {
            return array();
        }

        if ($start == true) {
            if (isset($path) && is_array($path)
                && (!isset($path[0])
                    || $path[0]->getId() != 1)
            ) {
                array_unshift($path, $this->first_child);
            }
        }

        if ($me == false) {
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
        $this->path = $path;
    }

    /**
     * Prüft ob die Seite im Pfad ist
     *
     * @param Int $id - ID der Seite welche geprüft werden soll
     *
     * @return boolean
     */
    public function isIdInPath($id)
    {
        return in_array($id, $this->ids_in_path) ? true : false;
    }

    /**
     * @param QUI\Projects\Site $Site
     */
    public function addSiteToPath($Site)
    {
        $this->path[] = $Site;
        array_push($this->ids_in_path, $Site->getId());
    }

    /**
     * Setzt eine Seite in den Path
     *
     * @param \QUI\Projects\Site $Site - seite die hinzugefügt wird
     */
    private function setIntoPath(QUI\Projects\Site $Site)
    {
        $this->path[] = $Site;
        array_push($this->ids_in_path, $Site->getId());
    }

    /**
     * Outputfilter
     * Geht HTML durch und ruft die dazugehörigen Funktionen auf um URLs umzuwandeln
     *
     * @param string $output - html, text
     *
     * @return string
     */
    public function outputFilter($output)
    {
        // Bilder umschreiben
        $output = preg_replace_callback(
            '#<img([^>]*)>#i',
            array(&$this, "outputImages"),
            $output
        );

        // restliche Dateien umschreiben
        $output = preg_replace_callback(
            '#(href|src|value)="(image.php)\?([^"]*)"#',
            array(&$this, "outputFiles"),
            $output
        );

        //Links umschreiben
        $output = preg_replace_callback(
            '#(href|src|action|value|data)="(index.php)\?([^"]*)"#',
            array(&$this, "outputLinks"),
            $output
        );

        // SPAM Protection
//        if (MAIL_PROTECT) {
//            $output = str_replace('</body>',
//                '<!-- [begin] QUIQQER Mail SPAM Bot Protection --><iframe src="'
//                .URL_BIN_DIR
//                .'mail_protection.php" style="position: absolute; display: none; width: 1px; height: 1px;"
//                   name="mail_protection" title="mail_protection"></iframe>
//                  <!-- [begin] P.MS Mail SPAM Bot Protection --></body>',
//                $output);
//
//            $output = preg_replace_callback(
//                '#(href)="(mailto:)([^"]*)"#',
//                array(&$this, "outputMail"),
//                $output
//            );
//        }

        $this->setOutputContent($output);

        // fire Rewrite::onOutput
        \QUI::getEvents()->fireEvent('QUI::rewriteOutput', array(
            'Rewrite' => $this
        ));

        return $this->getOutputContent();
    }

    /**
     * Output Content setzen
     *
     * @param string $str
     */
    public function setOutputContent($str)
    {
        $this->output_content = $str;
    }

    /**
     * Output Content bekommen
     *
     * @return string
     */
    public function getOutputContent()
    {
        return $this->output_content;
    }

    /**
     * Mail Protection gegen SPAM
     * Wandelt die Mail Addressen so um das ein BOT nichts mit anfangen kann
     *
     * @param string $output
     *
     * @return string
     */
    public function outputMail($output)
    {
        if (isset($output[3]) && strpos($output[3], '@') !== false) {
            list($user, $domain) = explode("@", $output[3]);

            return 'href="' . URL_DIR . '[mailto]' . $user . '[at]' . $domain
                   . '" target="mail_protection"';
        }

        return $output[0];
    }

    /**
     * Wandelt den Bildepfad in einen sprechenden Pfad um
     *
     * @param string $output
     *
     * @return string
     */
    public function outputFiles($output)
    {
        try {
            $url = MediaUtils::getRewritedUrl('image.php?' . $output[3]);

        } catch (QUI\Exception $Excxeption) {
            $url = '';
        }


        return $output[1] . '="' . $url . '"';
    }

    /**
     * Wandelt den Bilderpfad in einen sprechenden Pfad um
     *
     * @param string $output
     *
     * @return string
     */
    public function outputImages($output)
    {
        $img = $output[0];

        // Falls in der eigenen Sammlung schon vorhanden
        if (isset($this->image_cache[$img])) {
            return $this->image_cache[$img];
        }

        if (!MediaUtils::isMediaUrl($img)) {
            return $output[0];
        }

        $att = QUI\Utils\StringHelper::getHTMLAttributes($img);

        if (!isset($att['src'])) {
            return $output[0];
        }

        $src = str_replace('&amp;', '&', $att['src']);

        unset($att['src']);

        if (!isset($att['alt']) || !isset($att['title'])) {
            try {
                $Image = MediaUtils::getImageByUrl($src);

                $att['alt'] = $Image->getAttribute('alt')
                    ? $Image->getAttribute('alt') : '';

                $att['title'] = $Image->getAttribute('title')
                    ? $Image->getAttribute('title') : '';

                $att['data-src'] = $Image->getSizeCacheUrl();

            } catch (QUI\Exception $Exception) {
            }
        }

        $this->image_cache[$img] = MediaUtils::getImageHTML($src, $att);

        return $this->image_cache[$img];
    }

    /**
     * Wandelt eine PCSG URL in eine sprechende URL um
     *
     * @param string $output
     *
     * @return string
     */
    public function outputLinks($output)
    {
        // no php url
        if ($output[2] !== 'index.php') {
            return $output[0];
        }

        $output = str_replace('&amp;', '&', $output);   // &amp; fix
        $output = str_replace('〈=', '&lang=', $output); // URL FIX

        $components = $output[3];

        // Falls in der eigenen Sammlung schon vorhanden
        if (isset($this->url_cache[$components])) {
            return $output[1] . '="' . $this->url_cache[$components] . '"';
        }

        $parseUrl = parse_url($output[2] . '?' . $components);

        if (!isset($parseUrl['query']) || empty($parseUrl['query'])) {
            return $output[0];
        }

        $urlQuery = $parseUrl['query'];

        if (strpos($urlQuery, 'project') === false
            || strpos($urlQuery, 'lang') === false
            || strpos($urlQuery, 'id') === false
        ) {
            // no quiqqer url
            return $output[0];
        }

        // maybe a quiqqer url ?
        parse_str($urlQuery, $urlQueryParams);

        try {
            $url    = $this->getUrlFromSite($urlQueryParams);
            $anchor = '';

            if (isset($parseUrl['fragment']) && !empty($parseUrl['fragment'])) {
                $anchor = '#' . $parseUrl['fragment'];
            }

            $this->url_cache[$components] = $url . $anchor;

            return $output[1] . '="' . $url . $anchor . '"';

        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return $output[0];
    }

    /**
     * Sonderzeichen aus dem Namen entfernen damit die URL rein aussieht
     *
     * @param string $url
     * @param boolean $slash - Soll Slash ersetzt werden oder nicht
     *
     * @return string
     */
    public static function replaceUrlSigns($url, $slash = false)
    {
        $search = array('%20', '.', ' ', '_');

        if ($slash) {
            $search[] = '/';
        }

        $url = str_replace($search, '-', $url);

        if (substr($url, -5) == '_html') {
            $url = substr($url, 0, -5) . self::getDefaultSuffix();
        }

        return $url;
    }

    /**
     * Return the url params as index array
     *
     * @return array
     */
    public function getUrlParamsList()
    {
        if (!isset($_REQUEST['_url'])) {
            return array();
        }

        $url = $_REQUEST['_url'];
        $url = explode('.', $url);
        $url = explode('_', $url[0]);

        array_shift($url);

        return $url;
    }

    /**
     * Register a rewrite path
     *
     * @param string|array $paths
     * @param \QUI\Projects\Site $Site
     */
    public function registerPath($paths, $Site)
    {
        $Project = $Site->getProject();
        $table   = QUI::getDBProjectTableName('paths', $Project);

        $this->unregisterPath($Site);

        if (!is_array($paths)) {
            $paths = array($paths);
        }

        // cleanup paths - use only paths
        foreach ($paths as $key => $path) {
            $paths[$key] = parse_url($path, \PHP_URL_PATH);
        }

        foreach ($paths as $path) {
            QUI::getDataBase()->insert($table, array(
                'id' => $Site->getId(),
                'path' => $path
            ));
        }
    }

    /**
     * Unregister a rewrite path
     *
     * @param \QUI\Projects\Site $Site
     */
    public function unregisterPath($Site)
    {
        $Project = $Site->getProject();
        $table   = QUI::getDBProjectTableName('paths', $Project);

        QUI::getDataBase()->delete($table, array(
            'id' => $Site->getId()
        ));
    }

    /**
     * Return the Site or false if a path exists
     *
     * @param string $path
     * @param \QUI\Projects\Project $Project
     *
     * @return \QUI\Projects\Site
     */
    public function existRegisterPath($path, $Project)
    {
        $table = QUI::getDBProjectTableName('paths', $Project);
        $list  = QUI::getDataBase()->fetch(array(
            'from' => $table
        ));

        // nach / (slash) sortieren, damit urls mit mehr kindseiten als erstes kommen
        // ansonsten kann es vorkommen das die falsche seite für den Pfad zuständig ist
        usort($list, function ($a, $b) {
            return substr_count($a['path'], '/') < substr_count($b['path'], '/');
        });

        foreach ($list as $entry) {
            if (!QUI\Utils\StringHelper::match($entry['path'], $path)) {
                continue;
            }

            try {
                $Site = $Project->get((int)$entry['id']);

                if ($Site->getAttribute('active')) {
                    return $Site;
                }

            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addDebug($Exception->getMessage());
            }
        }

        return false;
    }

    /**
     * Gibt die sprechende URL einer Seite zurück
     *
     * @param array $params
     *    $params['site'] => (object) Site
     *
     * @param array $getParams
     *
     * oder
     *    $params['id'] => (int) Id - Id der Seite
     *    $params['lang'] => (string) lang - Sprache der Seite
     *    $params['project'] => (string) project - Projektnamen
     *
     * @return string
     * @throws QUI\Exception
     */
    public function getUrlFromSite($params = array(), $getParams = array())
    {
        // Falls ein Objekt übergeben wird
        if (isset($params['site']) && is_object($params['site'])) {
            /* @var $Project QUI\Projects\Project */
            /* @var $Site QUI\Projects\Site */
            $Site    = $params['site'];
            $Project = $Site->getProject();
            $id      = $Site->getId();

            $lang    = $Project->getLang();
            $project = $Project->getName();

            unset($params['site']);

        } else {
            if (isset($params['id'])) {
                $id = $params['id'];
            }

            if (isset($params['project'])) {
                $project = $params['project'];
            }

            if (isset($params['lang'])) {
                $lang = $params['lang'];
            }

            unset($params['project']);
            unset($params['id']);
            unset($params['lang']);
        }

        // Wenn nicht alles da ist dann wird ein Exception geworfen
        if (!isset($id) || !isset($project)) {
            throw new QUI\Exception(
                'Params missing Rewrite::getUrlFromPage'
            );
        }

        if (!isset($lang)) {
            $lang = '';
        }

        QUI\Utils\System\File::mkdir(VAR_DIR . 'cache/links');

        $link_cache_dir = VAR_DIR . 'cache/links/' . $project . '/';
        QUI\Utils\System\File::mkdir($link_cache_dir);

        $link_cache_file = $link_cache_dir . $id . '_' . $project . '_' . $lang;

        // get params
        if (!empty($getParams)) {
            $params['_getParams'] = $getParams;
        }

        // Falls es das Cachefile schon gibt
        if (file_exists($link_cache_file)) {
            $url = file_get_contents($link_cache_file);
            $url = $this->extendUrlWidthPrams($url, $params);

        } else {
            // Wenn nicht erstellen
            try {
                $Project = QUI::getProject($project, $lang);
                /* @var $Project \QUI\Projects\Project */
                $Site = $Project->get((int)$id);
                /* @var $s \QUI\Projects\Site */

            } catch (QUI\Exception $Exception) {
                // Seite existiert nicht
                return '';
            }

            $_params = array(); // Temp Params, nur um die Endung mitzuliefern

            if (isset($params['suffix'])) {
                $_params['suffix'] = $params['suffix'];
            }

            // Link Cache
            file_put_contents(
                $link_cache_file,
                str_replace(
                    '.print',
                    self::getDefaultSuffix(),
                    $Site->getLocation($_params)
                )
            );

            $url = $Site->getLocation($_params);
            $url = $this->extendUrlWidthPrams($url, $params);
        }

        $vhosts = $this->getVHosts();

        if (!isset($Project)) {
            $Project = $this->getProject();
        }

        /**
         * Sprache behandeln
         */

        if (isset($_SERVER['HTTP_HOST'])
            && isset($vhosts[$_SERVER['HTTP_HOST']])
            && isset($vhosts[$_SERVER['HTTP_HOST']][$lang])
            && !empty($vhosts[$_SERVER['HTTP_HOST']][$lang])
        ) {
            if (// wenn ein Host eingetragen ist
                $lang != $Project->getAttribute('lang')
                || // falls der jetzige host ein anderer ist als der vom link,
                // dann den host an den link setzen
                $vhosts[$_SERVER['HTTP_HOST']][$lang] != $_SERVER['HTTP_HOST']
            ) {
                // und die Sprache nicht die vom jetzigen Projekt ist
                // dann Host davor setzen
                $url = $vhosts[$_SERVER['HTTP_HOST']][$lang] . URL_DIR . $url;
                $url = QUI\Utils\StringHelper::replaceDblSlashes($url);
                $url = 'http://' . $this->project_prefix . $url;

                return $url;
            }

            $url = $this->project_prefix . $url;
            $url = QUI\Utils\StringHelper::replaceDblSlashes($url);

        } elseif ($Project->getAttribute('default_lang') !== $lang) {
            // Falls kein Host Eintrag gibt
            // Und nicht die Standardsprache dann das Sprachenflag davor setzen
            $url = $this->project_prefix . $lang . '/' . $url;
            $url = QUI\Utils\StringHelper::replaceDblSlashes($url);
        }

        $url = URL_DIR . $url;


        // falls host anders ist, dann muss dieser dran gehängt werden
        // damit kein doppelter content entsteht
        if ($_SERVER['HTTP_HOST'] != $Project->getHost() && $Project->getHost() == '') {
            $url = $Project->getHost() . $url;

            if (strpos($url, 'http://') === false) {
                $url = 'http://' . $url;
            }
        }

        return $url;
    }

    /**
     * Erweitert die URL um Params
     *
     * @param string $url
     * @param array $params
     *
     * @return string
     */
    private function extendUrlWidthPrams($url, $params)
    {
        if (!count($params)) {
            return $url;
        }

        $seperator = self::URL_PARAM_SEPERATOR;
        $getParams = array();

        if (isset($params['_getParams']) && is_string($params['_getParams'])) {
            parse_str($params['_getParams'], $getParams);
            unset($params['_getParams']);

        } elseif (isset($params['_getParams']) && is_array($params['_getParams'])) {
            $getParams = $params['_getParams'];
            unset($params['_getParams']);
        }

        if (isset($params['paramAsSites']) && $params['paramAsSites']) {
            $seperator = '/';
            unset($params['paramAsSites']);
        }


        $suffix = '';
        $exp    = explode('.', $url);
        $url    = $exp[0];

        foreach ($params as $param => $value) {
            if (is_integer($param)) {
                $url .= $seperator . $value;
                continue;
            }

            if ($param == 'suffix') {
                continue;
            }

            if ($param === "0") {
                $url .= $seperator . $value;
                continue;
            }

            $url .= $seperator . $param . $seperator . $value;
        }

        if (isset($params['suffix'])) {
            $suffix = '.' . $params['suffix'];
        }

        if (empty($suffix) && isset($exp[1])) {
            $suffix = '.' . $exp[1];
        }

        if (empty($suffix)) {
            $suffix = self::getDefaultSuffix();
        }

        if (empty($getParams)) {
            return $url . $suffix;
        }

        return $url . $suffix . '?' . http_build_query($getParams);
    }
}
