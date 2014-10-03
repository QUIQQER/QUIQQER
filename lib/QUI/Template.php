<?php

/**
 * This file contains \QUI\Template
 */

namespace QUI;

/**
 * Template Engine Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onTemplateGetHeader [ $this ]
 */

class Template extends \QUI\QDOM
{
    /**
     * Registered template engines
     * @var array
     */
    protected $_engines = array();

    /**
     * Header extentions
     * @var array
     */
    protected $_header = array();

    /**
     * assigned vars
     * @var array
     */
    protected $_assigned = array();

    /**
     * modules that loaded after the onload event
     * @var Array
     */
    protected $_onLoadModules = array();

    /**
     * site type tpl
     * @var String
     */
    protected $_typetpl = '';

    /**
     * constructor
     */
    public function __construct()
    {
        $this->setAttribute( 'mootools' , true );
        $this->setAttribute( 'requirejs' , true );
        $this->setAttribute( 'html5' , true );

        // defaults
        $this->setAttributes(array(
            'mootools'  => true,
            'requirejs' => true,
            'html5'     => true,

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
     * @param unknown $param
     * @param unknown $value
     */
    public function assignGlobalParam($param, $value)
    {
        $this->_assigned[ $param ] = $value;
    }

    /**
     * Return the Template Config object
     * @return \QUI\Config
     */
    static function getConfig()
    {
        if ( !file_exists( CMS_DIR .'etc/templates.ini.php' ) ) {
            file_put_contents( CMS_DIR .'etc/templates.ini.php', '' );
        }

        return \QUI::getConfig( 'etc/templates.ini.php' );
    }

    /**
     * Get the standard template engine
     *
     * if $admin=true, admin template plugins were loaded
     *
     * @param Integer $admin - is the template for the admin or frontend? <- param depricated
     * @return \QUI\Interfaces\Template\Engine
     */
    public function getEngine($admin=false)
    {
        if ( empty( $this->_engines ) ) {
            $this->load();
        }

        $engine = \QUI::conf( 'template', 'engine' );

        if ( !isset( $this->_engines[ $engine ] ) ) {
            throw new \QUI\Exception( 'Template Engine not found!' );
        }

        $Engine     = new $this->_engines[ $engine ]( $admin );
        $implements = class_implements( $Engine );

        if ( !isset( $implements['QUI\\Interfaces\\Template\\Engine'] ) )
        {
            throw new \QUI\Exception(
                'The Template Engine implements not from \QUI\Interfaces\Template\Engine'
            );
        }

        if ( !empty( $this->_assigned ) ) {
            $Engine->assign( $this->_assigned );
        }

        return $Engine;
    }

    /**
     * Register a template engine
     *
     * @param String $name
     * @param String $class - must a class that implements \QUI\Interfaces\Template\Engine
     */
    static function registerEngine($name, $class)
    {
        $Conf = self::getConfig();
        $Conf->setValue( $name, null, $class );
        $Conf->save();
    }

    /**
     * Extend the head <head>...</head>
     *
     * @param String $str
     * @param Integer $prio
     */
    public function extendHeader($str, $prio=3)
    {
        $prio = (int)$prio;

        if ( !isset( $this->_header[ $prio ] ) ) {
            $this->_header[ $prio ] = '';
        }

        $_str  = $this->_header[ $prio ];
        $_str .= $str;

        $this->_header[ $prio ] = $_str;
    }

    /**
     * Add a javascript module, that laoded at the onload event
     * @param String $module
     */
    public function addOnloadJavaScriptModule($module)
    {
        $this->_onLoadModules[] = $module;
    }

    /**
     * Return the Content of the Site
     *
     */
    public function get()
    {

    }

    /**
     * Prepares the contents of a template
     *
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @return String
     */
    public function fetchTemplate($Site)
    {
        /* @var $Site \QUI\Projects\Site */
        $Project = $Site->getProject();

        $Engine  = $this->getEngine();
        $Users   = \QUI::getUsers();
        $Rewrite = \QUI::getRewrite();
        $Locale  = \QUI::getLocale();

        $User = $Users->getUserBySession();

        // header
        $_header = $this->_header;

        foreach ( $_header as $key => $str ) {
            $Engine->extendHeader( $str, $key );
        }

        $this->setAttribute( 'Project', $Project );
        $this->setAttribute( 'Site', $Site );
        $this->setAttribute( 'Engine', $Engine );

        // Zuweisungen
        $Engine->assign(array(
            'URL_DIR'     => URL_DIR,
            'URL_BIN_DIR' => URL_BIN_DIR,
            'URL_LIB_DIR' => URL_LIB_DIR,
            'URL_VAR_DIR' => URL_VAR_DIR,
            'URL_OPT_DIR' => URL_OPT_DIR,
            'URL_USR_DIR' => URL_USR_DIR,

            'User'     => $User,
            'Locale'   => $Locale,
            'L'        => $Locale,
            'Template' => $this,
            'Site'     => $Site,
            'Project'  => $Project,
            'Rewrite'  => $Rewrite
        ));

        /**
         * find the index.html
         */

        $default_tpl  = LIB_DIR .'templates/index.html';

        $project_tpl   = USR_DIR . $Project->getAttribute('name') .'/lib/index.html';
        $project_index = USR_DIR . $Project->getAttribute('name') .'/lib/index.php';

        $template_tpl   = false;
        $template_index = false;

        $tpl = $default_tpl;

        if ( $Project->getAttribute('template') )
        {
            $template_tpl   = OPT_DIR . $Project->getAttribute('template') .'/index.html';
            $template_index = OPT_DIR . $Project->getAttribute('template') .'/index.php';
        }

        if ( $template_tpl && file_exists( $template_tpl ) )
        {
            $tpl = $template_tpl;

            $Engine->assign(array(
                'URL_TPL_DIR' => URL_OPT_DIR . $Project->getAttribute('template') .'/',
                'TPL_DIR'     => OPT_DIR . $Project->getAttribute('template') .'/',
            ));
        }

        if ( file_exists( $project_tpl ) )
        {
            $tpl = $project_tpl;

            $Engine->assign(array(
                'URL_TPL_DIR' => URL_USR_DIR . $Project->getAttribute('name') .'/',
                'TPL_DIR'     => USR_DIR . $Project->getAttribute('name') .'/',
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
        if ( file_exists( $project_index ) )
        {
            require $project_index;

        } else if ( $template_index && file_exists( $template_index ) )
        {
            require $template_index;
        }


        try
        {
            return $Engine->fetch( $tpl );

        } catch ( \Exception $Exception )
        {
            \QUI\System\Log::writeException( $Exception );
        }

        return '';
    }

    /**
     * Return the a html header
     * With all important meta entries and quiqqer libraries
     *
     * @return String
     */
    public function getHeader()
    {
        $Project = $this->getAttribute( 'Project' );
        $Site    = $this->getAttribute( 'Site' );
        $Engine  = $this->getAttribute( 'Engine' );

        $siteType = $Site->getAttribute( 'type' );
        $siteType = explode( ':', $siteType );

        if ( isset( $siteType[ 0 ] ) && isset( $siteType[ 1 ] ) )
        {
            $package = $siteType[ 0 ];
            $type    = $siteType[ 1 ];

            // @todo needed?
            // type css
            $siteStyle  = OPT_DIR . $package .'/bin/'. $type .'.css';
            $siteScript = OPT_DIR . $package .'/bin/'. $type .'.js';

            if ( file_exists( $siteStyle ) )
            {
                $Engine->assign(
                    'siteStyle',
                    URL_OPT_DIR . $package .'/bin/'. $type .'.css'
                );
            }

            if ( file_exists( $siteScript ) )
            {
                $Engine->assign(
                    'siteScript',
                    URL_OPT_DIR . $package .'/bin/'. $type .'.js'
                );
            }

            $realSitePath = OPT_DIR . $package .'/'. $type .'.css';

            if ( file_exists( $realSitePath ) )
            {
                $css = file_get_contents( $realSitePath );

                $this->extendHeader(
                    '<style>'. file_get_contents( $realSitePath ) .'</style>'
                );
            }
        }

        \QUI::getEvents()->fireEvent( 'templateGetHeader', array( $this ) );

        // locale files
        try
        {
            $files = \QUI\Translator::getJSTranslationFiles(
                $Project->getLang()
            );

        } catch ( \QUI\Exception $Exception )
        {

        }

        $locales = array();

        foreach ( $files as $package => $file ) {
            $locales[] = $package .'/'. $Project->getLang();
        }


        $headers      = $this->_header;
        $headerExtend = '';

        foreach ( $headers as $_str ) {
            $headerExtend .= $_str;
        }


        // assign
        $Engine->assign(array(
            'Project'         => $Project,
            'Site'            => $Site,
            'Engine'          => $Engine,
            'localeFiles'     => $locales,
            'loadModuleFiles' => $this->_onLoadModules,
            'headerExtend'    => $headerExtend,
            'ControlManager'  => new \QUI\Control\Manager(),
            'Canonical'       => new \QUI\Projects\Site\Canonical( $Site )
        ));

        return $Engine->fetch( LIB_DIR .'templates/header.html' );
    }

    /**
     * Return the Body of the Template
     * -> body.html
     *
     * @return String
     */
    public function getBody($params=array())
    {
        /* @var $Project \QUI\Projects\Project */
        /* @var $Site \QUI\Projects\Site */
        /* @var $Engine \QUI\Interfaces\Template\Engine */

        if ( is_array( $params ) ) {
            $this->setAttributes( $params );
        }

        $Project = $this->getAttribute( 'Project' );
        $Site    = $this->getAttribute( 'Site' );
        $Engine  = $this->getAttribute( 'Engine' );

        // abwärtskompatibilität
        $smarty  = $Engine;
        $Users   = \QUI::getUsers();
        $Rewrite = \QUI::getRewrite();
        $User    = $Users->getUserBySession();
        $suffix  = $Rewrite->getSuffix();

        // $this->types    = $Project->getType( $Site->getAttribute('type') );
        // $this->type     = $Site->getAttribute('type');
        $this->template = $Project->getAttribute('template');

        $package = false;
        $type    = false;

        $template = LIB_DIR .'templates/standard.html';

        $siteScript    = false;
        $siteStyle     = false;
        $projectScript = false;

        $siteType = $Site->getAttribute( 'type' );
        $siteType = explode( ':', $siteType );

        if ( isset( $siteType[ 0 ] ) && isset( $siteType[ 1 ] ) )
        {
            $package = $siteType[ 0 ];
            $type    = $siteType[ 1 ];

            // site template
            $siteTemplate = OPT_DIR . $package .'/'. $type .'.html';
            $siteScript   = OPT_DIR . $package .'/'. $type .'.php';
            $siteStyle    = OPT_DIR . $package .'/bin/'. $type .'.css';

            if ( file_exists( $siteStyle ) )
            {
                $Engine->assign(
                    'siteStyle',
                    URL_OPT_DIR . $package .'/'. $type .'.css'
                );
            }

            if ( file_exists( $siteTemplate ) ) {
                $template = $siteTemplate;
            }

            // project template
            $projectTemplate = USR_DIR .'lib/'. $this->template .'/'. $type .'.html';
            $projectScript   = USR_DIR .'lib/'. $this->template .'/'. $type .'.php';

            if ( file_exists( $projectTemplate ) ) {
                $template = $projectTemplate;
            }
        }

        // includes
        if ( $siteScript && file_exists( $siteScript ) ) {
            require $siteScript;
        }

        if ( $projectScript && file_exists( $projectScript ) ) {
            require $projectScript;
        }


        if ( !file_exists( $template ) ) {
            $template = LIB_DIR .'templates/standard.html';
        }

        return $Engine->fetch( $template );
    }

    /**
     * Template für den Seitentyp
     *
     * @param Array $types
     * @param String $type
     * @param String $template
     *
     * @return String
     */
    protected function _getTypeTemplate($types, $type, $template)
    {
        if ( isset($types['template'] ) )
        {
            // Falls im Projekt ein Template existiert
            $tpl = USR_DIR .'lib/'. $template .'/'. $type .'/'. $types['template'];

            if ( file_exists( $tpl ) ) {
                return $tpl;
            }

            // Falls im Plugin ein Template existiert
            $tpl = OPT_DIR . $type .'/'. $types['template'];

            if ( file_exists( $tpl ) ) {
                return $tpl;
            }
        }

        if ( file_exists( USR_DIR .'lib/'. $template .'/standard/body.html' ) ) {
            return USR_DIR .'lib/'. $template .'/standard/body.html';
        }

        return LIB_DIR .'templates/standard.html';
    }

    /**
     * Set the admin menu to the template
     * If the user is an administrator the admin will be insert
     *
     * @param String $html - html
     * @return String
     * @deprecated
     */
    static function setAdminMenu($html)
    {
        $User = \QUI::getUserBySession();

        // Nur bei Benutzer die in den Adminbereich dürfen macht das Menü Sinn
        if ( $User->isAdmin() == false ) {
            return $html;
        }

        $Project = \QUI\Projects\Manager::get();
        $Site    = \QUI::getRewrite()->getSite();

        // letzte body ersetzen
        $string  = $html;
        $search  = '</body>';
        $replace = '
            <script type="text/javascript">
            /* <![CDATA[ */
                if (typeof _pcsg == "undefined") {
                    var _pcsg = {};
                };

                _pcsg.Project = {
                    name : "'. $Project->getAttribute('name') .'",
                    lang : "'. $Project->getAttribute('lang') .'"
                };

                _pcsg.Site = {id : '. $Site->getId() .'};
                _pcsg.admin = {
                    link : "'. URL_SYS_DIR .'admin.php"
                };
            /* ]]> */
            </script>
            <script type="text/javascript" src="'. URL_BIN_DIR .'js/AdminPageMenu.js"></script></body>';

        return substr_replace(
            $html,
            $search,
            strrpos( $string, $search ),
            strlen( $search )
        );
    }
}
