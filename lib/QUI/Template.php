<?php

/**
 * This file contains QUI\Template
 */

namespace QUI;

use QUI;
use QUI\Utils\Security\Orthos;

use function class_exists;
use function class_implements;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function htmlspecialchars;
use function implode;
use function is_array;
use function realpath;
use function str_replace;
use function strpos;
use function trim;

use const ETC_DIR;
use const PHP_EOL;

/**
 * Template Engine Manager
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 *
 * @event onTemplateGetHeader [ $this ]
 */
class Template extends QUI\QDOM
{
    /**
     * Registered template engines
     *
     * @var array
     */
    protected array $engines = [];

    /**
     * Header extentions
     *
     * @var array
     */
    protected array $header = [];

    /**
     * Footer extentions
     *
     * @var array
     */
    protected array $footer = [];

    /**
     * assigned vars
     *
     * @var array
     */
    protected array $assigned = [];

    /**
     * modules that loaded after the onload event
     *
     * @var array
     */
    protected array $onLoadModules = [];

    /**
     * @var ?QUI\Package\Package
     */
    protected ?Package\Package $TemplatePackage = null;

    /**
     * @var ?QUI\Package\Package
     */
    protected ?Package\Package $TemplateParent = null;

    /**
     * @var null|QUI\Projects\Project
     */
    protected ?Projects\Project $Project = null;

    /**
     * Project template list
     *
     * @var array
     */
    protected array $templates = [];

    /**
     * constructor
     */
    public function __construct()
    {
        $this->setAttribute('mootools', true);
        $this->setAttribute('requirejs', true);
        $this->setAttribute('html5', true);

        // defaults
        $this->setAttributes([
            'mootools' => true,
            'requirejs' => true,
            'html5' => true,
            'content-header' => true,
            'content-body' => true,
            'template-header' => true,
            'template-footer' => true,
            'noConflict' => false // @todo in Version 2.0 -> true becomes the default
        ]);
    }

    /**
     * Register a template engine
     *
     * @param string $name
     * @param string $class - must a class that implements QUI\Interfaces\Template\EngineInterface
     * @throws QUI\Exception
     */
    public static function registerEngine(string $name, string $class)
    {
        $Conf = self::getConfig();
        $Conf->setValue($name, null, $class);
        $Conf->save();
    }

    /**
     * Return the Template Config object
     *
     * @return QUI\Config
     *
     * @throws QUI\Exception
     */
    public static function getConfig(): Config
    {
        if (!file_exists(CMS_DIR . 'etc/templates.ini.php')) {
            file_put_contents(CMS_DIR . 'etc/templates.ini.php', '');
        }

        return QUI::getConfig('etc/templates.ini.php');
    }

    /**
     * Return the current header extensions
     *
     * @return array
     */
    public function getExtendHeader(): array
    {
        return $this->header;
    }

    /**
     *
     * @param string $cssPath
     * @param int $priority
     */
    public function extendHeaderWithCSSFile(string $cssPath, int $priority = 3)
    {
        $this->extendHeader(
            '<link href="' . $cssPath . '" rel="stylesheet" type="text/css" />',
            $priority
        );
    }

    /**
     * Extend the head <head>...</head>
     *
     * @param string $str
     * @param integer $priority
     */
    public function extendHeader(string $str, int $priority = 3)
    {
        if (!isset($this->header[$priority])) {
            $this->header[$priority] = '';
        }

        $_str = $this->header[$priority];
        $_str .= $str;

        $this->header[$priority] = $_str;
    }

    /**
     * @param string $jsPath
     * @param boolean $async
     * @param integer $priority
     */
    public function extendHeaderWithJavaScriptFile(
        string $jsPath,
        bool $async = true,
        int $priority = 3
    ) {
        if ($async) {
            $this->extendHeader(
                '<script src="' . $jsPath . '" async></script>',
                $priority
            );

            return;
        }

        $this->extendHeader(
            '<script src="' . $jsPath . '"></script>',
            $priority
        );
    }

    /**
     * Add the JavaScript File to the bottom of the html
     *
     * @param string $jsPath
     * @param boolean $async
     * @param integer $priority
     */
    public function extendFooterWithJavaScriptFile(
        string $jsPath,
        bool $async = true,
        int $priority = 3
    ) {
        if ($async) {
            $this->extendFooter(
                '<script src="' . $jsPath . '" async></script>',
                $priority
            );

            return;
        }

        $this->extendFooter(
            '<script src="' . $jsPath . '"></script>',
            $priority
        );
    }

    /**
     * Add Code to the bottom of the html
     *
     * @param string $str
     * @param integer $priority
     */
    public function extendFooter(
        string $str,
        int $priority = 3
    ) {
        if (!isset($this->footer[$priority])) {
            $this->footer[$priority] = '';
        }

        $_str = $this->footer[$priority];
        $_str .= $str;

        $this->footer[$priority] = $_str;
    }

    /**
     * Return the current footer extensions
     *
     * @return array
     */
    public function getExtendFooter(): array
    {
        return $this->footer;
    }

    /**
     * Add a javascript module, that laoded at the onload event
     *
     * @param string $module
     */
    public function addOnloadJavaScriptModule(string $module)
    {
        $this->onLoadModules[] = $module;
    }

    /**
     * Returns the url for a file
     * - also considers template inheritance - template parent
     *
     * @param $path
     * @return string
     */
    public function getTemplateUrl($path): string
    {
        $template = $this->TemplatePackage->getName();
        $absolute = OPT_DIR . $template . '/' . $path;

        if (file_exists($absolute)) {
            return URL_OPT_DIR . $template . '/' . $path;
        }

        if ($this->TemplateParent) {
            $template = $this->TemplateParent->getName();
            $absolute = OPT_DIR . $template . '/' . $path;

            if (file_exists($absolute)) {
                return URL_OPT_DIR . $template . '/' . $path;
            }
        }

        return $path;
    }

    /**
     * Get absolute path to current template package
     *
     * @return string
     */
    public function getTemplatePath(): string
    {
        $template = $this->TemplatePackage->getName();

        return OPT_DIR . $template . '/';
    }

    /**
     * @return Package\Package|null
     */
    public function getTemplatePackage(): ?Package\Package
    {
        return $this->TemplatePackage;
    }

    /**
     * Return a template output
     *
     * @param string $template - Path to a template
     * @param array $params (optional) - Engine params
     * @return string
     *
     * @throws QUI\Exception
     */
    public function fetchTemplate(string $template, array $params = []): string
    {
        $Engine = $this->getEngine();
        $Engine->assign($params);

        return $Engine->fetch($template);
    }

    /**
     * Get the standard template engine
     *
     * if $admin=true, admin template plugins were loaded
     *
     * @param boolean $admin - (optional) is the template for the admin or frontend? <- param depricated
     *
     * @return QUI\Interfaces\Template\EngineInterface
     * @throws QUI\Exception
     */
    public function getEngine(bool $admin = false): Interfaces\Template\EngineInterface
    {
        if (empty($this->engines)) {
            $this->load();
        }

        $engine = QUI::conf('template', 'engine');

        if (!isset($this->engines[$engine]) || !class_exists($this->engines[$engine])) {
            $engine = $this->checkSmarty4Engine($engine);
        }

        /* @var $Engine QUI\Interfaces\Template\EngineInterface */
        $Engine = new $this->engines[$engine]($admin);
        $implements = class_implements($Engine);

        if (!isset($implements['QUI\Interfaces\Template\EngineInterface'])) {
            throw new QUI\Exception(
                'The Template Engine implements not from QUI\Interfaces\Template\EngineInterface'
            );
        }

        $Engine->assign('__TEMPLATE__', $this);

        QUI::getTemplateManager()->assignGlobalParam('Project', QUI::getRewrite()->getProject());

        if (!empty($this->assigned)) {
            $Engine->assign($this->assigned);
        }

        return $Engine;
    }

    /**
     * Check if the given template engine is Smarty4 and perform necessary actions
     *
     * @param mixed $engine - The template engine to check
     * @return string - Returns the name of the template engine if successful
     * @throws QUI\Exception - Throws an exception if the template engine is not found
     */
    protected function checkSmarty4Engine($engine): string
    {
        // smarty 4 workaround
        if ($engine === 'smarty3' && class_exists('QUI\Smarty\Smarty4')) {
            $Config = QUI::getConfig('etc/conf.ini.php');
            $Config->setValue('template', 'engine', 'smarty4');
            $Config->save();

            QUI::$Conf->reload();

            $templateIni = ETC_DIR . 'templates.ini.php';
            $iniContent = file_get_contents($templateIni);

            if (strpos($templateIni, 'QUI\\Smarty\\Smarty4') === false) {
                file_put_contents(
                    $templateIni,
                    trim($iniContent) . PHP_EOL . 'smarty4="QUI\Smarty\Smarty4"'
                );
            }

            static::getConfig()->reload();
            $this->load();

            return 'smarty4';
        }

        throw new QUI\Exception('Template Engine not found!');
    }

    /**
     * Load the registered engines
     *
     * @throws QUI\Exception
     */
    public function load()
    {
        $this->engines = self::getConfig()->toArray();
    }

    /**
     * Register a param for the Template engine
     * This registered param would be assigned to the Template Engine at the getEngine() method
     *
     * @param string $param
     * @param mixed $value
     */
    public function assignGlobalParam(string $param, $value)
    {
        $this->assigned[$param] = $value;
    }

    /**
     * Prepares the contents of a template
     *
     * @param QUI\Projects\Site|QUI\Projects\Site\Edit $Site
     *
     * @return string
     *
     * @throws QUI\Exception
     */
    public function fetchSite($Site): string
    {
        /* @var $Site QUI\Projects\Site */
        $Project = $Site->getProject();
        $Engine = $this->getEngine();

        $this->Project = $Project;

        $Users = QUI::getUsers();
        $Rewrite = QUI::getRewrite();
        $Locale = QUI::getLocale();
        $Template = $this;

        $projectTemplate = $Project->getAttribute('template');
        $hasTemplateParent = false;

        if ($Site->getAttribute('quiqqer.site.template')) {
            $projectTemplate = $Site->getAttribute('quiqqer.site.template');
        }

        try {
            $this->TemplatePackage = QUI::getPackage($projectTemplate);
            $hasTemplateParent = $this->TemplatePackage->hasTemplateParent();

            if ($hasTemplateParent) {
                $this->TemplateParent = $this->TemplatePackage->getTemplateParent();
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        $User = $Users->getUserBySession();

        $this->setAttribute('Project', $Project);
        $this->setAttribute('Site', $Site);
        $this->setAttribute('Engine', $Engine);

        // Zuweisungen
        $Engine->assign([
            'URL_DIR' => URL_DIR,
            'URL_BIN_DIR' => URL_BIN_DIR,
            'URL_LIB_DIR' => URL_LIB_DIR,
            'URL_VAR_DIR' => URL_VAR_DIR,
            'URL_OPT_DIR' => URL_OPT_DIR,
            'URL_USR_DIR' => URL_USR_DIR,
            'User' => $User,
            'Locale' => $Locale,
            'L' => $Locale,
            'Template' => $Template,
            'Site' => $Site,
            'Project' => $Project,
            'Rewrite' => $Rewrite,
            'lastUpdate' => QUI::getPackageManager()->getLastUpdateDate(),
            'Canonical' => new QUI\Projects\Site\Canonical($Site)
        ]);

        /**
         * find the index.html
         */

        $default_tpl = LIB_DIR . 'templates/index.html';
        $project_tpl = USR_DIR . $Project->getName() . '/lib/index.html';
        $project_index = USR_DIR . $Project->getName() . '/lib/index.php';

        //        $template_tpl   = false;
        //        $template_index = false;

        $tpl = $default_tpl;

        // standard template
        if (!$projectTemplate) {
            QUI\System\Log::addInfo(
                'Project has no standard template. Please set a standard template to the project'
            );

            $vhosts = QUI::getRewrite()->getVHosts();
            $projectName = $Project->getName();

            foreach ($vhosts as $vhost) {
                if (
                    isset($vhost['project'])
                    && $vhost['project'] == $projectName
                    && !empty($vhost['template'])
                ) {
                    $projectTemplate = $vhost['template'];

                    try {
                        $this->TemplatePackage = QUI::getPackage($projectTemplate);
                        $hasTemplateParent = $this->TemplatePackage->hasTemplateParent();

                        if ($hasTemplateParent) {
                            $this->TemplateParent = $this->TemplatePackage->getTemplateParent();
                        }
                    } catch (QUI\Exception $Exception) {
                        QUI\System\Log::writeDebugException($Exception);
                    }

                    break;
                }
            }
        }

        $template_tpl = OPT_DIR . $projectTemplate . '/index.html';
        $template_index = OPT_DIR . $projectTemplate . '/index.php';

        if ($template_tpl && !file_exists($template_tpl) && $hasTemplateParent) {
            $template_tpl = OPT_DIR . $this->TemplateParent->getName() . '/index.html';
        }

        if ($template_index && !file_exists($template_index) && $hasTemplateParent) {
            $template_index = OPT_DIR . $this->TemplateParent->getName() . '/index.php';
        }

        if ($template_tpl && file_exists($template_tpl)) {
            $tpl = $template_tpl;

            $Engine->assign([
                'URL_TPL_DIR' => URL_OPT_DIR . $projectTemplate . '/',
                'TPL_DIR' => OPT_DIR . $projectTemplate . '/',
            ]);
        }

        if (file_exists($project_tpl)) {
            $tpl = $project_tpl;

            $Engine->assign([
                'URL_TPL_DIR' => URL_USR_DIR . $Project->getAttribute('name') . '/',
                'TPL_DIR' => USR_DIR . $Project->getAttribute('name') . '/',
            ]);
        }

        // @todo suffix template prüfen
        /*
        $suffix = $Rewrite->getSuffix();

        if ( file_exists(USR_DIR .'lib/'. $Project->getAttribute('template') .'/index' . $suffix) ) {
            $tpl = USR_DIR .'lib/'. $Project->getAttribute('template') .'/index' . $suffix;
        }
        */

        // scripts file (index.php)
        if (file_exists($project_index)) {
            include $project_index;
        } else {
            if ($template_index && file_exists($template_index)) {
                include $template_index;
            }
        }


        // load template scripts
        $siteScript = false;
        $projectScript = false;

        $siteType = $Site->getAttribute('type');
        $siteType = explode(':', $siteType);

        if (isset($siteType[0]) && isset($siteType[1])) {
            $package = $siteType[0];
            $type = $siteType[1];

            // site template
            $siteScript = OPT_DIR . $package . '/' . $type . '.php';

            // project template
            $projectScript = USR_DIR . 'lib/' . $projectTemplate . '/' . $type . '.php';

            // template
            $tplScript = OPT_DIR . $projectTemplate . '/' . $package . '/' . $type . '.php';

            if (file_exists($tplScript)) {
                $siteScript = $tplScript;
            }

            // site template
            $siteUsrScript = USR_DIR . $Project->getAttribute('name') . '/lib/' . $package . '/' . $type . '.php';

            if (file_exists($siteUsrScript)) {
                $siteScript = $siteUsrScript;
            }
        }

        if ($siteType[0] == 'standard') {
            // site template
            $siteScript = OPT_DIR . $projectTemplate . '/standard.php';
        }

        // includes
        if ($siteScript) {
            $siteScript = Orthos::clearPath(realpath($siteScript));

            if ($siteScript) {
                include $siteScript;
            }
        }

        if ($projectScript) {
            $projectScript = Orthos::clearPath(realpath($projectScript));

            if ($projectScript) {
                include $projectScript;
            }
        }

        QUI::getEvents()->fireEvent('templateSiteFetch', [$this, $Site]);

        $result = $Engine->fetch($tpl);

        // footer extend
        $footer = $this->footer;
        $footerExtend = implode('', $footer);
        $result = str_replace('</body>', $footerExtend . '</body>', $result);

        return $result;
    }

    /**
     * Return the template title
     * eq: <title></title>
     *
     * @return string
     *
     * @throws QUI\Exception
     */
    public function getTitle(): string
    {
        $Site = $this->getAttribute('Site');
        $Project = $this->getAttribute('Project');

        if (
            $Site->getAttribute('quiqqer.meta.site.title') &&
            $Site->getAttribute('quiqqer.meta.site.title') !== ''
        ) {
            QUI::getEvents()->fireEvent('templateGetSiteTitle', [$this, $Site]);

            return $Site->getAttribute('meta.seotitle');
        }

        // prefix / suffix
        if ($Project) {
            $projectName = $Project->getName();
            $localeGroup = 'project/' . $projectName;
            $localePrefix = 'template.prefix';
            $localeSuffix = 'template.suffix';

            if (QUI::getLocale()->exists($localeGroup, $localePrefix)) {
                $prefix = QUI::getLocale()->get($localeGroup, $localePrefix);

                if (!empty($prefix)) {
                    $this->setAttribute('site_title_prefix', htmlspecialchars($prefix) . ' ');
                }
            }

            if (QUI::getLocale()->exists($localeGroup, $localeSuffix)) {
                $suffix = QUI::getLocale()->get($localeGroup, $localeSuffix);

                if (!empty($suffix)) {
                    $this->setAttribute('site_title_suffix', ' ' . htmlspecialchars($suffix));
                }
            }
        }

        QUI::getEvents()->fireEvent('templateGetSiteTitle', [$this, $Site]);

        $title = $this->getAttribute('site_title_prefix');
        $title .= $Site->getAttribute('meta.seotitle');
        $title .= $this->getAttribute('site_title_suffix');
        $title .= $this->getAttribute('site_title');

        return htmlspecialchars($title);
    }

    /**
     * Return the a html header
     * With all important meta entries and quiqqer libraries
     *
     * @return string
     *
     * @throws QUI\Exception
     */
    public function getHeader(): string
    {
        /* @var $Project QUI\Projects\Project */
        $Project = $this->getAttribute('Project');
        $Site = $this->getAttribute('Site');
        $Engine = $this->getAttribute('Engine');

        $siteType = $Site->getAttribute('type');
        $siteType = explode(':', $siteType);
        $files = [];

        if (isset($siteType[0]) && isset($siteType[1])) {
            $package = $siteType[0];
            $type = $siteType[1];

            // type css
            $siteStyle = OPT_DIR . $package . '/bin/' . $type . '.css';
            $siteScript = OPT_DIR . $package . '/bin/' . $type . '.js';

            if (file_exists($siteStyle)) {
                $Engine->assign(
                    'siteStyle',
                    URL_OPT_DIR . $package . '/bin/' . $type . '.css'
                );
            }

            if (file_exists($siteScript)) {
                $Engine->assign(
                    'siteScript',
                    URL_OPT_DIR . $package . '/bin/' . $type . '.js'
                );
            }

            $realSitePath = OPT_DIR . $package . '/' . $type . '.css';

            if (file_exists($realSitePath)) {
                //$css = file_get_contents( $realSitePath );

                $this->extendHeader(
                    '<style>' . file_get_contents($realSitePath) . '</style>'
                );
            }
        }

        QUI::getEvents()->fireEvent('templateGetHeader', [$this]);

        // locale files
        try {
            $files = QUI\Translator::getJSTranslationFiles(
                $Project->getLang()
            );
        } catch (QUI\Exception) {
        }

        $locales = [];

        foreach ($files as $package => $file) {
            $locales[] = $package . '/' . $Project->getLang();
        }


        $headers = $this->header;
        $headerExtend = implode('', $headers);

        // custom css
        $customCSS = $Project->getName() . '/bin/custom.css';
        $customJS = $Project->getName() . '/bin/custom.js';

        if (file_exists(USR_DIR . $customCSS)) {
            $headerExtend .= '<link rel="stylesheet" href="' . URL_USR_DIR . $customCSS . '" />';
        }

        if (file_exists(USR_DIR . $customJS)) {
            $headerExtend .= '<script src="' . URL_USR_DIR . $customJS . '"></script>';
        }

        // prefix / suffix
        $projectName = $Project->getName();
        $localeGroup = 'project/' . $projectName;
        $localePrefix = 'template.prefix';
        $localeSuffix = 'template.suffix';

        if (QUI::getLocale()->exists($localeGroup, $localePrefix)) {
            $prefix = QUI::getLocale()->get($localeGroup, $localePrefix);

            if (!empty($prefix)) {
                $this->setAttribute(
                    'site_title_prefix',
                    htmlspecialchars($prefix) . ' '
                );
            }
        }

        if (QUI::getLocale()->exists($localeGroup, $localeSuffix)) {
            $suffix = QUI::getLocale()->get($localeGroup, $localeSuffix);

            if (!empty($suffix)) {
                $this->setAttribute(
                    'site_title_suffix',
                    ' ' . htmlspecialchars($suffix)
                );
            }
        }

        // template logo
        if ($Project->getConfig('logo')) {
            $Engine->assign(
                'projectLogo',
                $Project->getMedia()->getLogoImage()->getSizeCacheUrl()
            );
        }

        // assign
        $Engine->assign([
            'Project' => $Project,
            'Site' => $Site,
            'Engine' => $Engine,
            'localeFiles' => $locales,
            'loadModuleFiles' => $this->onLoadModules,
            'headerExtend' => $headerExtend,
            'ControlManager' => new QUI\Control\Manager(),
            'Canonical' => $Engine->getCanonical(),
            'lastUpdate' => QUI::getPackageManager()->getLastUpdateDate(),
            'languages' => implode(',', $Project->getLanguages()),
            'systemCountry' => QUI::conf('globals', 'country')
        ]);

        if ($this->getAttribute('noConflict')) {
            return $Engine->fetch(LIB_DIR . 'templates/headerNoConflict.html');
        }

        return $Engine->fetch(LIB_DIR . 'templates/header.html');
    }

    /**
     * Return the layout of the template
     * If a template is set to the project
     *
     * @param array $params - body params
     *
     * @return string
     */
    public function getLayout(array $params = []): string
    {
        if (is_array($params)) {
            $this->setAttributes($params);
        }

        $layout = $this->getLayoutType();

        if (!$layout) {
            return $this->getBody($params);
        }

        $Project = $this->getAttribute('Project');
        $templates = $this->getProjectTemplates($Project);

        foreach ($templates as $template) {
            $layoutFile = $template . '/' . $layout . '.html';

            if (file_exists($layoutFile)) {
                return $this->getAttribute('Engine')->fetch($layoutFile);
            }
        }

        return '';
    }

    /**
     * Return the layout type
     *
     * @return string|false
     */
    public function getLayoutType()
    {
        $Project = $this->getAttribute('Project');
        $Site = $this->getAttribute('Site');

        QUI\Utils\Site::setRecursiveAttribute($Site, 'layout');

        $layout = $Site->getAttribute('layout');
        $templates = $this->getProjectTemplates($Project);

        if (!$layout) {
            return false;
        }

        if (!is_array($templates)) {
            $templates = [];
        }

        foreach ($templates as $template) {
            $siteXML = $template . '/site.xml';

            if (!file_exists($siteXML)) {
                continue;
            }

            $Layout = QUI\Utils\Text\XML::getLayoutFromXml($siteXML, $layout);
            $layoutFile = $template . '/' . $layout . '.html';

            if ($Layout && file_exists($layoutFile)) {
                return $layout;
            }
        }

        return false;
    }

    /**
     * Return all project templates which have a site.xml
     * -> consider template inheritance
     *
     * @param QUI\Projects\Project $Project
     * @return array
     */
    protected function getProjectTemplates($Project)
    {
        $name = $Project->getName();

        if (isset($this->templates[$name])) {
            return $this->templates[$name];
        }

        $templates = [];

        $template = OPT_DIR . $Project->getAttribute('template');
        $siteXML = $template . '/site.xml';

        if (file_exists($siteXML)) {
            $templates[] = $template;
        }

        try {
            $Package = QUI::getPackage($Project->getAttribute('template'));
            $Parent = $Package->getTemplateParent();

            if ($Parent) {
                $siteXML = $Parent->getXMLFilePath('site.xml');

                if (file_exists($siteXML)) {
                    $templates[] = OPT_DIR . $Parent->getName();
                }
            }
        } catch (QUI\Exception) {
        }

        $this->templates[$name] = $templates;

        if (empty($templates)) {
            $this->templates[$name] = false;
        }

        return $this->templates[$name];
    }

    /**
     * Return the Body of the Template
     * -> body.html
     *
     * @param array $params - body params
     *
     * @return string
     */
    public function getBody(array $params = []): string
    {
        /* @var $Project QUI\Projects\Project */
        /* @var $Site QUI\Projects\Site */
        /* @var $Engine QUI\Interfaces\Template\EngineInterface */

        if (is_array($params)) {
            $this->setAttributes($params);
        }

        $Project = $this->getAttribute('Project');
        $Site = $this->getAttribute('Site');
        $Engine = $this->getAttribute('Engine');

        $template = LIB_DIR . 'templates/standard.html';

        $siteType = $Site->getAttribute('type');
        $siteType = explode(':', $siteType);

        if (isset($siteType[0]) && isset($siteType[1])) {
            $package = $siteType[0];
            $type = $siteType[1];

            // site template
            $siteTemplate = OPT_DIR . $package . '/' . $type . '.html';
            $siteStyle = OPT_DIR . $package . '/bin/' . $type . '.css';

            if (file_exists($siteStyle)) {
                $Engine->assign(
                    'siteStyle',
                    URL_OPT_DIR . $package . '/bin/' . $type . '.css'
                );
            }

            if (file_exists($siteTemplate)) {
                $template = $siteTemplate;
            }

            // project template
            $projectTemplate = USR_DIR . $Project->getName() . '/lib/' . $type . '.html';

            if (file_exists($projectTemplate)) {
                $template = $projectTemplate;
            }
        }

        if ($siteType[0] == 'standard') {
            // site template
            $siteTemplate = OPT_DIR . $Project->getAttribute('template') . '/standard.html';
            $siteStyle = OPT_DIR . $Project->getAttribute('template') . '/bin/standard.css';

            if (file_exists($siteStyle)) {
                $Engine->assign(
                    'siteStyle',
                    URL_OPT_DIR . $Project->getAttribute('template') . '/standard.css'
                );
            }

            if (file_exists($siteTemplate)) {
                $template = $siteTemplate;
            }
        }

        if (!file_exists($template)) {
            $template = LIB_DIR . 'templates/standard.html';
        }

        $Engine->assign([
            'template' => $template
        ]);

        return $Engine->fetch($template);
    }
}
