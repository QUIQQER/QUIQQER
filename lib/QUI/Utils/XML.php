<?php

/**
 * This file contains the \QUI\Utils\XML
 */

namespace QUI\Utils;

use QUI;
use QUI\Utils\Security\Orthos;

/**
 * QUIQQER XML Util class
 *
 * Provides methods to read and work with QUIQQER XML files
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class XML
{
    /**
     * Add a menu.xml file to a contextmenu bar item
     *
     * @param QUI\Controls\Contextmenu\Bar $Menu - Menu Object
     * @param string $file - Path to XML File
     */
    public static function addXMLFileToMenu(QUI\Controls\Contextmenu\Bar $Menu, $file)
    {
        if (!file_exists($file)) {
            return;
        }

        // read the xml
        $items = self::getMenuItemsXml($file);

        foreach ($items as $Item) {
            /* @var $Item \DOMElement */
            if (!$Item->getAttribute('parent')) {
                continue;
            }

            $params = array(
                'text'    => DOM::getTextFromNode($Item),
                'name'    => $Item->getAttribute('name'),
                'icon'    => DOM::parseVar($Item->getAttribute('icon')),
                'require' => $Item->getAttribute('require'),
                'exec'    => $Item->getAttribute('exec'),
                'onClick' => 'QUI.Menu.menuClick'
            );

            $Parent = $Menu;

            if ($Item->getAttribute('parent') != '/') {
                $parent_path = explode(
                    '/',
                    trim($Item->getAttribute('parent'), '/')
                );

                foreach ($parent_path as $parent) {
                    if ($Parent) {
                        $Parent = $Parent->getElementByName($parent);
                    }
                }
            }

            // check, if item already exist
            if (!$Item->getAttribute('name')
                || !$Parent
                || $Parent->getElementByName($Item->getAttribute('name'))
            ) {
                continue;
            }

            if ($Item->getAttribute('parent') == '/') {
                $MenuItem = new QUI\Controls\Contextmenu\Baritem($params);
            } elseif ($Item->getAttribute('type') == 'seperator') {
                $MenuItem = new QUI\Controls\Contextmenu\Seperator($params);
            } else {
                $MenuItem = new QUI\Controls\Contextmenu\Menuitem($params);
            }

            if ($Item->getAttribute('disabled') == 1) {
                $MenuItem->setDisable();
            }

            if ($Parent) {
                $Parent->appendChild($MenuItem);
            }
        }
    }

    /**
     * Read the config parameter of an *.xml file and
     * create a QUI\Config if not exist or read the QUI\Config
     *
     * @param string $file - path to the xml file
     *
     * @return QUI\Config|boolean - Config | false
     */
    public static function getConfigFromXml($file)
    {
        $Dom      = self::getDomFromXml($file);
        $settings = $Dom->getElementsByTagName('settings');

        if (!$settings->length) {
            return false;
        }

        /* @var $Settings \DOMElement */
        $Settings = $settings->item(0);
        $configs  = $Settings->getElementsByTagName('config');

        if (!$configs->length) {
            return false;
        }

        /* @var $Conf \DOMElement */
        $Conf = $configs->item(0);
        $name = $Conf->getAttribute('name');

        if (!$name || empty($name)) {
            // plugin conf???
            $dirname = dirname($file);
            $package = str_replace(
                dirname(dirname($dirname)) . '/',
                '',
                $dirname
            );

            try {
                QUI::getPackageManager()->getInstalledPackage($package);

                $name = 'plugins/' . $package;

            } catch (QUI\Exception $Exception) {
                return false;
            }
        }


        $ini_file = CMS_DIR . 'etc/' . $name . '.ini.php';

        QUI\Utils\System\File::mkdir(dirname($ini_file));

        if (!file_exists($ini_file)) {
            file_put_contents($ini_file, '');
        }

        $Config = new QUI\Config($ini_file);
        $params = self::getConfigParamsFromXml($file);

        foreach ($params as $section => $key) {
            if (isset($key['default'])) {
                if ($Config->existValue($section) === false) {
                    $Config->setValue($section, $key['default']);
                }

                continue;
            }

            foreach ($key as $value => $entry) {
                // no special characters allowed
                if (preg_match('/[^0-9_a-zA-Z]/', $value)) {
                    continue;
                }

                if ($Config->existValue($section, $value) === false) {
                    $Config->setValue($section, $value, $entry['default']);
                }
            }
        }

        return $Config;
    }

    /**
     * Reads the config parameter from an *.xml
     *
     * @param string $file - path to xml file
     *
     * @return \DOMElement|boolean - DOMElement | false
     */
    public static function getConfigParamsFromXml($file)
    {
        return DOM::getConfigParamsFromDOM(
            self::getDomFromXml($file)
        );
    }

    /**
     * Reads the tools list from an *.xml
     *
     * @param string $file - path to xml file
     *
     * @return array
     */
    public static function getConsoleToolsFromXml($file)
    {
        $Dom  = self::getDomFromXml($file);
        $Path = new \DOMXPath($Dom);

        $tools = $Path->query("//console/tool");
        $list  = array();

        if (!$tools->length) {
            return array();
        }

        for ($i = 0; $i < $tools->length; $i++) {
            /* @var $Tool \DOMElement */
            $Tool = $tools->item($i);

            $exec = $Tool->getAttribute('exec');
            $file = $Tool->getAttribute('file');

            if (!empty($file)) {
                $file = DOM::parseVar($file);
                $file = Orthos::clearPath(realpath($file));

                if (file_exists($file)) {
                    require_once $file;
                }
            }

            if (!empty($exec)) {
                $list[] = $exec;
            }
        }

        return $list;
    }

    /**
     * Reads the css file list from an *.xml
     *
     * @param string $file
     *
     * @return array
     */
    public static function getWysiwygCSSFromXml($file)
    {
        $Dom  = self::getDomFromXml($file);
        $Path = new \DOMXPath($Dom);

        $CSSList = $Path->query("//wysiwyg/css");
        $files   = array();

        for ($i = 0; $i < $CSSList->length; $i++) {
            $files[] = $CSSList->item($i)->getAttribute('src');
        }

        return $files;
    }

    /**
     * Reads the database entries from an *.xml
     *
     * @param string $file - path to the xml file
     *
     * @return array
     */
    public static function getDataBaseFromXml($file)
    {
        $Dom      = self::getDomFromXml($file);
        $database = $Dom->getElementsByTagName('database');

        if (!$database->length) {
            return array();
        }

        $dbfields = array();
        $Database = $database->item(0);

        /* @var $Database \DOMElement */
        $global  = $Database->getElementsByTagName('global');
        $project = $Database->getElementsByTagName('projects');

        // global
        if ($global && $global->length) {
            /* @var $Table \DOMElement */
            $Table  = $global->item(0);
            $tables = $Table->getElementsByTagName('table');

            for ($i = 0; $i < $tables->length; $i++) {
                $dbfields['globals'][] = DOM::dbTableDomToArray(
                    $tables->item($i)
                );
            }

            if ($Table->getAttribute('execute')) {
                $dbfields['execute'][] = $Table->getAttribute('execute');
            }
        }

        // projects lang tables
        if ($project && $project->length) {
            $Table  = $project->item(0);
            $tables = $Table->getElementsByTagName('table');

            for ($i = 0; $i < $tables->length; $i++) {
                $dbfields['projects'][] = DOM::dbTableDomToArray(
                    $tables->item($i)
                );
            }
        }


        return $dbfields;
    }

    /**
     * Liefer das XML als DOMDocument zurück
     *
     * @param string $filename
     *
     * @return \DOMDocument
     */
    public static function getDomFromXml($filename)
    {
        if (strpos($filename, '.xml') === false) {
            return new \DOMDocument();
        }

        if (!file_exists($filename)) {
            return new \DOMDocument();
        }

        $Dom = new \DOMDocument();
        $Dom->load($filename);

        return $Dom;
    }

    /**
     * Reads the events from an *.xml
     * Return all <event>
     *
     * @param string $file
     *
     * @return array
     */
    public static function getEventsFromXml($file)
    {
        $Dom    = self::getDomFromXml($file);
        $events = $Dom->getElementsByTagName('events');

        if (!$events->length) {
            return array();
        }

        /* @var $Event \DOMElement */
        $Event = $events->item(0);
        $list  = $Event->getElementsByTagName('event');

        $result = array();

        for ($i = 0, $len = $list->length; $i < $len; $i++) {
            $result[] = $list->item($i);
        }

        return $result;
    }

    /**
     * Return the site types events from a site.xm file
     *
     * @param string $file
     *
     * @return array
     */
    public static function getSiteEventsFromXml($file)
    {
        $Dom  = self::getDomFromXml($file);
        $Path = new \DOMXPath($Dom);

        $types  = $Path->query("//site/types/type");
        $result = array();

        $package = str_replace(OPT_DIR, '', dirname($file));

        foreach ($types as $Type) {
            /* @var $Type \DOMElement */
            $events = $Type->getElementsByTagName('event');

            foreach ($events as $Event) {
                /* @var $Event \DOMElement */
                $result[] = array(
                    'on'   => $Event->getAttribute('on'),
                    'fire' => $Event->getAttribute('fire'),
                    'type' => $package . ':' . $Type->getAttribute('type')
                );
            }
        }

        return $result;
    }

    /**
     * Return the layout types from a xml file
     * https://dev.quiqqer.com/quiqqer/quiqqer/wikis/Site-Xml
     *
     * @param string $file
     *
     * @return array
     */
    public static function getLayoutsFromXml($file)
    {
        $Path    = new \DOMXPath(self::getDomFromXml($file));
        $layouts = $Path->query("//site/layouts/layout");
        $result  = array();

        /* @var $Layout \DOMElement */
        foreach ($layouts as $Layout) {
            $result[] = $Layout;
        }

        return $result;
    }

    /**
     * Return a specific layout DOM Node entry by its layout name
     *
     * @param string $file - path to the xml file
     * @param string $layoutName - name of the layout type
     *
     * @return bool|\DOMElement
     */
    public static function getLayoutFromXml($file, $layoutName)
    {
        $layouts = self::getLayoutsFromXml($file);

        foreach ($layouts as $Layout) {
            /* @var $Layout \DOMElement */
            if ($Layout->getAttribute('type') == $layoutName) {
                return $Layout;
            }
        }

        return false;
    }

    /**
     * Sucht die Übersetzungsgruppe aus einem DOMDocument Objekt
     *
     * @param \DOMDocument $Dom
     *
     * @return array array(
     *      array(
     *            'groups'   => 'group.name',
     *            'locales'  => array(),
     *            'datatype' => 'js'
     *      ),
     *      array(
     *            'groups'   => 'group.name',
     *            'locales'  => array(),
     *            'datatype' => ''
     *      ),
     *  );
     */
    public static function getLocaleGroupsFromDom(\DOMDocument $Dom)
    {
        $locales = $Dom->getElementsByTagName('locales');

        if (!$locales->length) {
            return array();
        }

        /* @var $Locales \DOMElement */
        $Locales = $locales->item(0);
        $groups  = $Locales->getElementsByTagName('groups');

        if (!$groups->length) {
            return array();
        }

        $result = array();

        for ($g = 0, $glen = $groups->length; $g < $glen; $g++) {
            /* @var $Group \DOMElement */
            $Group      = $groups->item($g);
            $localelist = $Group->getElementsByTagName('locale');

            $locales = array(
                'group'    => $Group->getAttribute('name'),
                'locales'  => array(),
                'datatype' => $Group->getAttribute('datatype')
            );

            for ($c = 0; $c < $localelist->length; $c++) {
                $Locale = $localelist->item($c);

                if ($Locale->nodeName == '#text') {
                    continue;
                }

                /* @var $Locale \DOMElement */
                $params = array(
                    'name' => $Locale->getAttribute('name'),
                    'html' => $Locale->getAttribute('html') ? true : false
                );

                $translations = $Locale->childNodes;

                for ($i = 0; $i < $translations->length; $i++) {
                    $Translation = $translations->item($i);

                    if ($Translation->nodeName == '#text') {
                        continue;
                    }

                    $params[$Translation->nodeName] = DOM::parseVar($Translation->nodeValue);
                }

                $locales['locales'][] = $params;
            }

            $result[] = $locales;
        }

        return $result;
    }

    /**
     * Reads the menu items from an *.xml
     *
     * @param string $file
     *
     * @return array
     */
    public static function getMenuItemsXml($file)
    {
        $Dom  = self::getDomFromXml($file);
        $menu = $Dom->getElementsByTagName('menu');

        if (!$menu->length) {
            return array();
        }

        /* @var $Menu \DOMElement */
        $Menu  = $menu->item(0);
        $items = $Menu->getElementsByTagName('item');

        if (!$items->length) {
            return array();
        }

        $result = array();

        for ($c = 0; $c < $items->length; $c++) {
            $Item = $items->item($c);

            if ($Item->nodeName == '#text') {
                continue;
            }

            $result[] = $Item;
        }

        return $result;
    }

    /**
     * Return the panel nodes from an *.xml file
     *
     * @param string $file - path to the xml file
     *
     * @return array
     */
    public static function getPanelsFromXMLFile($file)
    {
        $Dom  = self::getDomFromXml($file);
        $Path = new \DOMXPath($Dom);

        $panels = $Path->query("//quiqqer/panels/panel");

        if (!$panels->length) {
            return array();
        }

        $result = array();

        for ($i = 0, $len = $panels->length; $i < $len; $i++) {
            $result[] = DOM::parsePanelToArray(
                $panels->item($i)
            );
        }

        return $result;
    }

    /**
     * Read the permissions from an *.xml file
     *
     * @param string $file - path to the xml file
     *
     * @return array
     */
    public static function getPermissionsFromXml($file)
    {
        $Dom         = self::getDomFromXml($file);
        $permissions = $Dom->getElementsByTagName('permissions');

        if (!$permissions || !$permissions->length) {
            return array();
        }

        $package = str_replace(
            array(OPT_DIR, '/permissions.xml'),
            '',
            $file
        );

        $package = trim($package, '/');

        /* @var $Permissions \DOMElement */
        $Permissions = $permissions->item(0);
        $permission  = $Permissions->getElementsByTagName('permission');

        if (!$permission || !$permission->length) {
            return array();
        }

        $result = array();

        for ($i = 0; $i < $permission->length; $i++) {
            $data = DOM::parsePermissionToArray(
                $permission->item($i)
            );

            $data['title'] = $package . ' permission.' . $data['name'];
            $data['desc']  = $package . ' permission.' . $data['name'] . '._desc';

            $result[] = $data;
        }

        return $result;
    }

    /**
     * Reads the settings window from an *.xml and search the category
     *
     * @param string $file - path to xml file
     * @param string $name - Category name
     *
     * @return \DOMElement|boolean - DOMElement | false
     */
    public static function getSettingCategoriesFromXml($file, $name)
    {
        $Dom  = self::getDomFromXml($file);
        $Path = new \DOMXPath($Dom);

        $categories = $Path->query("//settings/window/categories/category");

        if (!$categories->length) {
            return false;
        }

        foreach ($categories as $Category) {
            /* @var $Category \DOMElement */
            if ($Category->getAttribute('name') == $name) {
                return $Category;
            }
        }

        return false;
    }

    /**
     * Return the settings window from an *.xml
     *
     * @param string $file
     *
     * @return array
     */
    public static function getSettingWindowsFromXml($file)
    {
        $Dom  = self::getDomFromXml($file);
        $Path = new \DOMXPath($Dom);

        $windows = $Path->query("//quiqqer/settings/window");

        if (!$windows->length) {
            return array();
        }

        $result = array();

        for ($i = 0, $len = $windows->length; $i < $len; $i++) {
            $result[] = $windows->item($i);
        }

        return $result;
    }

    /**
     * Return the project settings window from an *.xml
     *
     * @param string $file
     *
     * @return array
     */
    public static function getProjectSettingWindowsFromXml($file)
    {
        $Dom  = self::getDomFromXml($file);
        $Path = new \DOMXPath($Dom);

        $windows = $Path->query("//quiqqer/project/settings/window");

        if (!$windows->length) {
            return array();
        }

        $result = array();

        for ($i = 0, $len = $windows->length; $i < $len; $i++) {
            $result[] = $windows->item($i);
        }

        return $result;
    }

    /**
     * Return the site types from a xml file
     * https://dev.quiqqer.com/quiqqer/quiqqer/wikis/Site-Xml
     *
     * @param string $file
     *
     * @return array
     */
    public static function getTypesFromXml($file)
    {
        $Dom   = self::getDomFromXml($file);
        $sites = $Dom->getElementsByTagName('site');

        if (!$sites->length) {
            return array();
        }

        /* @var $Sites \DOMElement */
        $Sites = $sites->item(0);
        $types = $Sites->getElementsByTagName('types');

        if (!$types->length) {
            return array();
        }

        /* @var $Types \DOMElement */
        $Types    = $types->item(0);
        $typeList = $Types->getElementsByTagName('type');

        $result = array();

        for ($c = 0; $c < $typeList->length; $c++) {
            $Type = $typeList->item($c);

            if ($Type->nodeName == '#text') {
                continue;
            }

            $result[] = $Type;
        }

        return $result;
    }

    /**
     * Reads the tabs from an *.xml
     *
     * @param string $file
     *
     * @return array
     */
    public static function getTabsFromXml($file)
    {
        return self::getTabsFromDom(
            self::getDomFromXml($file)
        );
    }

    /**
     * Return the tabs from a DOMDocument
     *
     * @param \DOMDocument $Dom
     *
     * @return array
     */
    public static function getTabsFromDom(\DOMDocument $Dom)
    {
        $window = $Dom->getElementsByTagName('window');

        if (!$window->length) {
            return array();
        }

        return DOM::getTabs($window->item(0));
    }

    /**
     * @param \DOMDocument $Dom
     * @return array
     */
    public static function getSiteTabsFromDom(\DOMDocument $Dom)
    {
        $Path   = new \DOMXPath($Dom);
        $window = $Path->query("//site/window");

        if (!$window->length) {
            return array();
        }

        return DOM::getTabs($window->item(0));
    }

    /**
     * Reads the template_engines from an *.xml
     *
     * @param string $file
     *
     * @return array
     */
    public static function getTemplateEnginesFromXml($file)
    {
        $Dom      = self::getDomFromXml($file);
        $template = $Dom->getElementsByTagName('template_engines');

        if (!$template->length) {
            return array();
        }

        /* @var $Template \DOMElement */
        $Template = $template->item(0);
        $engines  = $Template->getElementsByTagName('engine');

        if (!$engines->length) {
            return array();
        }

        $result = array();

        for ($c = 0; $c < $engines->length; $c++) {
            $Engine = $engines->item($c);

            if ($Engine->nodeName == '#text') {
                continue;
            }

            $result[] = $Engine;
        }

        return $result;
    }

    /**
     * Reads the editor from an *.xml
     *
     * @param string $file
     *
     * @return array
     */
    public static function getWysiwygEditorsFromXml($file)
    {
        $Dom     = self::getDomFromXml($file);
        $editors = $Dom->getElementsByTagName('editors');

        if (!$editors->length) {
            return array();
        }

        /* @var $Editors \DOMElement */
        $Editors = $editors->item(0);
        $list    = $Editors->getElementsByTagName('editor');

        if (!$list->length) {
            return array();
        }

        $result = array();

        for ($c = 0; $c < $list->length; $c++) {
            $Editor = $list->item($c);

            if ($Editor->nodeName == '#text') {
                continue;
            }

            $result[] = $Editor;
        }

        return $result;
    }

    /**
     * Reads the widgets from an *.xml
     *
     * @param string $file
     *
     * @return array
     */
    public static function getWidgetsFromXml($file)
    {
        $Dom     = self::getDomFromXml($file);
        $widgets = $Dom->getElementsByTagName('widgets');

        if (!$widgets->length) {
            return array();
        }

        $result = array();

        for ($w = 0; $w < $widgets->length; $w++) {
            $Widgets = $widgets->item($w);

            if ($Widgets->nodeName == '#text') {
                continue;
            }

            /* @var $Widgets \DOMElement */
            $list = $Widgets->getElementsByTagName('widget');

            for ($c = 0; $c < $list->length; $c++) {
                $Widget = $list->item($c);

                if ($Widget->nodeName == '#text') {
                    continue;
                }

                /* @var $Widget \DOMElement */
                // widget on another location
                if ($Widget->getAttribute('src')) {
                    $file   = $Widget->getAttribute('src');
                    $file   = DOM::parseVar($file);
                    $Widget = self::getWidgetFromXml($file);

                    if ($Widget) {
                        $result[] = $Widget;
                    }

                    continue;
                }

                $Widget->setAttribute('name', md5($file . $c));

                $result[] = $Widget;
            }
        }

        return $result;
    }

    /**
     * Reads the widget from an *.xml file
     *
     * @param string $file - path to the xml file
     *
     * @return boolean|\DOMElement
     */
    public static function getWidgetFromXml($file)
    {
        $Dom    = self::getDomFromXml($file);
        $widget = $Dom->getElementsByTagName('widget');

        if (!$widget->length) {
            return false;
        }

        /* @var $Widget \DOMElement */
        $Widget = $widget->item(0);
        $Widget->setAttribute('name', md5($file));

        return $Widget;
    }

    /**
     * Save the setting to a xml specified config file
     *
     * @param string $file
     * @param array $params
     *
     * @throws QUI\Exception
     */
    public static function setConfigFromXml($file, $params)
    {
        if (QUI::getUserBySession()->isSU() === false) {
            throw new QUI\Exception(
                'You have no rights to edit the configuration.'
            );
        }

        // defaults prüfen
        $defaults = self::getConfigParamsFromXml($file);
        $Config   = self::getConfigFromXml($file);

        $checkFnMatch = function ($key, $keyList) {

            if (!is_array($keyList)) {
                return false;
            }

            foreach ($keyList as $keyEntry) {
                if (fnmatch($keyEntry, $key)) {
                    return $keyEntry;
                }
            }

            return false;
        };

        foreach ($params as $section => $param) {
            if (!is_array($param)) {
                continue;
            }

            foreach ($param as $key => $value) {
                if (!isset($defaults[$section])) {
                    continue;
                }

                // no special characters allowed
                if (preg_match('/[^0-9_a-zA-Z]/', $key)) {
                    continue;
                }

                // default key for fn match
                $defaultkeys  = array_keys($defaults[$section]);
                $fnMatchFound = $checkFnMatch($key, $defaultkeys);

                if (!$fnMatchFound && !isset($defaults[$section][$key])) {
                    continue;
                }

                if ($fnMatchFound) {
                    $default = $defaults[$section][$fnMatchFound];
                } else {
                    $default = $defaults[$section][$key];
                }

                if (empty($value) && $value !== 0 && $value !== '0') {
                    $value = $default['default'];
                }

                // typ prüfen
                switch ($default['type']) {
                    case 'bool':
                    case 'boolean':
                        $value = QUI\Utils\BoolHelper::JSBool($value);

                        if ($value) {
                            $value = 1;
                        } else {
                            $value = 0;
                        }
                        break;

                    case 'int':
                    case 'integer':
                        $value = (int)$value;
                        break;

                    case 'string':
                        $value = QUI\Utils\Security\Orthos::cleanHTML($value);
                        break;
                }

                $Config->set($section, $key, $value);
            }
        }

        $Config->save();

        // @todo muss in paket klasse ausgelagert werden
        // package config?
        if (strpos($file, OPT_DIR) !== false) {
            $_file = str_replace(OPT_DIR, '', $file);
            $_file = explode('/', $_file);

            try {
                $Package = QUI::getPackage($_file[0] . '/' . $_file[1]);

                QUI::getEvents()->fireEvent('packageConfigSave', array($Package));

            } catch (QUI\Exception $Exception) {
            }
        }

        // clear cache
        QUI\Cache\Manager::clearAll();
    }

    /**
     * Import a xml array to the database
     * the Array must come from self::getDataBaseFromXml
     *
     * @param array $dbfields - array with db fields
     */
    public static function importDataBase($dbfields)
    {
        $Table    = QUI::getDataBase()->Table();
        $projects = QUI\Projects\Manager::getConfig()->toArray();

        // globale tabellen erweitern / anlegen
        if (isset($dbfields['globals'])) {
            foreach ($dbfields['globals'] as $table) {
                $tbl = QUI::getDBTableName($table['suffix']);

                $Table->appendFields($tbl, $table['fields'], $table['engine']);

                if (isset($table['primary'])) {
                    $Table->setPrimaryKey($tbl, $table['primary']);
                }

                if (isset($table['unique'])) {
                    $Table->setUniqueColumns($tbl, $table['unique']);
                }

                if (isset($table['index'])) {
                    $index = $table['index'];

                    if (strpos($index, ',') !== false) {
                        $Table->setIndex($tbl, explode(',', $table['index']));
                    } else {
                        $Table->setIndex($tbl, $table['index']);
                    }
                }

                if (isset($table['auto_increment'])) {
                    $Table->setAutoIncrement($tbl, $table['auto_increment']);
                }

                if (isset($table['fulltext'])) {
                    $Table->setFulltext($tbl, $table['fulltext']);
                }
            }
        }

        // projekt tabellen erweitern / anlegen
        if (isset($dbfields['projects'])) {
            foreach ($dbfields['projects'] as $table) {
                if (!isset($table['suffix'])) {
                    continue;
                }

                $suffix = $table['suffix'];
                $fields = $table['fields'];
                $engine = $table['engine'];
                $noLang = false;

                if ($table['no-project-lang']) {
                    $noLang = true;
                }

                if ($table['no-site-reference'] !== true && $noLang === false) {
                    $fields = array(
                                  'id' => 'bigint(20) NOT NULL PRIMARY KEY'
                              ) + $fields;
                }

                // Projekte durchgehen
                foreach ($projects as $name => $params) {
                    $langs = explode(',', $params['langs']);

                    foreach ($langs as $lang) {
                        $tbl = QUI::getDBTableName($name . '_' . $lang . '_' . $suffix);

                        if ($noLang) {
                            $tbl = QUI::getDBTableName($name . '_' . $suffix);
                        }

                        $Table->appendFields($tbl, $fields, $engine);

                        if (isset($table['primary'])) {
                            $Table->setPrimaryKey($tbl, $table['primary']);
                        }

                        if (isset($table['index'])) {
                            $index = $table['index'];

                            if (strpos($index, ',') !== false) {
                                $Table->setIndex(
                                    $tbl,
                                    explode(',', $table['index'])
                                );
                            } else {
                                $Table->setIndex($tbl, $table['index']);
                            }
                        }

                        if (isset($table['auto_increment'])) {
                            $Table->setAutoIncrement(
                                $tbl,
                                $table['auto_increment']
                            );
                        }

                        if (isset($table['fulltext'])) {
                            $Table->setFulltext($tbl, $table['fulltext']);
                        }
                    }
                }
            }
        }

        // php executes
        if (isset($dbfields['execute'])) {
            foreach ($dbfields['execute'] as $exec) {
                $exec = str_replace('\\\\', '\\', $exec);

                if (!is_callable($exec)) {
                    QUI\System\Log::addInfo($exec . ' not callable');
                    continue;
                }

                call_user_func($exec);
            }
        }
    }

    /**
     * Import a database.xml
     *
     * @param string $xmlfile - Path to the file
     *
     * @throws QUI\Exception
     */
    public static function importDataBaseFromXml($xmlfile)
    {
        $dbfields = self::getDataBaseFromXml($xmlfile);

        if (!count($dbfields)) {
            return;
        }

        try {
            self::importDataBase($dbfields);

        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError(
                "Error on XML database import ($xmlfile): "
                . $Exception->getMessage()
            );

            throw $Exception;
        }
    }

    /**
     * Import a permissions.xml
     *
     * @param string $xmlfile - Path to the file
     * @param string $src - [optional] the source for the permissions
     */
    public static function importPermissionsFromXml($xmlfile, $src = '')
    {
        $Manager = QUI::getPermissionManager();
        $Manager->importPermissionsFromXml($xmlfile, $src);
    }
}
