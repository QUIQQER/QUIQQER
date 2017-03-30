<?php

/**
 * This file contains \QUI\Plugins\Plugin
 */

namespace QUI\Plugins;

use QUI;
use QUI\Utils\Text\XML;

/**
 * Grundklasse eines Plugins
 * Alle Plugins müssen von dieser Klasse erben
 *
 * @author www.pcsg.de (Henning Leutz)
 * @todo komplett überarbeiten -> als Paket
 * @deprecated
 */
class Plugin extends QUI\QDOM
{
    /**
     * Plugin config
     * @var \QUI\Config
     */
    protected $Config = null;

    /**
     * Admin Plugin
     * @deprecated
     * @var \QUI\Plugins\Plugin
     */
    protected $Admin = null; // Adminerweiterung

    /**
     * User extention, if really useful
     * @var UserExtend
     */
    protected $User = null; // Userweiterung

    /**
     * Default settings
     * @var array
     */
    protected $defaults = null;


    /**
     * to string
     * @return string
     */
    public function __toString()
    {
        return get_class($this);
    }

    /**
     * return the plugin config
     *
     * @return \QUI\Config|false
     */
    public function getConfig()
    {
        if ($this->Config) {
            return $this->Config;
        }

        $iniFile = CMS_DIR . 'etc/plugins/' . $this->getAttribute('name') . '.ini.php';

        if (!file_exists($iniFile)) {
            file_put_contents($iniFile, '');
        }

        $this->Config = new QUI\Config($iniFile);

        return $this->Config;
    }

    /**
     * Gibt die Plugin Config zurück
     *
     * @return array
     */
    public function getPluginIni()
    {
        return $this->getAttribute('config');
    }

    /**
     * Installationsroutine für jedes Plugin
     */
    public function install()
    {
        $this->getConfig();
        $this->setupHelper();

        // Sprache einlesen
        if (file_exists(OPT_DIR . $this->getAttribute('name') . '/locale.xml')) {
            QUI\Translator::import(
                OPT_DIR . $this->getAttribute('name') . '/locale.xml'
            );
        }

        // Datenbank aufbauen
        XML::importDataBaseFromXml(
            OPT_DIR . $this->getAttribute('name') . '/database.xml'
        );
    }

    /**
     * Abwärtskompatibilität
     * Alte setup Methoden aufrufen
     *
     * @throws \QUI\Exception
     */
    protected function setupHelper()
    {
        if (!method_exists($this, 'setup')) {
            return;
        }

        // Alle Projekte durchgehen
        $Conf = QUI\Projects\Manager::getConfig();
        $conf = $Conf->toArray();

        foreach ($conf as $project => $entrys) {
            if (isset($entrys['langs'])) {
                $langs = explode(',', $entrys['langs']);

                foreach ($langs as $lang) {
                    try {
                        $Project = QUI::getProject($project, $lang);
                        //$Project->setup(); <<--- wird das echt benötigt?

                        $this->setup($Project);
                    } catch (QUI\Exception $e) {
                        $message = 'Project [' . $project . ', ' . $lang . '] ';
                        $message .= 'Plugin [' . $this->getType() . '] ';
                        $message .= $e->getMessage();

                        $error[] = $message;
                    }
                }
            }
        }

        if (isset($error)) {
            throw new QUI\Exception(implode("\n", $error));
        }
    }

    /**
     * Deinstallationsroutine für jedes Plugin
     *
     * @param $params - Einstellungen
     * $params['database'] => false
     */
    public function uninstall($params)
    {
        // Datenbank aufbauen
        $dbfields = array();
        $Dom      = $this->getDbXml();
        $database = $Dom->getElementsByTagName('database');
        $projects = QUI\Projects\Manager::getConfig()->toArray();
        $DataBase = QUI::getDB();

        $settings = array(
            'database' => isset($params['database']) && $params['database'] ? true : false
        );

        if (method_exists($this, 'onUninstall')) {
            $this->onUninstall();
        }

        if (!$database->length) {
            $this->uninstallFiles();
            return;
        }

        // Datenbank nicht löschen, nur Dateien
        if ($settings['database'] !== true) {
            $this->uninstallFiles();
            return;
        }


        /**
         * Welche Felder gibt es
         */

        // global
        if (($global = $database->item(0)->getElementsByTagName('global')) && $global->length) {
            $tables = $global->item(0)->getElementsByTagName('table');

            for ($i = 0; $i < $tables->length; $i++) {
                $dbfields['globals'][] = QUI\Utils\DOM::dbTableDomToArray($tables->item($i));
            }
        }

        // projects
        if (($project = $database->item(0)->getElementsByTagName('projects')) && $project->length) {
            $tables = $project->item(0)->getElementsByTagName('table');

            for ($i = 0; $i < $tables->length; $i++) {
                $dbfields['projects'][] = QUI\Utils\DOM::dbTableDomToArray($tables->item($i));
            }
        }

        /**
         * Felder löschen
         */

        // globale tabellen löschen
        /*
        if (isset($dbfields['globals'])) {
            foreach ($dbfields['globals'] as $table) {
                $DataBase->deleteTableFields(
                    'pcsg_' . $table['suffix'],
                    $fields
                );
            }
        }
        */

        // projekt tabellen löschen
        if (isset($dbfields['projects'])) {
            foreach ($dbfields['projects'] as $table) {
                $suffix = $table['suffix'];
                $fields = $table['fields'];

                // Projekte durchgehen
                foreach ($projects as $name => $params) {
                    $langs = explode(',', $params['langs']);

                    foreach ($langs as $lang) {
                        $DataBase->deleteTableFields(
                            $name . '_' . $lang . '_' . $suffix,
                            $fields
                        );
                    }
                }
            }
        }

        $this->uninstallFiles();
    }

    /**
     * Dateien löschen
     */
    public function uninstallFiles()
    {
        QUI\Utils\System\File::unlink($this->getAttribute('_folder_'));
    }

    /**
     * Gibt das Admin Plugin Objekt zurück
     *
     * @deprecated
     */
    public function getAdminPlugin()
    {
        if ($this->getAttribute('admin') == false) {
            return false;
        }

        if (!is_null($this->Admin)) {
            return $this->Admin;
        }

        if (!file_exists($this->getAttribute('admin'))) {
            return false;
        }

        $class = 'Global_' . $this->getAttribute('name');

        if (!class_exists($class)) {
            require_once $this->getAttribute('admin');
        }

        if (!class_exists($class)) {
            return false;
        }

        $this->Admin = new $class();
        return $this->Admin;
    }

    /**
     * Benutzererweiterungs Plugin
     * @return Plugin
     * @deprecated
     */
    public function getUserPlugin()
    {
        if ($this->User) {
            return $this->User;
        }

        $dir  = $this->getAttribute('_folder_');
        $file = $dir . 'User.php';

        if (!file_exists($file)) {
            return false;
        }

        require_once $file;

        $class = 'UserExtend' . ucfirst($this->getAttribute('name'));

        if (!class_exists($class)) {
            return false;
        }

        $this->User = new $class();
        return $this->User;
    }

    /**
     * Gibt eine Einstellung / Konfiguration des Plugins zurück
     *
     * @param string $section
     * @param string|null $key - (optional)
     *
     * @return string|boolean
     */
    public function getSettings($section, $key = null)
    {
        $this->Config = $this->loadSetting();

        if (!$this->Config) {
            return false;
        }

        return $this->Config->get($section, $key);
    }

    /**
     * Setzt einen Config Parameter, prüft gleich den Typ, Rechte und ob dieser gesetzt werden darf
     *
     * @param string|boolean $section
     * @param string|null $key
     * @param string|null $value
     * @return void|boolean
     */
    public function setSettings($section = false, $key = null, $value = null)
    {
        $this->Config = $this->loadSetting();

        if (!$this->Config) {
            return false;
        }

        // defaults prüfen
        $defaults = $this->getDefaultSettings();

        if (!isset($defaults[$section])) {
            return false;
        }

        if (!isset($defaults[$section][$key])) {
            return false;
        }

        $default = $defaults[$section][$key];

        // typ prüfen
        switch ($default['type']) {
            case 'bool':
                $value = QUI\Utils\BoolHelper::JSBool($value);

                if ($value) {
                    $value = 1;
                } else {
                    $value = 0;
                }
                break;

            case 'int':
                $value = (int)$value;
                break;

            case 'string':
                $value = QUI\Utils\Security\Orthos::cleanHTML($value);
                $value = QUI\Utils\Security\Orthos::clearMySQL($value);
                break;
        }

        $this->Config->set($section, $key, $value);
        return true;
    }

    /**
     * Speichert die Konfiguration
     */
    public function saveSettings()
    {
        $this->Config = $this->loadSetting();

        if (!$this->Config) {
            return false;
        }

        $this->Config->save();
        return true;
    }

    /**
     * Prüft ob es eine Einstellungsseite / Fenster / Panel gibt
     *
     * @return boolean
     */
    public function existsSettingsWindow()
    {
        $Dom      = $this->getSettingsXml();
        $settings = $Dom->getElementsByTagName('plugin_settings');

        return $settings->length ? true : false;
    }

    /**
     * Gibt Einstellungsfenster zurück, wenn eines gesetzt ist
     *
     * @return \QUI\Controls\Windows\Window|false
     * @todo rewrite auf \QUI\Utils\DOM
     */
    public function getSettingsWindow()
    {
        $Dom      = $this->getSettingsXml();
        $settings = $Dom->getElementsByTagName('plugin_settings');

        if (!$settings->length) {
            return false;
        }

        /* @var $Settings \DOMElement */
        $Settings = $settings->item(0);
        $winlist  = $Settings->getElementsByTagName('window');

        if (!$winlist->length) {
            return false;
        }

        /* @var $Window \DOMElement */
        $Window = $winlist->item(0);
        $Win    = new QUI\Controls\Windows\Window();

        // settings laden
        $defaults = $this->getDefaultSettings();

        foreach ($defaults as $section => $entry) {
            foreach ($entry as $key => $value) {
                $defaults[$section][$key] = $this->getSettings($section, $key);
            }
        }

        // default
        $Win->setAttribute('name', $this->getAttribute('name') . 'Window');
        $Win->setAttribute('plugin', $this->getAttribute('name'));
        $Win->setAttribute('onsubmit', '_pcsg.Plugins.Settings.save');
        $Win->setAttribute('config', $defaults);
        $Win->setAttribute('winopen', '_pcsg.Plugins.Settings.open');

        // name
        if ($Window->getAttribute('name')) {
            $Win->setAttribute('name', $Window->getAttribute('name'));
        }

        // titel
        $titles = $Settings->getElementsByTagName('title');

        if ($titles->item(0)) {
            $Win->setAttribute('title', $titles->item(0)->nodeValue);
        }

        // Link zum öffnen des Popups
        $winopen = $Settings->getElementsByTagName('winopen');

        if ($winopen->item(0) && !empty($winopen->item(0)->nodeValue)) {
            $Win->setAttribute('winopen', $winopen->item(0)->nodeValue);
        }

        // Window Parameter
        $params = $Window->getElementsByTagName('params');

        if ($params->length) {
            $children = $params->item(0)->childNodes;

            for ($i = 0; $i < $children->length; $i++) {
                $Param = $children->item($i);

                if ($Param->nodeName == '#text') {
                    continue;
                }

                if ($Param->nodeName == 'image') {
                    $Win->setAttribute('image', QUI\Utils\DOM::parseVar($Param->nodeValue));
                    continue;
                }

                $Win->setAttribute($Param->nodeName, $Param->nodeValue);
            }
        }

        // buttons bauen
        $btnlist = $Settings->getElementsByTagName('categories');

        if ($btnlist->length) {
            $children = $btnlist->item(0)->childNodes;

            for ($i = 0; $i < $children->length; $i++) {
                $Param = $children->item($i);

                if ($Param->nodeName != 'category') {
                    continue;
                }

                /* @var $Param \DOMElement */
                $Button = new QUI\Controls\Buttons\Button();
                $Button->setAttribute('name', $Param->getAttribute('name'));
                $Button->setAttribute('onclick', '_pcsg.Plugins.Settings.getButtonContent');
                $Button->setAttribute('plugin', $this->getAttribute('name'));
                $Button->setAttribute('onload', '_pcsg.Plugins.Settings.onload');
                $Button->setAttribute('onunload', '_pcsg.Plugins.Settings.onunload');

                // Extra on / unload
                if (($onload = $Param->getElementsByTagName('onload')) && $onload->length) {
                    $Button->setAttribute('onloadExtra', $onload->item(0)->nodeValue);
                }

                if (($onunload = $Param->getElementsByTagName('onunload')) && $onunload->length) {
                    $Button->setAttribute('onunloadExtra', $onunload->item(0)->nodeValue);
                }


                $btnParams = $Param->childNodes;

                for ($b = 0; $b < $btnParams->length; $b++) {
                    switch ($btnParams->item($b)->nodeName) {
                        case 'text':
                        case 'title':
                        case 'onclick':
                            $Button->setAttribute(
                                $btnParams->item($b)->nodeName,
                                $btnParams->item($b)->nodeValue
                            );
                            break;

                        case 'image':
                            $value = $btnParams->item($b)->nodeValue;

                            $Button->setAttribute(
                                $btnParams->item($b)->nodeName,
                                QUI\Utils\DOM::parseVar($value)
                            );
                            break;
                    }
                }

                if ($Param->getAttribute('type') == 'projects') {
                    $projects = QUI\Projects\Manager::getProjects();

                    foreach ($projects as $project) {
                        $Button->setAttribute(
                            'text',
                            str_replace('{$project}', $project, $Button->getAttribute('text'))
                        );

                        $Button->setAttribute(
                            'title',
                            str_replace('{$project}', $project, $Button->getAttribute('title'))
                        );

                        $Button->setAttribute('section', $project);

                        $Win->appendButton($Button);
                    }

                    continue;
                }

                $Win->appendButton($Button);
            }
        }

        return $Win;
    }

    /**
     * Sucht die gewünschte Categorie
     *
     * @param string $name
     * @return \DOMNode|boolean
     * @deprecated
     */
    public function getSettingsCategory($name)
    {
        return XML::getSettingCategoryFromXml(
            OPT_DIR . $this->getAttribute('name') . '/settings.xml',
            $name
        );
    }

    /**
     * User Erweiterungen
     */

    /**
     * Gibt die DOM Tabs zurück
     * @return array
     */
    public function getUserTabs()
    {
        return XML::getTabsFromDom(
            $this->getUserXml()
        );
    }

    /**
     * Das HTML eines Tabs bekommen
     *
     * @param string $name
     * @return string
     */
    public function getUserTabHtml($name)
    {
        return QUI\Utils\DOM::getTabHTML($name, $this);
    }

    /**
     * Ladet die Benutzer-Tabs in die Toolbar
     *
     * @param \QUI\Controls\Toolbar\Bar $Tabbar
     * @param \QUI\Users\User $User
     */
    public function loadUserTabs(QUI\Controls\Toolbar\Bar $Tabbar, QUI\Users\User $User)
    {
        // Alte JS Tabs über PHP
        if ($this->getUserPlugin()) {
            $UserPlugin = $this->getUserPlugin();

            if (method_exists($UserPlugin, 'setTabs')) {
                $UserPlugin->setTabs($Tabbar, $User);
            }
        }

        // Neue XML Tabs
//        $tabs = $this->getUserTabs();

        QUI\Utils\DOM::addTabsToToolbar(
            $this->getUserTabs(),
            $Tabbar,
            $this->getAttribute('name')
        );
    }

    /**
     * Gibt die erweiterten Benutzereigenschaften zurück
     *
     * @return array
     */
    public function getUserAttributes()
    {
        $Dom  = $this->getUserXml();
        $atts = $Dom->getElementsByTagName('attribute');

        if (!$atts->length) {
            return array();
        }

        $list = array();

        for ($a = 0; $a < $atts->length; $a++) {
            /* @var $Attr \DOMElement */
            $Attr = $atts->item($a);

            if ($Attr->nodeName == '#text') {
                continue;
            }

            $list[] = $Attr->getAttribute('name');
        }

        return $list;
    }

    /**
     * Speichert die Extra Attribute ins Extrafeld des Benutzers
     * Falls kein onSave gesetzt ist
     *
     * @param \QUI\Users\User $User
     */
    public function onUserSave(QUI\Users\User $User)
    {
        if ($this->getUserPlugin()) {
            $AdminPlugin = $this->getUserPlugin();

            if (method_exists($AdminPlugin, 'onSave')) {
                $AdminPlugin->onSave($User);
                return;
            }
        }

        $attributes = $this->getUserAttributes();

        // Extra Felder setzen
        foreach ($attributes as $attr) {
            if (!$User->getAttribute($attr)) {
                continue;
            }

            $User->setExtra($attr, $User->getAttribute($attr));
        }
    }

    /**
     * Ladet die Pluginfelder aus den Extras in den Benutzern
     * Falls kein onLoad gesetzt ist
     *
     * @param \QUI\Users\User $User
     */
    public function onUserLoad(QUI\Users\User $User)
    {
        // Plugin Attribute setzen
        $attr = $this->getUserAttributes();

        foreach ($attr as $att) {
            if ($User->getAttribute($att) == false) {
                $User->setAttribute($att, '');
            }
        }

        // Alte Plugins laden - PHP Extend
        if ($this->getUserPlugin()) {
            $AdminPlugin = $this->getUserPlugin();

            if (method_exists($AdminPlugin, 'onLoad')) {
                $AdminPlugin->onLoad($User);
                return;
            }
        }

        // über XML
        $attributes = $this->getUserAttributes();

        // Extra Felder auf Attribute setzen
        foreach ($attributes as $attr) {
            if (!$User->getExtra($attr)) {
                continue;
            }

            $User->setAttribute($attr, $User->getExtra($attr));
        }
    }

    /**
     * Gibt die URL Addresse des Plugins zurück
     *
     * @return string
     */
    public function getUrlDir()
    {
        return str_replace(
            OPT_DIR,
            URL_OPT_DIR,
            $this->getDir()
        );
    }

    /**
     * Gibt die Pfad Addresse des Plugins zurück
     *
     * @return string
     */
    public function getDir()
    {
        return $this->getAttribute('_folder_');
    }

    /**
     * Plugin JavaScript Files
     *
     * @return array
     */
    public function getJS()
    {
        return array();
    }

    /**
     * Plugin CSS Files
     *
     * @return array
     */
    public function getCSS()
    {
        return array();
    }

    /**
     * Plugin beim MVC registrieren
     *
     * @param \QUI\Projects\Project|boolean $Project - optional
     * @return string
     */
    public function getTemplateHeader($Project = false)
    {
        $files = $this->getHeaderFiles($Project);

        $js  = $files['js'];
        $css = $files['css'];

        $str = '<script type="text/javascript">
            _pcsg.MVC.define(
                "plugin/' . $this->getAttribute('name') . '", ' .
               (!empty($js) ? json_encode($js) : 'false') . ', ' .
               (!empty($css) ? json_encode($css) : 'false') . ', ' .
               'false, ' .
               json_encode($this->getJsLangFiles())
               . ');
        </script>';

        return $str;
    }

    /**
     * Header Dateien des Plugins
     *
     * @param \QUI\Projects\Project|boolean $Project - optional
     * @return array
     */
    public function getHeaderFiles($Project = false)
    {
        $css = $this->getCSS();
        $js  = $this->getJS();

        // sprachdateien
        $langdir = $this->getDir() . 'bin/lang/';

        if (file_exists($langdir) &&
            is_dir($langdir)
        ) {
            /* @var $Users QUI\Users\Manager */
            $file = $langdir . QUI::getLocale()->getCurrent() . '.js';

            if (file_exists($file)) {
                array_unshift(
                    $js,
                    'order!' . $this->getUrlDir() . 'bin/lang/' . QUI::getLocale()->getCurrent() . '.js'
                );
                array_unshift($js, 'order!' . URL_BIN_DIR . 'js/ptools/locale/locale.js');
            }
        }

        if (empty($css)) {
            $path = explode('/', $this->getDir());
            array_pop($path);
            array_pop($path);

            $path = implode('/', $path);
            $file = $path . '/bin/style.css';

            if (file_exists($file)) {
                $css[] = str_replace(OPT_DIR, URL_OPT_DIR, $file);
            }
        }

        // Alle Dateien in Sachen Projekt prüfen
        if ($Project) {
            $project_path = 'bin/' . $Project->getAttribute('template') . '/';

            $_css = str_replace(URL_OPT_DIR, '', $css);
            $_js  = str_replace(URL_OPT_DIR, '', $js);

            // CSS Files
            foreach ($_css as $key => $value) {
                if (file_exists(USR_DIR . $project_path . $value)) {
                    $css[$key] = URL_USR_DIR . $project_path . $value;
                }
            }

            // CSS Plugin File
            // abwärtskompatibilität :-/ @todo : depricated
            $css_plg = $project_path . str_replace(URL_OPT_DIR, '', $this->getUrlDir()) . 'style.css';

            if (file_exists(USR_DIR . $css_plg) && !in_array(URL_USR_DIR . $css_plg, $css)) {
                $css[] = URL_USR_DIR . $css_plg;
            }

            // JS Files
            foreach ($js as $key => $value) {
                if (file_exists(USR_DIR . $project_path . $value)) {
                    $js[$key] = URL_USR_DIR . $project_path . $value;
                }
            }
        }

        return array(
            'js'  => $js,
            'css' => $css
        );
    }

    /**
     * Alle JavaScript Sprachdateien
     *
     * @return array
     */
    public function getJsLangFiles()
    {
        $langs   = array();
        $langdir = $this->getDir() . 'bin/lang/';

        $files = QUI\Utils\System\File::readDir($langdir);

        foreach ($files as $file) {
            if (strpos($file, '.js') === false) {
                continue;
            }

            $file = explode('.js', $file);

            foreach ($file as $lang) {
                if (strlen($lang) === 2) {
                    $langs[$lang] = $this->getUrlDir() . 'bin/lang/' . $lang . '.js';
                }
            }
        }

        return $langs;
    }

    /**
     * Protected
     */

    /**
     * Gibt das Setting Objekt eines Plugins zurück
     *
     * @throws \QUI\Exception
     * @return \DomDocument
     */
    protected function getSettingsXml()
    {
        if (isset($this->SettingsDOM)) {
            return $this->SettingsDOM;
        }

        $this->SettingsDOM = XML::getDomFromXml(
            OPT_DIR . $this->getAttribute('name') . '/settings.xml'
        );

        return $this->SettingsDOM;
    }

    /**
     * Gibt das XML für die Benutzererweiterungen zurück
     *
     * @throws \QUI\Exception
     * @return \DomDocument
     */
    protected function getUserXml()
    {
        if (isset($this->UserDOM)) {
            return $this->UserDOM;
        }

        $this->UserDOM = XML::getDomFromXml(
            OPT_DIR . $this->getAttribute('name') . '/user.xml'
        );

        return $this->UserDOM;
    }

    /**
     * Gibt das Datenbank Objekt eines Plugins zurück
     *
     * @throws \QUI\Exception
     * @return \DomDocument
     */
    protected function getDbXml()
    {
        if (isset($this->DbDOM)) {
            return $this->DbDOM;
        }

        $this->DbDOM = XML::getDomFromXml(
            OPT_DIR . $this->getAttribute('name') . '/database.xml'
        );

        return $this->DbDOM;
    }

    /**
     * Gibt das Default Config Array zurück
     *
     * Default = Einstellungen aus der config.xml
     * Welche Einstellungen sind vorhanden
     *
     * @return array
     */
    protected function getDefaultSettings()
    {
        if ($this->defaults) {
            return $this->defaults;
        }

        $Dom      = $this->getSettingsXml();
        $settings = $Dom->getElementsByTagName('plugin_settings');
        $projects = QUI\Projects\Manager::getProjects();

        if (!$settings->length) {
            return array();
        }

        $configs = $settings->item(0)->getElementsByTagName('config');

        if (!$configs) {
            return array();
        }

        $children = $configs->item(0)->childNodes;
        $result   = array();

        for ($i = 0; $i < $children->length; $i++) {
            $Param = $children->item($i);

            if ($Param->nodeName == '#text') {
                continue;
            }

            if ($Param->nodeName == 'section') {
                /* @var $Param \DOMElement */
                $name  = $Param->getAttribute('name');
                $confs = $Param->getElementsByTagName('conf');

                if ($Param->getAttribute('type') == 'project') {
                    foreach ($projects as $project) {
                        $result[$project] = $this->parseConfs($confs);
                    }

                    continue;
                }

                $result[$name] = $this->parseConfs($confs);
            }
        }

        $this->defaults = $result;

        return $this->defaults;
    }

    /**
     * Parse project config
     *
     * @param \DOMNode|\DOMElement|\DOMNodeList $confs
     * @return array
     */
    protected function parseConfs($confs)
    {
        $result = array();

        foreach ($confs as $Conf) {
            $type    = 'string';
            $default = '';

            /* @var $Conf \DOMElement */
            $types    = $Conf->getElementsByTagName('type');
            $defaults = $Conf->getElementsByTagName('defaultvalue');

            // type
            if ($types && $types->length) {
                $type = $types->item(0)->nodeValue;
            }

            // default
            if ($defaults && $defaults->length) {
                $default = $defaults->item(0)->nodeValue;
            }

            $result[$Conf->getAttribute('name')] = array(
                'type'    => $type,
                'default' => $default
            );
        }

        return $result;
    }

    /**
     * Konfiguration des Plugins laden
     *
     * @return QUI\Config
     */
    protected function loadSetting()
    {
        if ($this->Config) {
            return $this->Config;
        }

        // Init.d Pfad erstellen
        QUI\Utils\System\File::mkdir(CMS_DIR . 'etc/plugins/');

        $iniFile = CMS_DIR . 'etc/plugins/' . $this->getAttribute('name') . '.ini.php';

        $this->Config = new QUI\Config($iniFile);

        return $this->Config;
    }
}
