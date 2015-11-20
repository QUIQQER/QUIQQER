<?php

/**
 * This file contains QUI\Template
 */

namespace QUI;

use QUI;
use QUI\Utils\Security\Orthos;

/**
 * Template Engine Manager
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 *
 * @event   onTemplateGetHeader [ $this ]
 */
class Template extends QUI\QDOM
{
    /**
     * Registered template engines
     *
     * @var array
     */
    protected $_engines = array();

    /**
     * Header extentions
     *
     * @var array
     */
    protected $_header = array();

    /**
     * Footer extentions
     *
     * @var array
     */
    protected $_footer = array();

    /**
     * assigned vars
     *
     * @var array
     */
    protected $_assigned = array();

    /**
     * modules that loaded after the onload event
     *
     * @var array
     */
    protected $_onLoadModules = array();

    /**
     * site type tpl
     *
     * @var string
     */
    protected $_typetpl = '';

    /**
     * constructor
     */
    public function __construct()
    {
        $this->setAttribute('mootools', true);
        $this->setAttribute('requirejs', true);
        $this->setAttribute('html5', true);

        // defaults
        $this->setAttributes(array(
            'mootools'       => true,
            'requirejs'      => true,
            'html5'          => true,
            'content-header' => true,
            'content-body'   => true
        ));
    }

    /**
     * Load the registered engines
     */
    public function load()
    {
        $this->_engines = self::getConfig()->toArray();
    }

    /**
     * Register a param for the Template engine
     * This registered param would be assigned to the Template Engine at the getEngine() method
     *
     * @param string $param
     * @param mixed $value
     */
    public function assignGlobalParam($param, $value)
    {
        $this->_assigned[$param] = $value;
    }

    /**
     * Return the Template Config object
     *
     * @return QUI\Config
     */
    static function getConfig()
    {
        if (!file_exists(CMS_DIR . 'etc/templates.ini.php')) {
            file_put_contents(CMS_DIR . 'etc/templates.ini.php', '');
        }

        return QUI::getConfig('etc/templates.ini.php');
    }

    /**
     * Get the standard template engine
     *
     * if $admin=true, admin template plugins were loaded
     *
     * @param boolean $admin - (optionsl) is the template for the admin or frontend? <- param depricated
     *
     * @return QUI\Interfaces\Template\Engine
     * @throws QUI\Exception
     */
    public function getEngine($admin = false)
    {
        if (empty($this->_engines)) {
            $this->load();
        }

        $engine = QUI::conf('template', 'engine');

        if (!isset($this->_engines[$engine])) {
            throw new QUI\Exception('Template Engine not found!');
        }

        /* @var $Engine QUI\Interfaces\Template\Engine */
        $Engine     = new $this->_engines[$engine]($admin);
        $implements = class_implements($Engine);

        if (!isset($implements['QUI\\Interfaces\\Template\\Engine'])) {
            throw new QUI\Exception(
                'The Template Engine implements not from QUI\Interfaces\Template\Engine'
            );
        }

        if (!empty($this->_assigned)) {
            $Engine->assign($this->_assigned);
        }

        return $Engine;
    }

    /**
     * Register a template engine
     *
     * @param string $name
     * @param string $class - must a class that implements QUI\Interfaces\Template\Engine
     */
    static function registerEngine($name, $class)
    {
        $Conf = self::getConfig();
        $Conf->setValue($name, null, $class);
        $Conf->save();
    }

    /**
     * Extend the head <head>...</head>
     *
     * @param string $str
     * @param integer $prio
     */
    public function extendHeader($str, $prio = 3)
    {
        $prio = (int)$prio;

        if (!isset($this->_header[$prio])) {
            $this->_header[$prio] = '';
        }

        $_str = $this->_header[$prio];
        $_str .= $str;

        $this->_header[$prio] = $_str;
    }

    /**
     *
     * @param string $cssPath
     * @param int $prio
     */
    public function extendHeaderWithCSSFile($cssPath, $prio = 3)
    {
        $this->extendHeader(
            '<link href="' . $cssPath . '" rel="stylesheet" type="text/css" />',
            $prio
        );
    }

    /**
     * @param string $jsPath
     * @param boolean $async
     * @param integer $prio
     */
    public function extendHeaderWithJavaScriptFile($jsPath, $async = true, $prio = 3)
    {
        if ($async) {
            $this->extendHeader(
                '<script src="' . $jsPath . '" async></script>',
                $prio
            );

            return;
        }

        $this->extendHeader(
            '<script src="' . $jsPath . '"></script>',
            $prio
        );
    }

    /**
     * Add Code to the bottom of the html
     *
     * @param string $str
     * @param integer $prio
     */
    public function extendFooter($str, $prio = 3)
    {
        $prio = (int)$prio;

        if (!isset($this->_header[$prio])) {
            $this->_footer[$prio] = '';
        }

        $_str = $this->_footer[$prio];
        $_str .= $str;

        $this->_footer[$prio] = $_str;
    }

    /**
     * Add the JavaScript File to the bottom of the html
     *
     * @param string $jsPath
     * @param boolean $async
     * @param integer $prio
     */
    public function extendFooterWithJavaScriptFile($jsPath, $async = true, $prio = 3)
    {
        if ($async) {
            $this->extendFooter(
                '<script src="' . $jsPath . '" async></script>',
                $prio
            );

            return;
        }

        $this->extendFooter(
            '<script src="' . $jsPath . '"></script>',
            $prio
        );
    }

    /**
     * Add a javascript module, that laoded at the onload event
     *
     * @param string $module
     */
    public function addOnloadJavaScriptModule($module)
    {
        $this->_onLoadModules[] = $module;
    }

    /**
     * Prepares the contents of a template
     *
     * @param QUI\Projects\Site|QUI\Projects\Site\Edit $Site
     *
     * @return string
     */
    public function fetchTemplate($Site)
    {
        /* @var $Site QUI\Projects\Site */
        $Project = $Site->getProject();

        $Engine          = $this->getEngine();
        $Users           = QUI::getUsers();
        $Rewrite         = QUI::getRewrite();
        $Locale          = QUI::getLocale();
        $Template        = $this;
        $projectTemplate = $Project->getAttribute('template');

        $User = $Users->getUserBySession();

        // header
        $_header = $this->_header;

        foreach ($_header as $key => $str) {
            $Engine->extendHeader($str, $key);
        }

        $this->setAttribute('Project', $Project);
        $this->setAttribute('Site', $Site);
        $this->setAttribute('Engine', $Engine);

        // Zuweisungen
        $Engine->assign(array(
            'URL_DIR'     => URL_DIR,
            'URL_BIN_DIR' => URL_BIN_DIR,
            'URL_LIB_DIR' => URL_LIB_DIR,
            'URL_VAR_DIR' => URL_VAR_DIR,
            'URL_OPT_DIR' => URL_OPT_DIR,
            'URL_USR_DIR' => URL_USR_DIR,
            'User'        => $User,
            'Locale'      => $Locale,
            'L'           => $Locale,
            'Template'    => $Template,
            'Site'        => $Site,
            'Project'     => $Project,
            'Rewrite'     => $Rewrite,
            'lastUpdate'  => QUI::getPackageManager()->getLastUpdateDate()
        ));

        /**
         * find the index.html
         */

        $default_tpl   = LIB_DIR . 'templates/index.html';
        $project_tpl   = USR_DIR . $Project->getAttribute('name') . '/lib/index.html';
        $project_index = USR_DIR . $Project->getAttribute('name') . '/lib/index.php';

//        $template_tpl   = false;
//        $template_index = false;

        $tpl = $default_tpl;

        // standard template
        if (!$projectTemplate) {
            QUI\System\Log::addInfo(
                'Project has no standard template. Please set a standard template to the project'
            );

            $vhosts      = QUI::getRewrite()->getVHosts();
            $projectName = $Project->getName();

            foreach ($vhosts as $vhost) {

                if (isset($vhost['project'])
                    && $vhost['project'] == $projectName
                    && !empty($vhost['template'])
                ) {

                    $projectTemplate = $vhost['template'];
                    break;
                }
            }
        }

        $template_tpl   = OPT_DIR . $projectTemplate . '/index.html';
        $template_index = OPT_DIR . $projectTemplate . '/index.php';

        if ($template_tpl && file_exists($template_tpl)) {
            $tpl = $template_tpl;

            $Engine->assign(array(
                'URL_TPL_DIR' => URL_OPT_DIR . $projectTemplate . '/',
                'TPL_DIR'     => OPT_DIR . $projectTemplate . '/',
            ));
        }

        if (file_exists($project_tpl)) {
            $tpl = $project_tpl;

            $Engine->assign(array(
                'URL_TPL_DIR' => URL_USR_DIR . $Project->getAttribute('name') . '/',
                'TPL_DIR'     => USR_DIR . $Project->getAttribute('name') . '/',
            ));
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
            require $project_index;

        } else {
            if ($template_index && file_exists($template_index)) {
                require $template_index;
            }
        }


        // load template scripts
        $siteScript    = false;
        $projectScript = false;

        $siteType = $Site->getAttribute('type');
        $siteType = explode(':', $siteType);

        if (isset($siteType[0]) && isset($siteType[1])) {
            $package = $siteType[0];
            $type    = $siteType[1];

            // site template
            $siteScript = OPT_DIR . $package . '/' . $type . '.php';

            // project template
            $projectScript
                = USR_DIR . 'lib/' . $projectTemplate . '/' . $type
                  . '.php';
        }

        if ($siteType[0] == 'standard') {
            // site template
            $siteScript
                = OPT_DIR . $projectTemplate . '/standard.php';
        }

        // includes
        if ($siteScript) {
            $siteScript = Orthos::clearPath(realpath($siteScript));

            if ($siteScript) {
                require $siteScript;
            }
        }

        if ($projectScript) {
            $projectScript = Orthos::clearPath(realpath($projectScript));

            if ($projectScript) {
                require $projectScript;
            }
        }

        QUI::getEvents()->fireEvent('templateSiteFetch', array($this, $Site));

        $result = $Engine->fetch($tpl);

        // footer extend
        $footer       = $this->_footer;
        $footerExtend = '';

        foreach ($footer as $_str) {
            $footerExtend .= $_str;
        }

        $result = str_replace('</body>', $footerExtend . '</body>', $result);

        return $result;
    }

    /**
     * Return the a html header
     * With all important meta entries and quiqqer libraries
     *
     * @return string
     */
    public function getHeader()
    {
        $Project = $this->getAttribute('Project');
        $Site    = $this->getAttribute('Site');
        $Engine  = $this->getAttribute('Engine');

        $siteType = $Site->getAttribute('type');
        $siteType = explode(':', $siteType);
        $files    = array();

        if (isset($siteType[0]) && isset($siteType[1])) {
            $package = $siteType[0];
            $type    = $siteType[1];

            // type css
            $siteStyle  = OPT_DIR . $package . '/bin/' . $type . '.css';
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

        QUI::getEvents()->fireEvent('templateGetHeader', array($this));

        // locale files
        try {
            $files = QUI\Translator::getJSTranslationFiles(
                $Project->getLang()
            );

        } catch (QUI\Exception $Exception) {

        }

        $locales = array();

        foreach ($files as $package => $file) {
            $locales[] = $package . '/' . $Project->getLang();
        }


        $headers      = $this->_header;
        $headerExtend = '';

        foreach ($headers as $_str) {
            $headerExtend .= $_str;
        }

        // custom css
        $customCSS = $Project->getName() . '/bin/custom.css';
        $customJS  = $Project->getName() . '/bin/custom.js';

        if (file_exists(USR_DIR . $customCSS)) {
            $headerExtend
                .= '<link rel="stylesheet" href="' . URL_USR_DIR . $customCSS
                   . '" />';
        }

        if (file_exists(USR_DIR . $customJS)) {
            $headerExtend .= '<script src="' . URL_USR_DIR . $customJS
                             . '"></script>';
        }

        // assign
        $Engine->assign(array(
            'Project'         => $Project,
            'Site'            => $Site,
            'Engine'          => $Engine,
            'localeFiles'     => $locales,
            'loadModuleFiles' => $this->_onLoadModules,
            'headerExtend'    => $headerExtend,
            'ControlManager'  => new QUI\Control\Manager(),
            'Canonical'       => new QUI\Projects\Site\Canonical($Site),
            'lastUpdate'      => QUI::getPackageManager()->getLastUpdateDate()
        ));

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
    public function getLayout($params = array())
    {
        if (is_array($params)) {
            $this->setAttributes($params);
        }

        $Project  = $this->getAttribute('Project');
        $layout   = $this->getLayoutType();
        $template = OPT_DIR . $Project->getAttribute('template');

        if (!$layout) {
            return $this->getBody($params);
        }

        $layoutFile = $template . '/' . $layout . '.html';
        $Engine     = $this->getAttribute('Engine');

        return $Engine->fetch($layoutFile);
    }

    /**
     * Return the layout type
     *
     * @return string|false
     */
    public function getLayoutType()
    {
        $Project = $this->getAttribute('Project');
        $Site    = $this->getAttribute('Site');
        $layout  = $Site->getAttribute('layout');

        if (!$layout) {
            $layout = $Project->getAttribute('layout');
        }

        $template = OPT_DIR . $Project->getAttribute('template');
        $siteXML  = $template . '/site.xml';

        if (!$layout || !is_dir($template) && !file_exists($siteXML)) {
            return false;
        }

        $Layout     = QUI\Utils\XML::getLayoutFromXml($siteXML, $layout);
        $layoutFile = $template . '/' . $layout . '.html';

        if (!$Layout || !file_exists($layoutFile)) {
            return false;
        }

        return $layout;
    }

    /**
     * Return the Body of the Template
     * -> body.html
     *
     * @param array $params - body params
     *
     * @return string
     */
    public function getBody($params = array())
    {
        /* @var $Project QUI\Projects\Project */
        /* @var $Site QUI\Projects\Site */
        /* @var $Engine QUI\Interfaces\Template\Engine */

        if (is_array($params)) {
            $this->setAttributes($params);
        }

        $Project = $this->getAttribute('Project');
        $Site    = $this->getAttribute('Site');
        $Engine  = $this->getAttribute('Engine');

        $template = LIB_DIR . 'templates/standard.html';

        $siteType = $Site->getAttribute('type');
        $siteType = explode(':', $siteType);

        if (isset($siteType[0]) && isset($siteType[1])) {
            $package = $siteType[0];
            $type    = $siteType[1];

            // site template
            $siteTemplate = OPT_DIR . $package . '/' . $type . '.html';
            $siteStyle    = OPT_DIR . $package . '/bin/' . $type . '.css';

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
            $projectTemplate
                = USR_DIR . $Project->getName() . '/lib/' . $type
                  . '.html';

            if (file_exists($projectTemplate)) {
                $template = $projectTemplate;
            }
        }

        if ($siteType[0] == 'standard') {
            // site template
            $siteTemplate
                = OPT_DIR . $Project->getAttribute('template') . '/standard.html';

            $siteStyle
                = OPT_DIR . $Project->getAttribute('template')
                  . '/bin/standard.css';

            if (file_exists($siteStyle)) {
                $Engine->assign(
                    'siteStyle',
                    URL_OPT_DIR . $Project->getAttribute('template')
                    . '/standard.css'
                );
            }

            if (file_exists($siteTemplate)) {
                $template = $siteTemplate;
            }
        }


        if (!file_exists($template)) {
            $template = LIB_DIR . 'templates/standard.html';
        }

        return $Engine->fetch($template);
    }

    /**
     * Template für den Seitentyp
     *
     * @param array $types
     * @param string $type
     * @param string $template
     *
     * @return string
     */
    protected function _getTypeTemplate($types, $type, $template)
    {
        if (isset($types['template'])) {
            // Falls im Projekt ein Template existiert
            $tpl = USR_DIR . 'lib/' . $template . '/' . $type . '/' . $types['template'];

            if (file_exists($tpl)) {
                return $tpl;
            }

            // Falls im Plugin ein Template existiert
            $tpl = OPT_DIR . $type . '/' . $types['template'];

            if (file_exists($tpl)) {
                return $tpl;
            }
        }

        if (file_exists(USR_DIR . 'lib/' . $template . '/standard/body.html')) {
            return USR_DIR . 'lib/' . $template . '/standard/body.html';
        }

        return LIB_DIR . 'templates/standard.html';
    }
}
