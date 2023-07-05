<?php

/**
 * This file contains \QUI\Rewrite
 */

namespace QUI;

use QUI;
use QUI\Projects\Media\File;
use QUI\Projects\Media\Image;
use QUI\Projects\Media\Utils as MediaUtils;
use QUI\Projects\Project;
use QUI\Projects\Site;
use QUI\Projects\Site\Edit;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use function array_flip;
use function array_map;
use function array_shift;
use function array_unshift;
use function count;
use function define;
use function defined;
use function explode;
use function fclose;
use function file_exists;
use function filesize;
use function fopen;
use function fread;
use function gmdate;
use function header;
use function http_response_code;
use function implode;
use function in_array;
use function is_array;
use function is_object;
use function is_string;
use function ltrim;
use function mb_strlen;
use function mb_substr;
use function mb_substr_count;
use function parse_url;
use function pathinfo;
use function str_replace;
use function strlen;
use function strpos;
use function strrpos;
use function strtok;
use function substr;
use function trim;
use function urldecode;
use function usort;

use const PHP_URL_PATH;
use const URL_DIR;

/**
 * Rewrite - URL Verwaltung (sprechende URLS)
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 *
 * @todo  must be rewritten, spaghetti code :(
 * @todo  create new concept
 * @todo  translate comments
 *
 * @event onQUI::Request
 * @event onQUI::Access
 * @event onQUI::RewriteOutput [ Rewrite ]
 */
class Rewrite
{
    const URL_PARAM_SEPARATOR = '_';
    const URL_SPACE_CHARACTER = '-';
    const URL_PROJECT_CHARACTER = '^';
    const URL_DEFAULT_SUFFIX = '.html';

    public static $SUFFIX = false;

    /**
     * site request parameter
     *
     * @var array
     */
    public array $site_params = [];
    /**
     * @var null
     */
    protected $registerPaths = null;
    /**
     * @var Output
     */
    protected Output $Output;
    /**
     * @var Events\Event
     */
    protected QUI\Events\Event $Events;
    /**
     * active project
     *
     * @var Project
     */
    private $project;
    /**
     * active project
     *
     * @var string
     */
    private string $project_str = '';
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
    private string $project_prefix = '';
    /**
     * project lang
     *
     * @var string
     */
    private $lang = false;
    /**
     * active site
     *
     * @var Site
     */
    private $site = null;
    /**
     * first site of the project
     *
     * @var Site
     */
    private $first_child;
    /**
     * current site path
     *
     * @var array
     */
    private array $path = [];
    /**
     * current site path - but only the ids
     *
     * @var array
     */
    private array $ids_in_path = [];
    /**
     * internal url cache
     *
     * @var array
     */
    private array $url_cache = [];
    /**
     * internal image link cache
     *
     * @var array
     */
    private array $image_cache = [];
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
    private string $suffix = '.html';
    /**
     * the html output
     *
     * @var string
     */
    private string $output_content = '';
    /**
     * Standard header code
     *
     * @var int
     */
    private int $headerCode = 200;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->Events = new QUI\Events\Event();
        $this->Output = new Output();
    }

    /**
     * Sonderzeichen aus dem Namen entfernen damit die URL rein aussieht
     *
     * @param string $url
     * @param boolean $slash - Soll Slash ersetzt werden oder nicht
     *
     * @return string
     */
    public static function replaceUrlSigns(string $url, bool $slash = false): string
    {
        $search = ['%20', '.', ' ', '_'];

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
     * Request verarbeiten
     *
     * @throws QUI\Exception
     */
    public function exec()
    {
        if (!isset($_REQUEST['_url'])) {
            $_REQUEST['_url'] = '';
        }

        // nginx fix
        $_REQUEST['_url'] = ltrim($_REQUEST['_url'], '/');

        //wenn seite existiert, dann muss nichts mehr gemacht werden
        if (isset($this->site) && $this->site) {
            QUI::getEvents()->fireEvent('request', [$this, $_REQUEST['_url']]);

            return;
        }

        $vhosts = $this->getVHosts();
        $defaultSuffix = self::getDefaultSuffix();

        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = '';
        }

        // globale forwarding - 301, etc
        QUI\System\Forwarding::forward(QUI::getRequest());

        // on character urls are not allowed
        if (
            !empty($_REQUEST['_url'])
            && (mb_strlen($_REQUEST['_url']) === 1 || mb_substr($_REQUEST['_url'], 0, 1) === '.')
        ) {
            $this->showErrorHeader(404, URL_DIR);
        }

        // wenn sprach ohne /
        // dann / dran
        // sprach ist ein ordner keine seite
        if (!empty($_REQUEST['_url']) && mb_strlen($_REQUEST['_url']) == 2) {
            QUI::getEvents()->fireEvent('request', [$this, $_REQUEST['_url'] . '/']);

            // 301 weiterleiten
            $this->showErrorHeader(301, URL_DIR . $_REQUEST['_url'] . '/');
        }


        // Kategorien aufruf
        // Aus url/kat/ wird url/kat.html
        if (
            !empty($_REQUEST['_url'])
            && substr($_REQUEST['_url'], -1) == '/'
            && strlen($_REQUEST['_url']) != 3
            && strpos($_REQUEST['_url'], 'media/cache') === false
        ) {
            $_REQUEST['_url'] = substr($_REQUEST['_url'], 0, -1) . $defaultSuffix;

            QUI::getEvents()->fireEvent('request', [$this, $_REQUEST['_url']]);

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
            if (
                isset($_url[0])
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

                    $this->project_str = $_project_split[0];
                    $this->template_str = $_project_split[1];
                }

                $this->project_prefix = self::URL_PROJECT_CHARACTER . $this->project_str . '/';

                if ($this->template_str) {
                    $this->project_prefix = self::URL_PROJECT_CHARACTER . $this->project_str;
                    $this->project_prefix .= self::URL_PROJECT_CHARACTER . $this->template_str . '/';
                }


                unset($_url[0]);

                $_new_url = [];

                foreach ($_url as $elm) {
                    $_new_url[] = $elm;
                }

                $_url = $_new_url;
            }

            // Sprache
            $language = false;

            if (
                isset($_url[0])
                && (strlen($_url[0]) == 2 || strlen(str_replace($defaultSuffix, '', $_url[0])) == 2)
            ) {
                $availableLanguages = QUI::availableLanguages();
                $language = str_replace($defaultSuffix, '', $_url[0]);

                if (!in_array($language, $availableLanguages)) {
                    $language = false;
                }
            }

            if ($language) {
                $this->lang = $language;
                QUI::getLocale()->setCurrent($this->lang);

                unset($_url[0]);

                $_new_url = [];

                foreach ($_url as $elm) {
                    $_new_url[] = $elm;
                }

                $_url = $_new_url;

                // Wenns ein Hosteintrag mit der Sprache gibt, dahin leiten
                // und es nicht der https host ist
                // @todo https host nicht über den port prüfen, zu ungenau
                if (
                    isset($_SERVER['HTTP_HOST'])
                    && isset($vhosts[$_SERVER['HTTP_HOST']])
                    && isset($vhosts[$_SERVER['HTTP_HOST']][$this->lang])
                    && !empty($vhosts[$_SERVER['HTTP_HOST']][$this->lang])
                    && (int)$_SERVER['SERVER_PORT'] !== 443
                    && QUI::conf('globals', 'httpshost') != 'https://' . $_SERVER['HTTP_HOST']
                ) {
                    $url = implode('/', $_url);
                    $url = $vhosts[$_SERVER['HTTP_HOST']][$this->lang] . URL_DIR . $url;
                    $url = QUI\Utils\StringHelper::replaceDblSlashes($url);
                    $url = 'http://' . $this->project_prefix . $url;

                    QUI::getEvents()->fireEvent('request', [$this, $_REQUEST['_url']]);

                    if ($url !== $this->getRequestUri()) {
                        $this->showErrorHeader(301, $url);
                    }
                }
            }

            $_REQUEST['_url'] = implode('/', $_url);

            if (!count($_url)) {
                unset($_REQUEST['_url']);
            }
        }


        // Media Center Datei, falls nicht im Cache ist
        if (
            isset($_REQUEST['_url'])
            && strpos($_REQUEST['_url'], 'media/cache') !== false
        ) {
            QUI::getEvents()->fireEvent('request', [$this, $_REQUEST['_url']]);

            $imageNotError = false;
            $Item = false;

            try {
                $Item = MediaUtils::getElement($_REQUEST['_url']);

                if (strpos($_REQUEST['_url'], '__') !== false) {
                    $lastpos_ul = strrpos($_REQUEST['_url'], '__') + 2;
                    $pos_dot = strpos($_REQUEST['_url'], '.', $lastpos_ul);

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
                QUI::getEvents()->fireEvent('onRequestImageNotFound', [$_REQUEST['_url']]);

                $Redirect = new RedirectResponse(
                    $this->getErrorSite()->getUrlRewritten()
                );

                $Redirect->setStatusCode(Response::HTTP_NOT_FOUND);
                $Redirect->send();
                exit;
            }

            if ($Item->getType() === 'QUI\\Projects\\Media\\Image') {
                /* @var $Item Image */
                if (empty($width)) {
                    $width = false;
                }

                if (empty($height)) {
                    $height = false;
                }

                try {
                    $file = $Item->createSizeCache($width, $height);
                } catch (QUI\Permissions\Exception $Exception) {
                    http_response_code(Response::HTTP_FORBIDDEN);

                    $file = OPT_DIR . 'quiqqer/quiqqer/bin/images/deny.svg';
                    $Item->setAttribute('mime_type', 'image/svg+xml');
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);
                }
            } else {
                try {
                    /* @var $Item File */
                    $file = $Item->createCache();
                } catch (QUI\Permissions\Exception $Exception) {
                    http_response_code(Response::HTTP_FORBIDDEN);

                    $file = OPT_DIR . 'quiqqer/quiqqer/bin/images/deny.svg';
                    $Item->setAttribute('mime_type', 'image/svg+xml');
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);
                }
            }

            // @todo consider permissions denied -> show permission denied image
            if (!isset($file) || !file_exists($file)) {
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

        QUI::getEvents()->fireEvent('request', [$this, $_REQUEST['_url']]);

        // Falls kein suffix dann 301 weiterleiten auf .html
        if (!empty($_REQUEST['_url']) && substr($_REQUEST['_url'], -1) != '/') {
            $pathinfo = pathinfo($_REQUEST['_url']);

            if (!isset($pathinfo['extension']) && $defaultSuffix !== '') {
                $url = URL_DIR . $_REQUEST['_url'] . $defaultSuffix;
                $url = QUI\Utils\StringHelper::replaceDblSlashes($url);

                // Falls keine Extension (.html) dann auf .html
                // nur wenn $defaultSuffix == ''
                $this->showErrorHeader(301, $url);
            } elseif (
                $defaultSuffix === ''
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

        if ($this->site && $this->site->getId() !== $this->first_child->getId()) {
            $this->setIntoPath($this->first_child);
            $this->setIntoPath($this->site);

            return;
        }

        $this->site = $this->first_child;

        if (!empty($_REQUEST['_url'])) {
            // URL Parameter filtern
            try {
                $this->site = $this->getSiteByUrl($_REQUEST['_url']);
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
            if (
                isset($_SERVER['HTTP_HOST'])
                && isset($vhosts[$_SERVER['HTTP_HOST']])
                && isset($vhosts[$_SERVER['HTTP_HOST']][$this->lang])
                && !empty($vhosts[$_SERVER['HTTP_HOST']][$this->lang])
                && $_SERVER['HTTP_HOST'] != $vhosts[$_SERVER['HTTP_HOST']][$this->lang]
                && (int)$_SERVER['SERVER_PORT'] !== 443
                && QUI::conf('globals', 'httpshost') != 'https://' . $_SERVER['HTTP_HOST']
            ) {
                $url = $this->site->getUrlRewritten();

                if (strpos($url, 'http:') === false) {
                    $url = $vhosts[$_SERVER['HTTP_HOST']][$this->lang] . URL_DIR . $url;
                    $url = QUI\Utils\StringHelper::replaceDblSlashes($url);
                    $url = 'http://' . $this->project_prefix . $url;
                }

                if ($url !== $this->getRequestUri()) {
                    $this->showErrorHeader(301, $url);
                }
            }

            // REQUEST setzen
            $site_params = $this->site_params;

            if (isset($site_params[1])) {
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
        }

        // Prüfen ob die aufgerufene URL gleich der von der Seite ist
        // Wenn nicht 301 auf die richtige
        $url = $this->Output->getSiteUrl([
            'site' => $this->site
        ]);

        $request_url = $_SERVER['REQUEST_URI'] ?? '';

        $pos = strpos($request_url, self::URL_PARAM_SEPARATOR);
        $end = strpos($request_url, '.');


        if ($pos !== false) {
            $request_url = substr($request_url, 0, $pos) . substr($request_url, $end);

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
     * Gibt die Vhosts zurück
     *
     * @return array
     */
    public function getVHosts(): array
    {
        if (!empty($this->vhosts) || is_array($this->vhosts)) {
            return $this->vhosts;
        }

        $this->vhosts = QUI::vhosts();

        return $this->vhosts;
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
     * Error Header übermitteln
     *
     * @param integer $code - Error Code
     * @param string $url - Bei manchen Error Codes muss eine URL übergeben werden (30*)
     *
     * @return boolean
     */
    public function showErrorHeader(int $code = 404, string $url = ''): bool
    {
        // Im Admin gibt es keine Error Header
        if (defined('ADMIN')) {
            return false;
        }

        try {
            QUI::getEvents()->fireEvent('errorHeaderShowBefore', [$code, $url]);
        } catch (\Exception $e) {
            QUI\System\Log::writeException($e);
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
                    $this->site = $ErrorSite;
                    $this->site->setAttribute('ERROR_HEADER', 404);

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

        try {
            QUI::getEvents()->fireEvent('errorHeaderShowAfter', [$code, $url]);
        } catch (\Exception $e) {
            QUI\System\Log::writeException($e);
        }

        return true;
    }

    /**
     * Shows the 404 site
     *
     * @return Site
     * @throws QUI\Exception
     */
    public function getErrorSite()
    {
        $vhosts = $this->getVHosts();

        // Falls der Host eine eigene Fehlerseite zugewiesen bekommen hat
        if (
            isset($vhosts[$_SERVER['HTTP_HOST']])
            && isset($vhosts[$_SERVER['HTTP_HOST']]['error'])
        ) {
            $host = $_SERVER['HTTP_HOST'];

            $error = $vhosts[$host]['error'];
            $error = explode(',', $error);

            try {
                if (!isset($error[0]) || !isset($error[1]) || !isset($error[2])) {
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
                $Site = $Project->get((int)$error[2]);

                return $Site;
            } catch (QUI\Exception $Exception) {
                // no error site found, dry it global
                echo $Exception->getMessage();
            }
        }

        if (
            isset($vhosts[404]) && isset($vhosts[404]['id'])
            && isset($vhosts[404]['project'])
            && isset($vhosts[404]['lang'])
        ) {
            try {
                $Project = QUI::getProject(
                    $vhosts[404]['project'],
                    $vhosts[404]['lang']
                );

                return $Project->get($vhosts[404]['id']);
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
     * Gibt das aktuelle Projekt zurück
     * Die Daten werden aus der URL gehohlt
     *
     * @return Project
     *
     * @throws QUI\Exception
     */
    public function getProject(): Project
    {
        if ($this->project) {
            return $this->project;
        }

        if (!empty($this->project_str)) {
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

        // wenn standard vhost nicht der gewünschte ist, dann 404
        $host = '';

        if (defined('HOST')) {
            $host = str_replace(['http://', 'https://'], '', HOST);
        }

        if (isset($_SERVER['HTTP_HOST']) && $host != $_SERVER['HTTP_HOST'] && $this->project) {
            $this->showErrorHeader(404);

            return $this->project;
        }

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
        $this->lang = $Project->getLang();

        QUI::getLocale()->setCurrent($Project->getLang());

        return $Project;
    }

    /**
     * Return the project by the vhost, if a vhost exist
     *
     * @return Project|false
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
            $Project = QUI::getProject(
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
            $this->Output->setProject($Project);

            if (!defined('QUIQQER_AJAX')) {
                QUI::getLocale()->setCurrent(
                    $Project->getAttribute('lang')
                );
            }

            return $Project;
        }

        return false;
    }

    /**
     * Return the current request uri without params
     *
     * @return string
     */
    public function getRequestUri(): string
    {
        return strtok(QUI::getRequest()->getUri(), '?');
    }

    /**
     * Setzt eine Seite in den Path
     *
     * @param Interfaces\Projects\Site $Site - seite die hinzugefügt wird
     */
    private function setIntoPath(Interfaces\Projects\Site $Site)
    {
        $this->path[] = $Site;
        $this->ids_in_path[] = $Site->getId();
    }

    /**
     * Enter description here...
     *
     * @param string $url
     * @param boolean $setPath
     *
     * @return Site|false
     *
     * @throws QUI\Exception
     */
    public function getSiteByUrl(string $url, bool $setPath = true)
    {
        // Sprache raus
        if ($url == '') {
            return $this->first_child;
        }

        try {
            $_url = explode('/', trim($url, '/'));

            if (count($_url) <= 1) {
                // Erste Ebene
                $site_url = explode('.', $_url[0]);

                $this->site_params = explode(
                    self::URL_PARAM_SEPARATOR,
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

                if ($setPath) {
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
                    $suffixLen = mb_strlen($defaultSuffix);

                    if (
                        $defaultSuffix != ''
                        && mb_substr($val, $suffixLen * -1) == $defaultSuffix
                    ) {
                        $site_url = mb_substr($val, 0, $suffixLen * -1);
                    } else {
                        $site_url = $val;
                    }

                    $this->site_params = explode(
                        self::URL_PARAM_SEPARATOR,
                        $site_url
                    );

                    $val = $this->site_params[0];
                }

                $id = $Child->getChildIdByName($val);
                $Child = $this->getProject()->get($id);

                if ($setPath) {
                    $this->setIntoPath($Child);
                }
            }
        } catch (\Exception $Exception) {
            $Child = QUI\Utils\Site::getSiteByUrl($url);
        }

        return $Child;
    }

    /**
     * Return the Site or false if a path exists
     *
     * @param string $path
     * @param Project $Project
     *
     * @return Site|false
     */
    public function existRegisterPath(string $path, Project $Project)
    {
        if ($this->registerPaths === null) {
            $table = QUI::getDBProjectTableName('paths', $Project);
            $result = QUI::getDataBase()->fetch([
                'from' => $table
            ]);

            $this->registerPaths = $result;
        }

        $list = $this->registerPaths;

        // Nach / (slash) sortieren, damit URL mit mehr Kindseiten als erstes kommen
        // Ansonsten kann es vorkommen das die falsche Seite für den Pfad zuständig ist
        usort($list, function ($a, $b) {
            return mb_substr_count($a['path'], '/') - mb_substr_count($b['path'], '/');
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
     * Parameter der Rewrite
     *
     * @param string $name
     *
     * @return string|boolean
     */
    public function getParam(string $name)
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
    public function getHeaderCode(): int
    {
        return $this->headerCode;
    }

    /**
     * Enter description here...
     *
     * @return string
     */
    public function getProjectPrefix(): string
    {
        return $this->project_prefix;
    }

    /**
     * @return Output
     *
     * @throws QUI\Exception
     */
    public function getOutput(): Output
    {
        $this->Output->setProject($this->getProject());

        return $this->Output;
    }

    /**
     * Gibt das Suffix des Aufrufs zurück
     *
     * @return string .print / .html
     */
    public function getSuffix(): string
    {
        return $this->suffix;
    }

    /**
     * Gibt die aktuelle Seite zurück
     *
     * @return Site|null
     *
     * @throws QUI\Exception
     */
    public function getSite(): ?Site
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
     * @param Site|Edit|QUI\Projects\Site\Virtual $Site
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
            return [];
        }

        if ($start == true) {
            if (
                isset($path) && is_array($path)
                && (!isset($path[0]) || $path[0]->getId() != 1)
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
    public function setPath(array $path)
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
    public function isIdInPath(int $id): bool
    {
        return in_array($id, $this->ids_in_path);
    }

    /**
     * @param Site|QUI\Projects\Site\Virtual $Site
     */
    public function addSiteToPath($Site)
    {
        $this->path[] = $Site;
        $this->ids_in_path[] = $Site->getId();
    }

    /**
     * Outputfilter
     * Geht HTML durch und ruft die dazugehörigen Funktionen auf um URLs umzuwandeln
     *
     * @param string $output - html, text
     *
     * @return string
     *
     * @throws QUI\Exception
     */
    public function outputFilter(string $output): string
    {
        $this->Output->setProject($this->getProject());

        QUI::getEvents()->fireEvent('rewriteOutputBegin', [
            'Rewrite' => $this,
            'output' => $output
        ]);

        $output = $this->Output->parse($output);

        $this->setOutputContent($output);

        // fire Rewrite::onOutput
        QUI::getEvents()->fireEvent('QUI::rewriteOutput', [
            'Rewrite' => $this
        ]);

        QUI::getEvents()->fireEvent('rewriteOutput', [
            'Rewrite' => $this,
            'output' => $output
        ]);

        return $this->getOutputContent();
    }

    /**
     * Output Content bekommen
     *
     * @return string
     */
    public function getOutputContent(): string
    {
        return $this->output_content;
    }

    /**
     * Output Content setzen
     *
     * @param string $str
     */
    public function setOutputContent(string $str)
    {
        $this->output_content = $str;
    }

    /**
     * Mail Protection gegen SPAM
     * Wandelt die Mail Addressen so um das ein BOT nichts mit anfangen kann
     *
     * @param string $output
     *
     * @return string
     */
    public function outputMail(string $output): string
    {
        if (isset($output[3]) && strpos($output[3], '@') !== false) {
            [$user, $domain] = explode("@", $output[3]);

            return 'href="' . URL_DIR . '[mailto]' . $user . '[at]' . $domain . '" target="mail_protection"';
        }

        return $output[0];
    }

    /**
     * Return the url params as index array
     *
     * @return array
     */
    public function getUrlParamsList(): array
    {
        if (!isset($_REQUEST['_url'])) {
            return [];
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
     * @param Site|Edit $Site
     *
     * @throws QUI\Exception
     */
    public function registerPath($paths, $Site)
    {
        $Project = $Site->getProject();
        $table = QUI::getDBProjectTableName('paths', $Project);

        // check, if path is the same, if yes, we have nothing to do
        if (is_string($paths)) {
            $alreadyRegistered = QUI::getDataBase()->fetch([
                'from' => $table,
                'where' => [
                    'id' => $Site->getId(),
                    'path' => $paths
                ]
            ]);

            if (count($alreadyRegistered)) {
                return;
            }
        }

        $currentPaths = QUI::getDataBase()->fetch([
            'from' => $table
        ]);

        $this->unregisterPath($Site);

        if (!is_array($paths)) {
            $paths = [$paths];
        }

        // cleanup paths - use only paths
        foreach ($paths as $key => $path) {
            $paths[$key] = parse_url($path, PHP_URL_PATH);
        }

        foreach ($paths as $path) {
            QUI::getDataBase()->insert($table, [
                'id' => $Site->getId(),
                'path' => $path
            ]);
        }

        // change children - quiqqer/quiqqer#334
        $currentPathsIds = array_map(function ($entry) {
            return $entry['id'];
        }, $currentPaths);

        $childrenIds = array_flip($Site->getChildrenIdsRecursive());

        /**
         * Helper for site event triggering
         *
         * @param $eventName
         * @param $Site
         */
        $triggerEvent = function ($eventName, $Site) {
            try {
                QUI::getEvents()->fireEvent($eventName, [$Site], true);
            } catch (QUI\ExceptionStack $Exception) {
                $list = $Exception->getExceptionList();

                foreach ($list as $Exc) {
                    /* @var $Exc \Exception */
                    QUI\System\Log::addWarning($Exc->getMessage());
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addWarning($Exception->getMessage());
            }
        };

        // save children
        foreach ($currentPathsIds as $childId) {
            if (isset($childrenIds[$childId])) {
                try {
                    $Child = $Project->get($childId)->getEdit();

                    $triggerEvent('siteSaveBefore', $Child);

                    // ich denke wir brauchen kein children save,
                    // nur die events damit die registerPaths ausgeführt werden
                    // falls doch:
                    // $Child->save(QUI::getUsers()->getSystemUser());

                    $triggerEvent('siteSave', $Child);
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::addNotice($Exception->getMessage());
                }
            }
        }
    }

    /**
     * Unregister a rewrite path
     *
     * @param Site|Edit $Site
     *
     * @throws QUI\Exception
     */
    public function unregisterPath($Site)
    {
        $Project = $Site->getProject();
        $table = QUI::getDBProjectTableName('paths', $Project);

        QUI::getDataBase()->delete($table, [
            'id' => $Site->getId()
        ]);
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
     * @deprecated
     */
    public function getUrlFromSite(array $params = [], array $getParams = []): string
    {
        return $this->Output->getSiteUrl($params, $getParams);
    }
}
